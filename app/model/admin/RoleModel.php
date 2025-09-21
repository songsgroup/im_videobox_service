<?php
declare (strict_types = 1);

namespace app\model\admin;

use think\annotation\model\relation\BelongsToMany;

/**
 * RoleModel
 */
#[BelongsToMany("users", UserModel::class, UserRoleModel::class, "user_id",'role_id')]
#[BelongsToMany("dept", DeptModel::class, RoleDeptModel::class, "dept_id",'role_id')]
#[BelongsToMany("menu", MenuModel::class, RoleMenuModel::class, "menu_id",'role_id')]
class RoleModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_role';
    protected $pk = 'role_id';

    protected $append = [
            'admin',
// deptIds = null
// flag = false
// menuIds = null
// permissions = null
            'statusText',
        ];

    protected $default = [
            'role_id' => null,
            'role_name' => '',
            'role_key' => '',
            'role_sort' => 0,
            'data_scope' => '',
            'menu_check_strictl' => 1,
            'dept_check_strictl' => 1,
            'status' => 0,
            'del_flag' => 0,
            'create_by' => '',
            'create_time' => null,
            'update_by' => '',
            'update_time' => null,
            'remark' => '',
        ];

    public function getAdminAttr()
    {
        return $this->role_key=='admin';
    }

    public function getStatusTextAttr()
    {
        return ['0'=>'正常','1'=>'停用'][$this->status]??$this->status;
    }

    public static function getRoles()
    {
        return self::where('del_flag',0)->where('status',0)->where('role_key','<>','admin')->select()->toArray();
    }

    public static function search($where=[])
    {
        extract($where);

        $m = self::where('del_flag',0);
        $roleName && $m = $m->where('role_name','like','%'.$roleName.'%');
        $roleKey && $m = $m->where('role_Key','like','%'.$roleKey.'%');
        $status!==null && $m = $m->where('status',$status);
        $params && $m = $m->whereBetweenTime ('create_time',$params['beginTime'],$params['endTime']);
        $total = $m->count();
        $rows = $m->page($pageNum)
                    ->limit($pageSize)
                    ->select()
                    ->map(function($x){$x->dept;return $x;})
                    ->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

}
