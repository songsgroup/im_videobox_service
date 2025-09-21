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
 * Profile
 */
#[Group('admin/system/user/profile')]
class Profile extends \app\BaseController
{
    protected $noNeedLogin = [];

    /**
     * index
     *
     */
    public function index(){
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
     * 修改用户
     */
    #[Route('PUT','$')]
    public function updateProfile(){
        $userId = $this->auth->id;
    	$nickName = input('nickName','');
    	$phonenumber = input('phonenumber','');
    	$email = input('email','');
    	$sex = input('sex',0);

    	$user = UserModel::find($userId);
    	if (!$user) {
    		$this->error('用户不存在');
    	}
    	$user->nick_name   = $nickName;
    	$user->phonenumber = $phonenumber;
    	$user->email       = $email;
    	$user->sex         = $sex;
    	$user->save();

    	$this->success();
    }

    /**
     * 重置密码
     */
    #[Route('PUT','updatePwd')]
    public function updatePwd(){
        // 
    	$userId = $this->auth->id;
    	$oldPassword = input('oldPassword','');
    	$newPassword = input('newPassword','');

    	$user = UserModel::find($userId);
    	if (!$user) {
    		$this->error('用户不存在');
    	}
        if (!password_verify($oldPassword, $user->password)) {
            $this->error('密码错误!');
        }

    	$newPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->save(['password' => $newPassword]);

    	$this->success();
    }
    
    /**
     * 头像上传
     */
    #[Route('POST','avatar')]
    public function avatar(){
    	$userId = $this->auth->id;
        $file = request()->file('avatarfile');
        if ($file) {
            $fileInfo = pathinfo($file);
            $filePath = $fileInfo['dirname'] . '/' . $fileInfo['basename'];
            
            $filesize = $file->getSize();
            $suffixation = strtolower($file->getOriginalExtension());
            $fullName = $file->getOriginalName();
            $path = 'download/'.date('Y/m/d').'/';
            $realName = $file->md5() . '.' . $suffixation;
            $savePath = root_path('public') . $path;
            
            $info = $file->move(str_replace('/',DS,$savePath),$realName);

            if ($info) {
                $now = date('Y-m-d H:i:s');
                $imgUrl = '/'.$path.$realName;

                $user = UserModel::find($userId);
                $user->avatar = $imgUrl;
                $user->save();
                
                $r = [
                    'imgUrl'=>$imgUrl,
                ];
                $this->success($r);
            } else {
                $this->error($file->getError());
            }
        }
        $this->error('上传图片异常，请联系管理员');
    }
}
