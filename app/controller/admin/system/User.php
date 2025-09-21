<?php
namespace app\controller\admin\system;

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
 * User
 */
#[Group('admin/system/user')]
class User extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize(){
        parent::initialize();
        $this->model = new UserModel;
    }
    
    #[Route('GET','profile$')]
    public function profile(){
        $user = $this->auth->getUser();
        if ($user) {
            $user->dept;
            $r = [
                    'data'=>$user,
                    'postGroup'=>$user->posts?$user->posts[0]?->postName:'',
                    'roleGroup'=>$user->roles?$user->roles[0]?->roleName:'',
                ];
        }
        $this->success($r);
    }

    /**
     * 获取部门树列表
     */
    #[Route('GET','deptTree')]
    #[PreAuthorize('hasPermi','system:user:list')]
    public function deptTree(){
        $r = (new DeptModel)->tree();
        
        $this->success(['data'=>$r]);
    }
    
    /**
     * 获取用户列表
     */
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','system:user:list')]
    public function list(){
    	$where = [];
    	$where['pageNum']    = input('pageNum/d',1);
    	$where['pageSize']   = input('pageSize/d',10);
    	$where['deptId']     = input('deptId',0);
    	$where['userName']   = input('userName','');
    	$where['phonenumber']= input('phonenumber','');
    	$where['status']     = input('status',null);
    	$where['params']     = input('params/a',[]);

        $r = UserModel::search($where);
        $this->success($r);
    }

    #[Route('POST','export')]
    #[PreAuthorize('hasPermi','system:user:export')]
    public function export(){
        $where = [];
        $where['pageNum'] = input('pageNum',1);
        $where['pageSize'] = input('pageSize',10);
        $where['deptId'] = input('deptId',0);
        $where['userName'] = input('userName','');
        $where['phonenumber'] = input('phonenumber','');
        $where['status'] = input('status',null);
        $where['params'] = input('params/a',[]);

        $r = UserModel::search($where);

        $header = implode("\t",['用户编号','用户名称','用户昵称','部门','手机号码','状态','创建时间']);
        $body = implode("\n",array_map(fn($x)=>implode("\t",[
                        $x['userId'],
                        $x['userName'],
                        $x['nickName'],
                        $x['dept']['deptName'],
                        "'".$x['phonenumber'],
                        $x['statusText'],
                        $x['createTime'],
                    ]),$r['rows']));
        $r = $header."\n".$body;
        return response($r);
    }

    /**
     * 根据用户编号获取详细信息
     */
    #[Route('GET','<id>')]
    #[PreAuthorize('hasPermi','system:user:query')]
    public function getInfo($id){
        $r = [
            'posts'=>PostModel::getPosts(),
            'roles'=>RoleModel::getRoles(),
        ];

        if ($id) {
            if ($user = $this->model->find($id)) {
                $user->dept;
                $r = array_merge($r,[
                        'data'=>$user,
                        'postIds'=>$user->posts->map(fn($x)=>$x->post_id)->toArray(),
                        'roleIds'=>$user->roles->map(fn($x)=>$x->role_id)->toArray(),
                    ]);
            }
        }
        $this->success($r);
    }

    #[Route('GET','//$')]
    #[PreAuthorize('hasPermi','system:user:query')]
    public function index(){
        $r = [
            'posts'=>PostModel::getPosts(),
            'roles'=>RoleModel::getRoles(),
        ];
        $this->success($r);
    }

    /**
     * 新增用户
     */
    #[Route('POST','$')]
    #[PreAuthorize('hasPermi','system:user:add')]
    public function new(){
    	$userName = input('userName','');
    	$password = input('password','');
    	$nickName = input('nickName','');
    	$deptId = input('deptId',0);
    	$phonenumber = input('phonenumber','');
    	$email = input('email','');
    	$sex = input('sex',0);
    	$status = input('status',0);
    	$remark = input('remark','');
    	$postIds = input('postIds/a',[]);
    	$roleIds = input('roleIds/a',[]);

    	$user = new UserModel;
    	$user->user_name   = $userName;
    	$user->password    = password_hash($password, PASSWORD_BCRYPT);
    	$user->nick_name   = $nickName;
    	$user->dept_id     = $deptId;
    	$user->phonenumber = $phonenumber;
    	$user->email       = $email;
    	$user->sex         = $sex;
    	$user->status      = $status;
    	$user->remark      = $remark;
    	$user->save();

    	UserPostModel::setUserPosts($user->user_id,$postIds);
    	UserRoleModel::setUserRoles($user->user_id,$roleIds);

    	$this->success();
    }

    /**
     * 修改用户
     */
    #[Route('PUT','$')]
    #[PreAuthorize('hasPermi','system:user:edit')]
    public function edit(){
    	$userId = input('userId',0);
    	$nickName = input('nickName','');
    	$deptId = input('deptId',0);
    	$phonenumber = input('phonenumber','');
    	$email = input('email','');
    	$sex = input('sex',0);
    	$status = input('status',0);
    	$remark = input('remark','');
    	$postIds = input('postIds/a',[]);
    	$roleIds = input('roleIds/a',[]);

    	$user = UserModel::find($userId);
    	if (!$user) {
    		$this->error('用户不存在');
    	}
    	$user->nick_name   = $nickName;
    	$user->dept_id     = $deptId;
    	$user->phonenumber = $phonenumber;
    	$user->email       = $email;
    	$user->sex         = $sex;
    	$user->status      = $status;
    	$user->remark      = $remark;
    	$user->save();

    	UserPostModel::setUserPosts($user->user_id,$postIds);
    	UserRoleModel::setUserRoles($user->user_id,$roleIds);

    	$this->success();
    }

    /**
     * 删除用户
     */
    #[Route('DELETE',':userId')]
    #[PreAuthorize('hasPermi','system:user:remove')]
    public function remove(int $userId)
    {
        $data = $this->model->find($userId);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->delete();
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 重置密码
     */
    #[Route('PUT','resetPwd')]
    #[PreAuthorize('hasPermi','system:user:resetPwd')]
    public function resetPwd(){
        $userId = input('userId',0);
        $password = input('password','');

        $user = UserModel::find($userId);
        if (!$user) {
            $this->error('用户不存在');
        }

        $password = password_hash($password, PASSWORD_BCRYPT);
        $user->save(['password' => $password]);

        $this->success();
    }

    /**
     * 状态修改
     */
    #[Route('PUT','changeStatus')]
    #[PreAuthorize('hasPermi','system:user:edit')]
    public function changeStatus(){
    	$userId = input('userId',0);
    	$status = input('status',1);

    	$user = UserModel::find($userId);
    	if (!$user) {
    		$this->error('用户不存在');
    	}
    	$r = $user->save(['status'=>$status]);
    	if (!$r) {
    		$this->error('操作失败');
    	}
    	$this->success();
    }

    #[Route('POST','importData')]
    #[PreAuthorize('hasPermi','system:user:import')]
    public function importData(){
        // TODO:
        $this->success();
    }
    
    #[Route('POST','importTemplate')]
    public function importTemplate(){
    }
}
