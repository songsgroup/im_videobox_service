<?php
declare (strict_types = 1);

namespace addons\generator\model;

use think\annotation\model\relation\BelongsTo;

use think\facade\Db;

/**
 * GenTableColumnModel
 */
#[BelongsTo('table',GenTableModel::class,'table_id','table_id')]
#[BelongsTo('dicttype',DictTypeModel::class,'dict_type','dict_type')]
class GenTableColumnModel extends \app\BaseModel
{
    // 表名
    protected $table = 'gen_table_column';
    protected $pk = 'column_id';

    protected $append = [
            'isSuperColumn',
            'isUsableColumn',
        ];
        
    protected $default = [
            'table_id'=>null,
            'column_name'=>'',
            'column_comment'=>'',
            'column_type'=>null,
            'java_type'=>null,
            'java_field'=>null,
            'is_pk'=>0,
            'is_increment'=>0,
            'is_required'=>0,
            'is_insert'=>0,
            'is_edit'=>0,
            'is_list'=>0,
            'is_query'=>0,
            'query_type'=>'EQ',
            'html_type'=>null,
            'dict_type'=>null,
            'sort'=>0,
            'create_by'=>'',
            // 'create_time'=>null,
            'update_by'=>'',
            // 'update_time'=>null,
        ];

    public function getIsSuperColumnAttr(){
        return in_array($this->javaField,[
                // BaseEntity
                "createBy", "createTime", "updateBy", "updateTime", "deleteTime", "remark", "weigh",
                // TreeEntity
                "parentName", "parentId", "orderNum", "ancestors"
            ]);
    }

    public function getIsUsableColumnAttr(){
        return in_array($this->javaField,["parentId", "orderNum", "remark"]);
    }

    public static function selectDbColumnsByName(array|string $tableName=''){
        switch (Db::connect()->getConfig('type')) {
            case 'sqlite':
                $auto_increment = Db::table('sqlite_sequence')->where('name',$tableName)->find()?1:0;
                $rows = Db::query("PRAGMA table_info({$tableName});");
                $rows = array_map(fn($row)=>[
                            'column_name'=>$row['name'],
                            'is_required'=>($row['notnull'] && !$row['pk'])?1:0,
                            'is_pk'=>$row['pk'],
                            'sort'=>$row['cid'],
                            'is_increment'=>($auto_increment && $row['pk'])?1:0,
                            'column_type'=>strtolower(str_replace(['TEXT(19)','TEXT(','INTEGER'],['datetime','varchar(','int'],$row['type'])),
                        ],$rows);
                $total = count($rows);
                break;
            default:
                $table_schema = current(current(Db::query('select database()')));
                $m = Db::table('information_schema.columns')
                    ->where('table_schema',$table_schema)
                    ->where('table_name',$tableName);
                $total = $m->count();
                $rows = $m->order('ordinal_position')
                            ->field(['column_name',
                                    "(case when (is_nullable = 'no' and column_key != 'PRI') then '1' else '0' end) as is_required",
                                    "(case when column_key = 'PRI' then '1' else '0' end) as is_pk",
                                    'ordinal_position as sort',
                                    'column_comment',
                                    "(case when extra = 'auto_increment' then '1' else '0' end) as is_increment",
                                    'column_type'
                                ])
                            ->select()
                            ->toArray();
                break;
        }
        $rows = array_map(fn($x)=>new self(array_change_key_case($x)),$rows);
        return ['total'=>$total,'rows'=>$rows];
    }
}
