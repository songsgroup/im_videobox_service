<?php

namespace addons\apidoc;

use think\Addons;

/**
 * Apidoc
 */
class Apidoc extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        if(!file_exists(root_path('config').'apidoc.php')){
            $rst = copy(
                    $this->addonPath('config').'apidoc.php',
                    root_path('config').'apidoc.php'
                );
        }
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        @unlink(root_path('config').'apidoc.php');
        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {
        return true;
    }

    /**
     * 插件停用方法
     * @return bool
     */
    public function disable()
    {
        return true;
    }

    /**
     * 添加命名空间
     */
    public function appInit()
    {
        //添加命名空间
        if (!class_exists('\Doctrine\Common\Lexer')) {
            addNamespace('Doctrine\\Common\\Lexer\\',[
                $this->addonPath('library/lexer/src')
            ]);
        }
        if (!class_exists('\Doctrine\Common\Annotations')) {
            addNamespace('Doctrine\\Common\\Annotations\\',[
                $this->addonPath('library/annotations/lib/Doctrine/Common/Annotations')
            ]);
        }
        if (!class_exists('\hg\apidoc')) {
            addNamespace('hg\\apidoc\\',[
                $this->addonPath('library/apidoc/src')
            ]);
        }
    }
}
