<?php

namespace addons\cron;

use think\Addons;
use app\model\admin\MenuModel;
use think\Console;

/**
 * Cron插件
 */
class Cron extends Addons
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
        MenuModel::enable("job");
        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        MenuModel::disable("job");
        return true;
    }

    /**
     * appInit
     */
    public function appInit(){
        // 添加cron指令
        Console::starting(function (Console $console) {
            $console->addCommands([
                command\Cron::class,
            ]);
        });
    }
}
