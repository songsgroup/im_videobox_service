<?php
namespace app\controller\admin\system\dict;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use app\model\admin\DictDataModel;

/**
 * Data
 */
#[Group('admin/system/dict/data')]
class Data extends \app\BaseController
{
    protected $noNeedLogin = ['index'];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new DictDataModel;
    }
    
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','system:dict:list')]
    public function list()
    {
    	$where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['dictType'] = input('dictType','');
    	$where['dictLabel'] = input('dictLabel','');
    	$where['status'] = input('status',null);

        $r = DictDataModel::search($where);

        $this->success($r);
    }

    #[Route('POST','export')]
    #[PreAuthorize('hasPermi','system:dict:export')]
    public function export()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['dictType'] = input('dictType','');
        $where['dictLabel'] = input('dictLabel','');
        $where['status'] = input('status',null);

        $r = DictDataModel::search($where);

        // util.exportExcel($r, "字典数据");
        // TODO:
    }

    #[Route('GET','<id>')]
    #[PreAuthorize('hasPermi','system:dict:query')]
    public function getInfo(int $id)
    {

        $r = DictDataModel::find($id)->toArray();

        $this->success(['data'=>$r]);
    }

    #[Route('GET','/type/:dictType')]
    public function dictType(string $dictType)
    {

        $r = DictDataModel::where('dict_type',$dictType)->select()->toArray();

        $this->success(['data'=>$r]);
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
     * 修改保存字典类型
     */
    #[Route('PUT','$')]
    #[PreAuthorize('hasPermi','system:dict:edit')]
    public function edit()
    {
        $id = input('dictCodes/d',0);

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
    #[Route('DELETE',':dictCodes')]
    #[PreAuthorize('hasPermi','system:dict:remove')]
    public function remove(int $dictCodes)
    {
        $data = $this->model->select($dictCodes);
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
