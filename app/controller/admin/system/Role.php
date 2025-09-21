<?php
namespace app\controller\admin\system;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use think\facade\Cache;
use app\model\admin\RoleModel;
use app\model\admin\RoleMenuModel;
use app\model\admin\DeptModel;
use app\model\admin\RoleDeptModel;
use think\facade\Request;

/**
 * Role
 */
#[Group('admin/system/role')]
class Role extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize(){
        parent::initialize();
        $this->model = new RoleModel;
    }
    
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','system:role:list')]
    public function list(){
    	$where = [];
    	$where['pageNum'] = input('pageNum/d',1);
    	$where['pageSize'] = input('pageSize/d',10);
    	$where['roleName'] = input('roleName','');
    	$where['roleKey'] = input('roleKey','');
    	$where['status'] = input('status',null);
    	$where['params'] = input('params/a',[]);

        $r = RoleModel::search($where);
        $this->success($r);
    }

    #[Route('POST','export')]
    #[PreAuthorize('hasPermi','system:role:export')]
    public function export(){
        $where = [];
        $where['pageNum'] = input('pageNum',1);
        $where['pageSize'] = input('pageSize',10);
        $where['roleName'] = input('roleName','');
        $where['roleKey'] = input('roleKey','');
        $where['status'] = input('status',null);
        $where['params'] = input('params/a',[]);

        $r = RoleModel::search($where);

        $header = implode("\t",['角色编号','角色名称','权限字符','显示顺序','状态','创建时间']);
        $body = implode("\n",array_map(fn($x)=>implode("\t",[
                        $x['roleId'],
                        $x['roleName'],
                        $x['roleKey'],
                        $x['roleSort'],
                        $x['statusText'],
                        $x['createTime'],
                    ]),$r['rows']));
        $r = $header."\n".$body;
        return response($r);
    }

    /**
     * 根据角色编号获取详细信息
     */
    #[Route('GET','<id>')]
    #[PreAuthorize('hasPermi','system:role:query')]
    public function getInfo($id){
    	$r = [
    		'data' => $this->model->find($id)
    	];
        $this->success($r);
    }

    /**
     * 新增角色
     */
    #[Route('POST','$')]
    #[PreAuthorize('hasPermi','system:role:add')]
    public function add(){
        $data = [
            'deptCheckStrictly'=> input('deptCheckStrictly',true)?1:0,
            'menuCheckStrictly'=> input('menuCheckStrictly',true)?1:0,
            'roleName'=> input('roleName',''),
            'roleKey'=> input('roleKey',''),
            'roleSort'=> input('roleSort',0),
            'status'=> input('status',0),
            'remark'=> input('remark',''),
        ];
        $menuIds = input('menuIds/a',[]);

        $role = RoleModel::create($data);

        RoleMenuModel::setRoleMenus($role->role_id,$menuIds);

    	$this->success();
    }

    /**
     * 修改保存角色
     */
    #[Route('PUT','$')]
    #[PreAuthorize('hasPermi','system:role:edit')]
    public function edit(){
        $roleId = input('roleId',0);
        $data = [
            'deptCheckStrictly'=> input('deptCheckStrictly',true)?1:0,
            'menuCheckStrictly'=> input('menuCheckStrictly',true)?1:0,
            'roleName'=> input('roleName',''),
            'roleKey'=> input('roleKey',''),
            'roleSort'=> input('roleSort',0),
            'status'=> input('status',0),
            'remark'=> input('remark',''),
        ];
        $menuIds = input('menuIds/a',[]);

        $role = RoleModel::find($roleId);
        $role->save($data);

        RoleMenuModel::setRoleMenus($role->role_id,$menuIds);
    	$this->success();
    }

    /**
     * 修改保存数据权限
     */
    #[Route('PUT','dataScope')]
    #[PreAuthorize('hasPermi','system:role:edit')]
    public function dataScope(){
        // TODO:
        $this->success();
    }

    #[Route('GET','$')]
    public function index(){
        if (Request::has('roleId')) {
            $roleId = input('roleId',0);
            $role = RoleModel::find($roleId);
            $r = $role->save(['dataScope'=>input('dataScope','1')]);
            if (!$r) {
                $this->error();
            }
            RoleDeptModel::setRoleDepts($id,input('deptIds/a',[]));
        }
        $this->success($r);
    }

    /**
     * 状态修改
     */
    #[Route('PUT','changeStatus')]
    #[PreAuthorize('hasPermi','system:role:edit')]
    public function changeStatus(){
    	$roleId = input('roleId',0);
    	$status = input('status',1);

    	$role = RoleModel::find($roleId);
    	if (!$role) {
    		$this->error('角色不存在');
    	}
    	$r = $role->save(['status'=>$status]);
    	if (!$r) {
    		$this->error('操作失败');
    	}
    	$this->success();
    }

    /**
     * 删除角色
     */
    #[Route('DELETE',':roleIds')]
    #[PreAuthorize('hasPermi','system:role:remove')]
    public function remove(string $roleIds)
    {
        $roleIds = explode(',',$roleIds);
        $data = $this->model->select($roleIds);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->delete();
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    #[Route('GET','deptTree/:roleId')]
    #[PreAuthorize('hasPermi','system:role:query')]
    public function deptTree($roleId){
        $role = RoleModel::find($roleId);
        $r = [
            'checkedKeys'=>$role->dept->filter(fn($x)=>!count($x->children))->column('dept_id'),
            'depts'=>(new DeptModel)->tree(),
        ];
        $this->success($r);
    }
}
