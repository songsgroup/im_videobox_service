<?php

namespace helper;

/**
 * sql操作工具类
 */
class SqlUtil
{
    /**
     * 定义常用的 sql关键字
     */
    const SQL_REGEX = "and |extractvalue|updatexml|exec |insert |select |delete |update |drop |count |chr |mid |master |truncate |char |declare |or |+|user()";

    /**
     * 仅支持字母、数字、下划线、空格、逗号、小数点（支持多个字段排序）
     */
    const SQL_PATTERN = "/[a-zA-Z0-9_\\ \\,\\.]+/";

    /**
     * 限制orderBy最大长度
     */
    private const ORDER_BY_MAX_LENGTH = 500;

    /**
     * 检查字符，防止注入绕过
     */
    public static function escapeOrderBySql(string $value):string {
        if ($value && !self::isValidOrderBySql($value)){
            throw new Exception("参数不符合规范，不能进行查询");
        }
        if(strlen($value) > self::ORDER_BY_MAX_LENGTH){
            throw new Exception("参数已超过最大限制，不能进行查询");
        }
        return $value;
    }

    /**
     * 验证 order by 语法是否符合规范
     */
    public static function isValidOrderBySql(string $value):boolean{
        return !!preg_match(self::SQL_PATTERN,$value);
    }

    /**
     * SQL关键字检查
     */
    public static function filterKeyword(string $value){
        if (!$value){
            return;
        }
        $sqlKeywords = explode('|',self::SQL_REGEX);
        foreach ($sqlKeywords as $sqlKeyword){
            if (strpos(strtolower($value), $sqlKeyword) > -1){
                throw new Exception("参数存在SQL注入风险");
            }
        }
    }

}
