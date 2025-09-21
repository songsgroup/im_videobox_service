<?php

namespace constants;

class Constants
{
    /**
     * UTF-8 字符集
     */
    const UTF8 = "UTF-8";

    /**
     * GBK 字符集
     */
    const GBK = "GBK";

    /**
     * www主域
     */
    const WWW = "www.";

    /**
     * http请求
     */
    const HTTP = "http://";

    /**
     * https请求
     */
    const HTTPS = "https://";

    /**
     * 通用成功标识
     */
    const SUCCESS = "0";

    /**
     * 通用失败标识
     */
    const FAIL = "1";

    /**
     * 登录成功
     */
    const LOGIN_SUCCESS = "Success";

    /**
     * 注销
     */
    const LOGOUT = "Logout";

    /**
     * 注册
     */
    const REGISTER = "Register";

    /**
     * 登录失败
     */
    const LOGIN_FAIL = "Error";

    /**
     * 所有权限标识
     */
    const ALL_PERMISSION = "*:*:*";

    /**
     * 管理员角色权限标识
     */
    const SUPER_ADMIN = "admin";

    /**
     * 角色权限分隔符
     */
    const ROLE_DELIMETER = ",";

    /**
     * 权限标识分隔符
     */
    const PERMISSION_DELIMETER = ",";

    /**
     * 验证码有效期（分钟）
     */
    const CAPTCHA_EXPIRATION = 2;

    /**
     * 令牌
     */
    const TOKEN = "token";

    /**
     * 令牌前缀
     */
    const TOKEN_PREFIX = "Bearer ";

    /**
     * 令牌前缀
     */
    const LOGIN_USER_KEY = "login_user_key";

    /**
     * 用户ID
     */
    const JWT_USERID = "userid";

    /**
     * 用户名称
     */
    const JWT_USERNAME = Claims.SUBJECT;

    /**
     * 用户头像
     */
    const JWT_AVATAR = "avatar";

    /**
     * 创建时间
     */
    const JWT_CREATED = "created";

    /**
     * 用户权限
     */
    const JWT_AUTHORITIES = "authorities";

    /**
     * 资源映射路径 前缀
     */
    const RESOURCE_PREFIX = "/profile";

    /**
     * RMI 远程方法调用
     */
    const LOOKUP_RMI = "rmi:";

    /**
     * LDAP 远程方法调用
     */
    const LOOKUP_LDAP = "ldap:";

    /**
     * LDAPS 远程方法调用
     */
    const LOOKUP_LDAPS = "ldaps:";

    /**
     * 定时任务白名单配置（仅允许访问的包名，如其他需要可以自行添加）
     */
    const JOB_WHITELIST_STR = [ "com.ruoyi" ];

    /**
     * 定时任务违规的字符
     */
    const JOB_ERROR_STR = [ "java.net.URL", "javax.naming.InitialContext", "org.yaml.snakeyaml",
            "org.springframework", "org.apache", "com.ruoyi.common.utils.file", "com.ruoyi.common.config", "com.ruoyi.generator" ];
}