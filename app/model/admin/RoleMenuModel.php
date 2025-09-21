<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * RoleMenuModel
 */
class RoleMenuModel extends \think\model\Pivot
{
    // 表名
    protected $name = 'sys_role_menu';

    protected $convertNameToCamel = true;
    
    protected $mapping = [
// role_id
// menu_id
        ];

    public static function setRoleMenus($roleId,$menuIds)
    {
        $old = self::where('role_id',$roleId)
                ->field(['menu_id'])
                ->select()
                ->map(fn($x)=>$x->menu_id)
                ->toArray();
        $dels = array_diff($old,$menuIds);
        if ($dels) {
            self::where('role_id',$roleId)
                    ->where('menu_id','in',$dels)
                    ->delete();
        }
        $news = array_diff($menuIds,$old);
        if ($news) {
            (new self)->saveAll(array_map(fn($menuId)=>['menu_id'=>$menuId,'role_id'=>$roleId],$news));
        }
        return ;
    }
}
