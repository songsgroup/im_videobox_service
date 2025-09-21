<?php

namespace addons\generator\library;

use addons\generator\model\GenTableModel;
use addons\generator\model\GenTableColumnModel;
use think\helper\Str;

/**
 * GenUtils
 */
class GenUtils
{
    
    /**
     * 初始化表信息
     */
    public static function initTable(GenTableModel &$table, string $operName){
        $table['className'] = self::convertClassName($table['tableName']);
        $tableNameA = explode('_',$table['tableName']);
        count($tableNameA)==1 && array_unshift($tableNameA,'nprefix');
        $table['packageName'] = env('gen_packageName','app..admin');
        $table['moduleName'] = current(array_slice(explode('.',$table['packageName']),-1));
        $table['businessName'] = array_reverse($tableNameA)[0];
        if ( array_key_exists($tableNameA[1],get_addons_list()) ) {
            $table['packageName'] = 'addons.'.$tableNameA[1];
            $table['moduleName'] = $tableNameA[1];
            $table['businessName'] = Str::camel(implode('_',array_slice($tableNameA,2)));
            $table['className'] = self::convertClassName(implode('_',array_slice($tableNameA,2)));
        }
        $table['functionName'] = preg_replace('/(?:表|若依|心衍)/','',$table['tableComment']);
        $table['functionAuthor'] = env('gen_author','心衍');
        $table['createBy'] = $operName;
    }

