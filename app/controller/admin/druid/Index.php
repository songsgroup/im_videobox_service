<?php
namespace app\controller\admin\druid;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use think\facade\Cache;

/**
 * Index
 */
#[Group('admin/druid')]
class Index extends \app\BaseController
{
    protected $noNeedLogin = ['login','logout'];

    #[Route('*','login')]
    public function login(){
        $this->success();
    }

    #[Route('*','logout')]
    public function logout(){
        $this->success();
    }
}
