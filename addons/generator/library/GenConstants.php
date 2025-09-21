<?php

namespace addons\generator\library;

class GenConstants
{
    /** 单表（增删改查） */
    const TPL_CRUD = "crud";

    /** 树表（增删改查） */
    const TPL_TREE = "tree";

    /** 主子表（增删改查） */
    const TPL_SUB = "sub";

    /** 树编码字段 */
    const TREE_CODE = "treeCode";

    /** 树父编码字段 */
    const TREE_PARENT_CODE = "treeParentCode";

    /** 树名称字段 */
    const TREE_NAME = "treeName";

    /** 上级菜单ID字段 */
    const PARENT_MENU_ID = "parentMenuId";

    /** 上级菜单名称字段 */
    const PARENT_MENU_NAME = "parentMenuName";

    /** 数据库字符串类型 */
    const COLUMNTYPE_STR = [ "char", "varchar", "nvarchar", "varchar2" ];

    /** 数据库文本类型 */
    const COLUMNTYPE_TEXT = [ "tinytext", "text", "mediumtext", "longtext" ];

    /** 数据库时间类型 */
    const COLUMNTYPE_TIME = [ "datetime", "time", "date", "timestamp" ];

    /** 数据库数字类型 */
    const COLUMNTYPE_NUMBER = [ "tinyint", "smallint", "mediumint", "int", "number", "integer",
            "bit", "bigint", "float", "double", "decimal" ];

    /** 页面不需要编辑字段 */
    const COLUMNNAME_NOT_EDIT = [ "id", "weigh", "create_by", "create_time", "del_flag","update_time","delete_time" ];

    /** 页面不需要显示的列表字段 */
    const COLUMNNAME_NOT_LIST = [ "id", "create_by", "create_time", "del_flag", "update_by",
            "update_time" ,"delete_time" ];

    /** 页面不需要查询字段 */
    const COLUMNNAME_NOT_QUERY = [ "id", "weigh", "create_by", "create_time", "del_flag", "update_by",
            "update_time", "remark", "delete_time" ];

    /** Entity基类字段 */
    const BASE_ENTITY = [ "createBy", "createTime", "updateBy", "updateTime", "remark" ,"deleteTime" ];

    /** Tree基类字段 */
    const TREE_ENTITY = [ "parentName", "parentId", "orderNum", "ancestors", "children" ];

    /** 文本框 */
    const HTML_INPUT = "input";

    /** 文本域 */
    const HTML_TEXTAREA = "textarea";

    /** 下拉框 */
    const HTML_SELECT = "select";

    /** 单选框 */
    const HTML_RADIO = "radio";

    /** 复选框 */
    const HTML_CHECKBOX = "checkbox";

    /** 日期控件 */
    const HTML_DATETIME = "datetime";

    /** 图片上传控件 */
    const HTML_IMAGE_UPLOAD = "imageUpload";

    /** 文件上传控件 */
    const HTML_FILE_UPLOAD = "fileUpload";

    /** 富文本控件 */
    const HTML_EDITOR = "editor";

    /** 字符串类型 */
    const TYPE_STRING = "String";

    /** 整型 */
    const TYPE_INTEGER = "Integer";

    /** 长整型 */
    const TYPE_LONG = "Long";

    /** 浮点型 */
    const TYPE_DOUBLE = "Double";

    /** 高精度计算类型 */
    const TYPE_BIGDECIMAL = "BigDecimal";

    /** 时间类型 */
    const TYPE_DATE = "Date";

    /** 模糊查询 */
    const QUERY_LIKE = "LIKE";

    /** 相等查询 */
    const QUERY_EQ = "EQ";

    /** 需要 */
    const REQUIRE = "1";
}