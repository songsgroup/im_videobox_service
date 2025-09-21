<?php
declare (strict_types = 1);

namespace app\model\admin;

use think\annotation\model\relation\BelongsTo;
use think\annotation\model\relation\BelongsToMany;

use constants\Constants;

/**
 * UserModel
 */
#[BelongsTo("dept", DeptModel::class, "dept_id",'dept_id')]
#[BelongsToMany("roles", RoleModel::class, UserRoleModel::class, "role_id",'user_id')]
#[BelongsToMany("posts", PostModel::class, UserPostModel::class, "post_id",'user_id')]
class UserModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_user';
    protected $pk = 'user_id';

    protected $permissionsMenus = [];

    protected $mapping = [
        //'user_id'=>'userId',
        //'dept_id'=>'deptId',
        //'user_name'=>'userName',
        //'nick_name'=>'nickName',
        //'user_type'=>'userType',
        //'email'=>'email',
        //'phonenumber'=>'phonenumber',
        //'sex'=>'sex',
        //'avatar'=>'avatar',
        //'password'=>'password',
        //'status'=>'status',
        //'del_flag'=>'delFlag',
        //'login_ip'=>'loginIp',
        //'login_date'=>'loginDate',
        //'create_by'=>'createBy',
        //'create_time'=>'createTime',
        //'update_by'=>'updateBy',
        //'update_time'=>'updateTime',
        //'remark'=>'remark',
        ];

    // 字段默认值
    protected $default = [
            'user_id'=>null,
            'dept_id'=>0,
            'user_name'=>'',
            'nick_name'=>'',
            'user_type'=>'00',
            'email'=>'',
            'phonenumber'=>'',
            'sex'=>2,
            'avatar'=>'',
            'password'=>'',
            'status'=>0,
            'del_flag'=>0,
            'login_ip'=>'',
            'login_date'=>'',
            'create_by'=>'',
            'create_time'=>'',
            'update_by'=>'',
            'update_time'=>'',
            'remark'=>'',
        ];

    protected $append = [
            'admin',
// "roleIds": null,
// "postIds": null,
// "roleId": null,
            'statusText',
        ];

    public function getAdminAttr()
    {
        $this->roles?->hidden(['pivot']);
        $roles = array_column($this->roles?->toArray()?:[],'roleKey');
        return in_array('admin',$roles);
    }

    public function getStatusTextAttr()
    {
        return ['0'=>'正常','1'=>'停用'][$this->status]??$this->status;
    }
    
    public function getPerms()
    {
        if ($this->admin) {
            return [Constants::ALL_PERMISSION];
        }
        $menus = call_user_func_array('array_merge', 
                    $this->roles->map(fn($role)=>$role->menu->hidden(['pivot']))
                        ->where('status',0)->toArray());
        return array_values(array_filter(array_unique(array_column($menus,'perms'))));
    }

    public function getPermissionsMenus()
    {
        if ($this->admin) {
            return MenuModel::getMenus();
        }
        $r = $this->roles->map(fn($x)=>$x->menu->hidden(['pivot']))->where('status',0)->where('menu_type','<>','F')->toArray();
        $r = call_user_func_array('array_merge', $r);
        $r = array_values(array_combine(array_column($r,'menuId'), $r));
        return $r;
    }

    public function getRouters($parentId=0)
    {
        if (!$this->permissionsMenus) {
            $this->permissionsMenus = $this->getPermissionsMenus();
        }
        $r = array_values(array_filter($this->permissionsMenus,fn($x)=>$x['parentId']==$parentId));
        array_multisort($r,SORT_ASC,SORT_NUMERIC,array_column($r,'orderNum'));
        $r = array_map(function ($x)use($parentId){
                $r = [
                    'name'      => ucfirst($x['path']),
                    'path'      => $x['isFrame']?($parentId?'':'/').$x['path']:$x['path'],
                    'hidden'    => !!$x['visible'],
                    'component' => $x['component']?:($parentId&&$x['component']===''?'ParentView':'Layout'),
                    'meta'      => [
                            'title'   =>$x['menuName'],
                            'icon'    =>$x['icon'],
                            'noCache' =>!$x['isCache'],
                            'link'    =>$x['isFrame']?null:$x['path'],
                        ],
                ];
                $x['isFrame'] && $x['menuType']=='M' && $children = $this->getRouters($x['menuId']);
                $x['isFrame'] && $x['menuType']=='M' && $r['alwaysShow']=true;
                $x['isFrame'] && $x['menuType']=='M' && $r['children']=$children;
                $x['isFrame'] && $x['menuType']=='M' && $r['redirect']='noRedirect';
                return $r;
            }, $r);
        return $r;
    }

    public static function search($where=[])
    {
        extract($where);

        $m = self::where('del_flag',0);
        if ($deptId) {
            $deptIds = array_merge([$deptId],DeptModel::childIds($deptId,true));
            $m = $m->where('dept_id','in',$deptIds);
        }
        $userName && $m = $m->where('user_name','like','%'.$userName.'%');
        $phonenumber && $m = $m->where('phonenumber','like','%'.$phonenumber.'%');
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
