<?php
namespace app\controller\admin\system\role;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use think\facade\Cache;
use app\model\admin\UserModel;
use app\model\admin\DeptModel;
use app\model\admin\PostModel;
use app\model\admin\RoleModel;
use app\model\admin\UserPostModel;
use app\model\admin\UserRoleModel;

/**
 * AuthUser
 */
#[Group('admin/system/role/authUser')]
class AuthUser extends \app\BaseController
{
    protected $noNeedLogin = [];

    /**
     * 查询已分配用户角色列表
     */
    #[Route('GET','allocatedList')]
    #[PreAuthorize('hasPermi','system:role:list')]
    public function allocatedList(){
    	$pageNum = input('pageNum/d',1);
    	$pageSize = input('pageSize/d',10);
    	$roleId = input('roleId/d',0);
    	$userName = input('userName','');
    	$phonenumber = input('phonenumber','');

        $m = UserModel::where('del_flag',0)
        			->where('status',0)
        			->whereIn('user_id',fn($query)=>$query->name((new UserRoleModel)->getName())->where('role_id',$roleId)->field('user_id'));
        $userName && $m = $m->whereLike('user_name','%'.$userName.'%');
        $phonenumber && $m = $m->whereLike('user_name','%'.$phonenumber.'%');
        $total = 0;
        $rows = [];
        $this->success([
	        	'total'=>$m->count(),
	        	'rows'=>$m->page($pageNum)->limit($pageSize)->select(),
	        ]);
    }

    /**
     * 查询未分配用户角色列表
     */
    #[Route('GET','unallocatedList')]
    #[PreAuthorize('hasPermi','system:role:list')]
    public function unallocatedList(){
    	$pageNum = input('pageNum/d',1);
    	$pageSize = input('pageSize/d',10);
    	$roleId = input('roleId',0);
    	$userName = input('userName','');
    	$phonenumber = input('phonenumber','');
    	
        $m = UserModel::where('del_flag',0)
        			->where('status',0)
        			->whereNotIn('user_id',fn($query)=>$query->name((new UserRoleModel)->getName())->where('role_id',$roleId)->field('user_id'));
        $userName && $m = $m->whereLike('user_name','%'.$userName.'%');
        $phonenumber && $m = $m->whereLike('user_name','%'.$phonenumber.'%');
        $total = 0;
        $rows = [];
        $this->success([
	        	'total'=>$m->count(),
	        	'rows'=>$m->page($pageNum)->limit($pageSize)->select(),
	        ]);
    }

    /**
     * 取消授权用户
     */
    #[Route('PUT','cancel')]
    #[PreAuthorize('hasPermi','system:role:edit')]
    public function cancel(){
    	$roleId = input('roleId/d',0);
    	$userId = input('userId/d',0);

        $r = UserRoleModel::where('role_id',$roleId)->where('user_id',$userId)->delete();
        if (!$r) {
        	$this->error();
        }
        $this->success();
    }

    /**
     * 批量取消授权用户
     */
    #[Route('PUT','cancelAll')]
    #[PreAuthorize('hasPermi','system:role:edit')]
    public function cancelAll(){
    	$roleId = input('roleId/d',0);
    	$userIds = input('userIds','');

        $r = UserRoleModel::where('role_id',$roleId)->whereIn('user_id',$userIds)->delete();
        if (!$r) {
        	$this->error();
        }
        $this->success();
    }

    /**
     * 批量选择用户授权
     */
    #[Route('PUT','selectAll')]
    #[PreAuthorize('hasPermi','system:role:edit')]
    public function selectAll(){
    	$roleId = input('roleId/d',0);
    	$userIds = input('userIds','');

        $r = UserRoleModel::insertAll(array_map(fn($userId)=>['role_id'=>$roleId,'user_id'=>$userId],explode(',',$userIds)));
        if (!$r) {
        	$this->error();
        }
        $this->success();
    }
}
