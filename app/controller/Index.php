<?php
namespace app\controller;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;

/**
 * Index
 */
class Index extends \app\BaseController
{
    protected $noNeedLogin = ['index'];

    /**
     * 首页
     *
     */
    #[Route('*','/')]
    public function index()
    {
        // 
    }
}
