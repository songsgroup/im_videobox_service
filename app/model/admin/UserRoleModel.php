<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * UserRoleModel
 */
class UserRoleModel extends \think\model\Pivot
{
    // 表名
    protected $name = 'sys_user_role';

    protected $convertNameToCamel = true;
    
    protected $mapping = [
// user_id
// role_id
        ];
    
    public function role(){
        return $this->BelongsTo(RoleModel::class,'role_id','role_id');
    }
    
    public function user(){
        return $this->BelongsTo(UserModel::class,'user_id','user_id');
    }

    public static function setUserRoles(int $userId,array $roleIds)
    {
        $old = self::where('user_id',$userId)
                ->field(['role_id'])
                ->select()
                ->map(fn($x)=>$x->role_id)
                ->toArray();
        $dels = array_diff($old,$roleIds);
        $news = array_diff($roleIds,$old);
        if ($dels) {
            self::where('user_id',$userId)
                    ->where('role_id','in',$dels)
                    ->delete();
        }
        if ($news) {
            (new self)->saveAll(array_map(fn($roleId)=>['role_id'=>$roleId,'user_id'=>$userId],$news));
        }
        return ;
    }

}
