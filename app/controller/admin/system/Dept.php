<?php
namespace app\controller\admin\system;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use think\facade\Cache;
use app\model\admin\DeptModel;
use think\facade\Request;

/**
 * Dept
 */
#[Group('admin/system/dept')]
class Dept extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new DeptModel;
    }
    
    /**
     * 获取部门列表
     */
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','system:dept:list')]
    public function list()
    {
        $where = [];
        $where['deptName'] = input('deptName','');
        $where['status'] = input('status',null);

        $depts = DeptModel::search($where);

    	$r = [
    		'data' => $depts,
    	];
        $this->success($r);
    }

    /**
     * 查询部门列表（排除节点）
     */
    #[Route('GET','list/exclude/:deptId')]
    #[PreAuthorize('hasPermi','system:dept:list')]
    public function excludeChild(int $deptId)
    {
        $depts = DeptModel::search([]);
        $depts = array_filter($depts,function($d){
            return !( $d['deptId']==$deptId || in_array($deptId,explode(',',$d['ancestors'])) );
        });

        $r = [
            'data' => $depts,
        ];
        $this->success($r);
    }

    /**
     * 根据部门编号获取详细信息
     */
    #[Route('GET',':deptId')]
    #[PreAuthorize('hasPermi','system:dept:query')]
    public function getInfo($deptId)
    {
        $r = [
            'data' => $this->model->find($deptId)
        ];
        $this->success($r);
    }

    /**
     * 新增部门
     */
    #[Route('POST','$')]
    #[PreAuthorize('hasPermi','system:dept:add')]
    public function add()
    {
        $data=$this->request->param();

        $r = $this->model->create($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success();
    }

    /**
     * 修改部门
     */
    #[Route('PUT','$')]
    #[PreAuthorize('hasPermi','system:dept:edit')]
    public function edit()
    {
        $id = input('deptId/d',0);

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
     * 删除部门
     */
    #[Route('DELETE',':deptId')]
    #[PreAuthorize('hasPermi','system:dept:remove')]
    public function remove(int $deptId)
    {
        $data = $this->model->select($deptId);
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
