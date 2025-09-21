<?php
namespace app\controller\admin\system\user;

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
 * AuthRole
 */
#[Group('admin/system/user/authRole')]
class AuthRole extends \app\BaseController
{
    protected $noNeedLogin = [];

    /**
     * 根据用户编号获取授权角色
     */
    #[Route('GET','<id>')]
    #[PreAuthorize('hasPermi','system:user:query')]
    public function authRole($id){
        $user = UserModel::find($id);
        $r = [
            'roles'=>RoleModel::getRoles(),
            'user'=>$user,
        ];
        $this->success($r);
    }

    /**
     * 用户授权角色
     */
    #[Route('PUT','$')]
    #[PreAuthorize('hasPermi','system:user:edit')]
    public function insertAuthRole(){
        $userId = input('userId/d',0);
        $roleIds = input('roleIds/a',[]);
        UserRoleModel::setUserRoles($userId,$roleIds);
        $this->success();
    }
}
