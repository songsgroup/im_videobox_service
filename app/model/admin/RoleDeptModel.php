<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * RoleDeptModel
 */
class RoleDeptModel extends \think\model\Pivot
{
    // 表名
    protected $name = 'sys_role_dept';

    protected $convertNameToCamel = true;
    
    protected $mapping = [
// role_id
// dept_id
        ];

    public static function setRoleDepts($roleId,$deptIds)
    {
        $old = self::where('role_id',$roleId)
                ->field(['dept_id'])
                ->select()
                ->map(fn($x)=>$x->dept_id)
                ->toArray();
        $dels = array_diff($old,$deptIds);
        $news = array_diff($deptIds,$old);
        if ($dels) {
            self::where('role_id',$roleId)
                    ->where('dept_id','in',$dels)
                    ->delete();
        }
        if ($news) {
            (new self)->saveAll(array_map(fn($deptId)=>['dept_id'=>$deptId,'role_id'=>$roleId],$news));
        }
        return ;
    }

}
