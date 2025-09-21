<?php
namespace app\controller\admin\system\dict;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use app\model\admin\DictTypeModel;

/**
 * 数据字典信息
 */
#[Group('admin/system/dict/type')]
class Type extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new DictTypeModel;
    }

    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','system:dict:list')]
    public function list()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['dictName'] = input('dictName','');
        $where['dictType'] = input('dictType','');
        $where['status'] = input('status',null);
        $where['params'] = input('params/a',[]);

        $r = DictTypeModel::search($where);

        $this->success($r);
    }

    #[Route('POST','export')]
    #[PreAuthorize('hasPermi','system:dict:export')]
    public function export()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['dictName'] = input('dictName','');
        $where['dictType'] = input('dictType','');
        $where['status'] = input('status',null);
        $where['params'] = input('params/a',[]);

        $r = DictTypeModel::search($where);

        // util.exportExcel($r, "字典类型");
    }
    
    /**
     * getInfo
     *
     */
    #[Route('GET','<id>')]
    public function getInfo(int $id)
    {
        $r = [
            'data' => $this->model->find($id)
        ];
        $this->success($r);
    }

    /**
     * 新增字典类型
     */
    #[Route('POST','$')]
    #[PreAuthorize('hasPermi','system:dict:add')]
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
     * 修改字典类型
     */
    #[Route('PUT','$')]
    #[PreAuthorize('hasPermi','system:dict:edit')]
    public function edit()
    {
        $id = input('dictId/d',0);

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
     * 删除字典类型
     */
    #[Route('DELETE',':dictIds')]
    #[PreAuthorize('hasPermi','system:dict:remove')]
    public function remove(string $dictIds)
    {
        $dictIds = explode(',',$dictIds);
        $data = $this->model->select($dictIds);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->delete();
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除字典类型
     */
    #[Route('DELETE','refreshCache')]
    #[PreAuthorize('hasPermi','system:dict:remove')]
    public function refreshCache()
    {
        // TODO:
        $this->success();
    }

    /**
     * optionselect
     *
     * @method (GET)
     */
    #[Route('GET','optionselect')]
    public function optionselect()
    {
        $r = ['data'=>
            DictTypeModel::select()->toArray(),
        ];

        $this->success($r);
    }
}
