<?php
namespace app\controller\admin\system;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use think\facade\Cache;
use app\model\admin\RoleModel;
use app\model\admin\MenuModel;

/**
 * Menu
 */
#[Group('admin/system/menu')]
class Menu extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize(){
        parent::initialize();
        $this->model = new MenuModel;
    }
    
    /**
     * 获取菜单列表
     */
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','system:menu:list')]
    public function list(){
        $where = [];
        $where['menuName'] = input('menuName','');
        $where['status'] = input('status',null);

        $m = MenuModel::order('parent_id,menu_id');
        $where['menuName'] && $m = $m->whereLike('menu_name','%'.$where['menuName'].'%');
        $where['status']!==null && $m = $m->where('status',$where['status']);
        $r = [
            'data'=>$m->select()
        ];
        $this->success($r);
    }

    /**
     * 根据菜单编号获取详细信息
     */
    #[Route('GET','<id>')]
    #[PreAuthorize('hasPermi','system:menu:query')]
    public function getInfo($id){
        $r = [
            'data' => $this->model->find($id)
        ];
        $r['data']['isFrame'] = strval($r['data']['isFrame']);
        $this->success($r);
    }

    /**
     * 获取菜单下拉树列表
     * 
     * @method (GET)
     */
    #[Route('GET','treeselect')]
    public function treeselect(){
        $r = [
            'data'=>MenuModel::getMenusTree(),
        ];
        $this->success($r);
    }

    /**
     * 加载对应角色菜单列表树
     */
    #[Route('GET','roleMenuTreeselect/:roleId')]
    public function roleMenuTreeselect($roleId){
        $role = RoleModel::find($roleId);
        $r = [
            'checkedKeys'=>array_column($role->menu->toArray(),'menuId'),
            'menus'=>MenuModel::getMenusTree(),
        ];
        $this->success($r);
    }

    /**
     * 新增菜单
     */
    #[Route('POST','$')]
    public function add(){
        $data = $this->request->param();

        $r = $this->model->create($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success();
    }

    /**
     * 修改菜单
     */
    #[Route('PUT','$')]
    public function edit(){
        $id = input('menuId/d',0);
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->save($this->request->param());
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除菜单
     */
    #[Route('DELETE','<ids>')]
    public function remove($ids){
        $ids = explode(',',$ids);
        $data = $this->model->select($ids);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->delete();
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }
}
