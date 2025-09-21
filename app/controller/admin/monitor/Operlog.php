<?php
namespace app\controller\admin\monitor;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use app\model\admin\OperLogModel;

/**
 * Operlog
 */
#[Group('admin/monitor/operlog')]
class Operlog extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new OperLogModel;
    }
    
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','monitor:operlog:list')]
    public function list()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['title'] = input('title','');
        $where['operName'] = input('operName','');
        $where['businessType'] = input('businessType',0);
        $where['status'] = input('status',null);
        $where['params'] = input('params/a',[]);
        $where['orderByColumn'] = input('orderByColumn','oper_id');
        $where['isAsc'] = input('isAsc','')!=='descending';

        $r = OperLogModel::search($where);

        $this->success($r);
    }

    #[Route('GET','export')]
    #[PreAuthorize('hasPermi','monitor:operlog:export')]
    public function export()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['title'] = input('title','');
        $where['operName'] = input('operName','');
        $where['businessType'] = input('businessType',0);
        $where['status'] = input('status',null);
        $where['params'] = input('params/a',[]);
        $where['orderByColumn'] = input('orderByColumn','oper_id');
        $where['isAsc'] = input('isAsc','')!=='descending';

        $r = OperLogModel::search($where);
        // TODO:
        // util.exportExcel($r, "操作日志");
    }

    #[Route('DELETE','clean')]
    #[PreAuthorize('hasPermi','monitor:operlog:remove')]
    public function clean()
    {
        $r = $this->model->where(true)->delete();
        if (!$r) {
            $this->error();
        }
        $this->success();
    }

    #[Route('DELETE',':operIds')]
    #[PreAuthorize('hasPermi','monitor:operlog:remove')]
    public function remove($operIds)
    {
        $operIds = explode(',',$operIds);
        $r = $this->model->select($operIds)->delete();
        if (!$r) {
            $this->error();
        }
        $this->success();
    }
}
