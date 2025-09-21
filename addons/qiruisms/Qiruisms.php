<?php

namespace addons\qiruisms;

use think\Addons;

/**
 * Qiruisms插件
 */
class Qiruisms extends Addons
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
        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        return true;
    }

    /**
     * 短信发送
     * @param Sms $params
     * @return mixed
     */
    public function smsSend(array &$params)
    {
        $qiruisms = new library\Sms();
        $r = $qiruisms->send($params['mobile'],"您的验证码： {$params['code']}");
        $r = json_decode($r,true)??['success'=>false];
        return $r['success'];
    }

    /**
     * 短信发送通知（msg参数直接构建实际短信内容即可）
     * @param   array $params
     * @return  boolean
     */
    public function smsNotice(array &$params)
    {
        $qiruisms = new library\Sms();
        $r = $qiruisms->send($params['mobile'],$params['msg']);
        $r = json_decode($r,true)??['success'=>false];
        return $r['success'];
    }

    /**
     * 检测验证是否正确
     * @param   Sms $params
     * @return  boolean
     */
    public function smsCheck(&$params):bool
    {
        return TRUE;
    }
}
