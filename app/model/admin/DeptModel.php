<?php
declare (strict_types = 1);

namespace app\model\admin;

use think\annotation\model\relation\BelongsTo;
use think\annotation\model\relation\HasMany;

/**
 * DeptModel
 */
#[BelongsTo("parent", DeptModel::class, "parent_id",'dept_id')]
#[HasMany("children", DeptModel::class, "parent_id",'dept_id')]
class DeptModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_dept';
    protected $pk = 'dept_id';

    protected $deptList = [];
    
    protected $mapping = [
// dept_id
// parent_id
// ancestors
// dept_name
// order_num
// leader
// phone
// email
// status
// del_flag
// create_by
// create_time
// update_by
// update_time
        ];

    protected $append = [
            'parentName'
        ];

    public function getParentNameAttr()
    {
        return $this->parent?->dept_name;
    }

    public function tree($parentId=0)
    {
        if (!$this->deptList) {
            $this->deptList = $this->where('del_flag',0)->where('status',0)->select()->toArray();
        }
        $r = array_values(array_filter($this->deptList,fn($x)=>$x['parentId']==$parentId));
        array_multisort($r,SORT_ASC,SORT_NUMERIC,array_column($r,'orderNum'));
        $r = array_map(function ($x)use($parentId){
                $r = [
                    'id'   => $x['deptId'],
                    'label'=> $x['deptName'],
                ];
                $children = $this->tree($x['deptId']);
                $children && $r['children']=$children;
                return $r;
            }, $r);
        return $r;
    }

    public static function childIds($deptId,$deep=0)
    {
        if ($deep) {
            $r = self::where('del_flag',0)
                        ->whereFindInSet('ancestors',$deptId)
                        ->field('dept_id')
                        ->select()
                        ->map(fn($x)=>$x->dept_id)
                        ->toArray();
        }else{
            $r = self::where('del_flag',0)
                        ->where('parent_id',$deptId)
                        ->field('dept_id')
                        ->select()
                        ->map(fn($x)=>$x->dept_id)
                        ->toArray();
        }
        return $r;
    }

    public static function search($where=[])
    {
        extract($where);

        $m = self::where('del_flag',0);
        $deptName && $m = $m->where('dept_name','like','%'.$deptName.'%');
        $status!==null && $m = $m->where('status',$status);
        $r = $m->select()->toArray();
        return $r;
    }

}