    /**
     * 初始化列属性字段
     */
    public static function initColumnField(GenTableColumnModel &$column, GenTableModel $table){
        $dataType = self::getDbType($column['columnType']);
        $columnName = $column['columnName'];
        $column['tableId'] = $table['tableId'];
        $column['createBy'] = $table['createBy'];
        // 设置java字段名
        $column['javaField'] = Str::camel($columnName);
        // 设置默认类型
        $column['javaType'] = GenConstants::TYPE_STRING;
        $column['queryType'] = GenConstants::QUERY_EQ;

        if (in_array($dataType,GenConstants::COLUMNTYPE_STR)
            || in_array($dataType,GenConstants::COLUMNTYPE_TEXT)){
            // 字符串长度超过500设置为文本域
            $columnLength = self::getColumnLength($column['columnType']);
            $htmlType = $columnLength >= 500 
                        || in_array($dataType,GenConstants::COLUMNTYPE_TEXT)
                            ? GenConstants::HTML_TEXTAREA 
                            : GenConstants::HTML_INPUT;
            $column['htmlType'] = $htmlType;
        }else if (in_array($dataType,GenConstants::COLUMNTYPE_TIME)){
            $column['javaType'] = GenConstants::TYPE_DATE;
            $column['htmlType'] = GenConstants::HTML_DATETIME;
        }else if (in_array($dataType,GenConstants::COLUMNTYPE_NUMBER)){
            $column['htmlType'] = GenConstants::HTML_INPUT;

            // 如果是浮点型 统一用BigDecimal
            preg_match('/\((.*)\)/',$column['columnType'],$str) or $str = [false,0];
            $str = explode(',',$str[1]);
            if ($str && count($str) == 2 && intval($str[1]) > 0){
                $column['javaType'] = GenConstants::TYPE_BIGDECIMAL;
            }
            // 如果是整形
            else if ($str && count($str) == 1 && intval($str[0]) <= 10){
                $column['javaType'] = GenConstants::TYPE_INTEGER;
            }
            // 长整形
            else{
                $column['javaType'] = GenConstants::TYPE_LONG;
            }
        }

        // 插入字段（默认所有字段都需要插入）
        $column['isInsert'] = GenConstants::REQUIRE;

        // 编辑字段
        if (!in_array($columnName,GenConstants::COLUMNNAME_NOT_EDIT) && !$column['isPk']){
            $column['isEdit'] = GenConstants::REQUIRE;
        }
        // 列表字段
        if (!in_array($columnName,GenConstants::COLUMNNAME_NOT_LIST) && !$column['isPk']){
            $column['isList'] = GenConstants::REQUIRE;
        }
        // 查询字段
        if (!in_array($columnName,GenConstants::COLUMNNAME_NOT_QUERY) && !$column['isPk']){
            $column['isQuery'] = GenConstants::REQUIRE;
        }

        // 查询字段类型
        if (str_ends_with(strtolower($columnName), 'name')){
            $column['queryType'] = GenConstants::QUERY_LIKE;
        }
        // 状态字段设置单选框
        if (str_ends_with(strtolower($columnName), 'status')){
            $column['htmlType'] = GenConstants::HTML_RADIO;
        }
        // 类型&性别字段设置下拉框
        else if (str_ends_with(strtolower($columnName), 'type')
                || str_ends_with(strtolower($columnName), 'sex')){
            $column['htmlType'] = GenConstants::HTML_SELECT;
        }
        // 图片字段设置图片上传控件
        else if (str_ends_with(strtolower($columnName), 'image')){
            $column['htmlType'] = GenConstants::HTML_IMAGE_UPLOAD;
        }
        // 文件字段设置文件上传控件
        else if (str_ends_with(strtolower($columnName), 'file')){
            $column['htmlType'] = GenConstants::HTML_FILE_UPLOAD;
        }
        // 内容字段设置富文本控件
        else if (str_ends_with(strtolower($columnName), 'content')){
            $column['htmlType'] = GenConstants::HTML_EDITOR;
        }
        if(!$column['columnComment']){
            switch ($columnName) {
                case 'id':          $column['columnComment'] = 'ID';       break;
                case 'title':       $column['columnComment'] = '标题';     break;
                case 'name':        $column['columnComment'] = '名称';     break;
                case 'sex':         $column['columnComment'] = '性别';     break;
                case 'type':        $column['columnComment'] = '类型';     break;
                case 'weigh':       $column['columnComment'] = '权重';     break;
                case 'status':      $column['columnComment'] = '状态';     break;
                case 'create_by':   $column['columnComment'] = '创建者';   break;
                case 'create_time': $column['columnComment'] = '创建时间'; break;
                case 'update_by':   $column['columnComment'] = '更新者';   break;
                case 'update_time': $column['columnComment'] = '更新时间'; break;
                case 'delete_time': $column['columnComment'] = '删除时间'; break;
                case 'del_flag':    $column['columnComment'] = '已删除';   break;
                default:
                    $column['columnComment'] = $column['columnName'];
            }
        }
        if (str_contains($column['columnComment'],':')) {
            $column['columnComment'] = explode(':',$column['columnComment'])[0];
        }
    }
    
    /**
     * 表名转换成Java类名
     * 
     * @param tableName 表名称
     * @return 类名
     */
    public static function convertClassName($tableName){
        $autoRemovePre = false;//GenConfig.getAutoRemovePre();
        $tablePrefix = '';//GenConfig.getTablePrefix();
        if ($autoRemovePre && $tablePrefix){
            $searchList = explode(',',tablePrefix);
            foreach ($searchList as $s){
                if(str_starts_with($tableName,$s)){
                    $tableName = substr($tableName,strlen($s));
                    break;
                }
            }
        }
        return Str::studly($tableName);
    }
    
    /**
     * 获取数据库类型字段
     * 
     * @param columnType 列类型
     * @return 截取后的列类型
     */
    public static function getDbType($columnType){
        if (strpos($columnType,'(') > 0){
            return explode('(',$columnType)[0];
        }else{
            return $columnType;
        }
    }
    
    /**
     * 获取字段长度
     * 
     * @param columnType 列类型
     * @return 截取后的列类型
     */
    public static function getColumnLength($columnType){
        if (strpos($columnType, "(")!==false){
            $r = preg_match('/\((.*)\)/',$columnType,$m);
            if(!$r)return 0;
            return intval($m[1]);
        }else{
            return 0;
        }
    }
}
