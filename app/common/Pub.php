<?php
namespace app\common;

use app\common\LaiKeLogUtils;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

//全局变量
class Pub
{
    /**
     * 全局变量
     * 系统管理员，用户消费都放到这个账户上
     */
    public static $SysUserId = '_PubAdminUser';   
     
}