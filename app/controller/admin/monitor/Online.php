<?php
namespace app\controller\admin\monitor;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use app\model\admin\UserModel;
use app\model\admin\LogininforModel;

/**
 * Online
 */
#[Group('admin/monitor/online')]
class Online extends \app\BaseController
{
    protected $noNeedLogin = [];

    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','monitor:online:list')]
    public function list()
    {
    	$where = [];
    	$where['ipaddr'] = input('ipaddr','');
    	$where['userName'] = input('userName','');
    	
    	$rows = \think\facade\Cache::getTagItems(\constants\CacheConstants::LOGIN_TOKEN_KEY);
    	$rows = array_map(function($x){
    	        $d=\think\facade\Cache::get($x);
    	        if(!$d)return;
    	        return $d+['uuid'=>substr($x,strlen(\constants\CacheConstants::LOGIN_TOKEN_KEY))];
    	    },$rows);
    	$rows = array_values(array_filter($rows));
    	
    	$user_ids = array_unique(array_column($rows,'user_id'));
    	$info_ids = array_unique(array_column($rows,'info_id'));
    	$users = UserModel::where(['user_id'=>$user_ids])->with('dept')->select()->toArray();
    	$infos = LogininforModel::where(['info_id'=>$info_ids])->select()->toArray();
        $users = array_combine($user_ids,$users);
        $infos = array_combine($info_ids,$infos);
        
        $rows = array_map(fn($x)=>
                [
                    'tokenId'      =>$x['uuid'],
                    'deptName'     =>$users[$x['user_id']]['dept']['deptName'],
                    'userName'     =>$infos[$x['info_id']]['userName'],
                    'ipaddr'       =>$infos[$x['info_id']]['ipaddr'],
                    'loginLocation'=>$infos[$x['info_id']]['loginLocation'],
                    'loginTime'    =>$infos[$x['info_id']]['loginTime'],
                    'browser'      =>$infos[$x['info_id']]['browser'],
                    'os'           =>$infos[$x['info_id']]['os'],
                ],
            $rows);
            
        $r = [
            'rows' => $rows,
        ];
        $this->success($r);
    }
    
    #[Route('DELETE',':tokenId')]
    #[PreAuthorize('hasPermi','monitor:online:forceLogout')]
    public function forceLogout($tokenId)
    {
        $userKey = \constants\CacheConstants::LOGIN_TOKEN_KEY.$tokenId;
        \think\facade\Cache::delete($userKey);
        $this->success();
    }
}
