<?php
declare (strict_types = 1);

namespace addons\generator\model;

use think\annotation\model\relation\HasMany;

use addons\generator\library\GenConstants;
use think\facade\Db;

/**
 * GenTableModel
 */
#[HasMany('columns',GenTableColumnModel::class,'table_id','table_id')]
class GenTableModel extends \app\BaseModel
{
    // 表名
    protected $table = 'gen_table';
    protected $pk = 'table_id';

    protected $mapping = [
// table_id
// table_name
// table_comment
// sub_table_name
// sub_table_fk_name
// class_name
// tpl_category
// package_name
// module_name
// business_name
// function_name
// function_author
// gen_type
// gen_path
// options
// create_by
// create_time
// update_by
// update_time
// remark
        ];

    protected $append = [
            'isCrud',
            'isSub',
        ];
        
    protected $default = [
            'table_id'=>null,
            'table_name'=>'',
            'table_comment'=>'',
            'sub_table_name'=>null,
            'sub_table_fk_name'=>null,
            'class_name'=>'',
            'tpl_category'=>'crud',
            'tpl_web_type'=>'element-plus',
            'package_name'=>null,
            'module_name'=>null,
            'business_name'=>null,
            'function_name'=>null,
            'function_author'=>null,
            'gen_type'=>'0',
            'gen_path'=>'',
            'options'=>null,
            'create_by'=>'',
            'create_time'=>null,
            'update_by'=>'',
            'update_time'=>null,
            'remark'=>null,
        ];

    public function getIsCrudAttr(){
        return $this->tplCategory && $this->tplCategory==GenConstants::TPL_CRUD;
    }

    public function getIsSubAttr(){
        return $this->tplCategory && $this->tplCategory==GenConstants::TPL_SUB;
    }

    public static function search($where=[]){
        extract($where);

        $m = new self;
        $tableName && $m = $m->where('table_name','like','%'.$tableName.'%');
        $tableComment && $m = $m->where('table_comment','like','%'.$tableComment.'%');
        $params && $params['beginTime'] && $params['endTime']
            && $m = $m->whereBetweenTime ('create_time',$params['beginTime'],$params['endTime']);
        $total = $m->count();
        $rows = $m->page($pageNum)
                    ->limit($pageSize)
                    ->select()
                    ->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }
    
    public static function searchDb($where=[]){
        extract($where);

        switch (Db::connect()->getConfig('type')) {
            case 'sqlite':
                $m = Db::table('sqlite_master')
                    ->where('type','table')
                    ->where('name','<>','sqlite_sequence')
                    ->where('name','not like','qrtz_%')
                    ->where('name','not like','gen_%')
                    ->where('name','not in',fn($query)=>$query->table('gen_table')->field('table_name'));
                $tableName && $m = $m->where('name','like','%'.$tableName.'%');
                $total = $m->count();
                $rows = $m->page($pageNum)
                            ->limit($pageSize)
                            ->order('name','desc')
                            ->field(['name as table_name'])
                            ->select()
                            ->toArray();
                break;
            default:
                $table_schema = current(current(Db::query('select database()')));
                $m = Db::table('information_schema.tables')
                    ->where('table_schema',$table_schema)
                    ->where('table_name','not like','qrtz_%')
                    ->where('table_name','not like','gen_%')
                    ->where('table_name','not in',fn($query)=>$query->table('gen_table')->field('table_name'));
                $tableName && $m = $m->where('table_name','like','%'.$tableName.'%');
                $tableComment && $m = $m->where('table_comment','like','%'.$tableComment.'%');
                $total = $m->count();
                $rows = $m->page($pageNum)
                            ->limit($pageSize)
                            ->order('create_time','desc')
                            ->field(['table_name', 'table_comment', 'create_time', 'update_time'])
                            ->select()
                            ->toArray();
                break;
        }
        $rows = array_map(fn($x)=>new self(array_change_key_case($x)),$rows);
        return ['total'=>$total,'rows'=>$rows];
    }
    
    public static function searchDbByName($name){
        switch (Db::connect()->getConfig('type')) {
            case 'sqlite':
                $m = Db::table('sqlite_master')
                    ->where('type','table')
                    ->where('name','<>','sqlite_sequence')
                    ->where('name','not like','qrtz_%')
                    ->where('name','not like','gen_%')
                    ->where('name','in',$name);
                $total = $m->count();
                $rows = $m->order('name','desc')
                            ->field(['name as table_name','name as table_comment'])
                            ->select()
                            ->toArray();
                break;
            default:
                $table_schema = current(current(Db::query('select database()')));
                $m = Db::table('information_schema.tables')
                    ->where('table_schema',$table_schema)
                    ->where('table_name','not like','qrtz_%')
                    ->where('table_name','not like','gen_%')
                    ->where('table_name','in',$name);
                $total = $m->count();
                $rows = $m->order('CREATE_TIME','desc')
                            ->field(['table_name', 'table_comment', 'create_time', 'update_time'])
                            ->select()
                            ->toArray();
                break;
        }
        $rows = array_map(fn($x)=>new self(array_change_key_case($x)),$rows);
        return ['total'=>$total,'rows'=>$rows];
    }

}
