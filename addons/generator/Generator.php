<?php

namespace addons\generator;

use think\Addons;
use app\model\admin\MenuModel;
use think\Console;

/**
 * Generator插件
 */
class Generator extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {
        MenuModel::enable("gen");
        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        MenuModel::disable("gen");
        return true;
    }
}
