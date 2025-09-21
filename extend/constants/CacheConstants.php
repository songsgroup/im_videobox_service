<?php

namespace constants;

class CacheConstants
{
    /**
     * 登录用户 redis key
     */
    const LOGIN_TOKEN_KEY = "login_tokens:";

    /**
     * 验证码 redis key
     */
    const CAPTCHA_CODE_KEY = "captcha_codes:";

    /**
     * 参数管理 cache key
     */
    const SYS_CONFIG_KEY = "sys_config:";

    /**
     * 字典管理 cache key
     */
    const SYS_DICT_KEY = "sys_dict:";

    /**
     * 防重提交 redis key
     */
    const REPEAT_SUBMIT_KEY = "repeat_submit:";

    /**
     * 限流 redis key
     */
    const RATE_LIMIT_KEY = "rate_limit:";

    /**
     * 登录账户密码错误次数 redis key
     */
    const PWD_ERR_CNT_KEY = "pwd_err_cnt:";
}