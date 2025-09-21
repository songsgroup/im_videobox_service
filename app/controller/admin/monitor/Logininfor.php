<?php
namespace app\controller\admin\monitor;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use app\model\admin\LogininforModel;

/**
 * Logininfor
 */
#[Group('admin/monitor/logininfor')]
class Logininfor extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new LogininforModel;
    }
    
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','monitor:logininfor:list')]
    public function list()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['ipaddr'] = input('ipaddr','');
        $where['userName'] = input('userName','');
        $where['status'] = input('status',null);
        $where['params'] = input('params/a',[]);

        $r = LogininforModel::search($where);

        $this->success($r);
    }

    #[Route('POST','export')]
    #[PreAuthorize('hasPermi','monitor:logininfor:export')]
    public function export()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['ipaddr'] = input('ipaddr','');
        $where['userName'] = input('userName','');
        $where['status'] = input('status',null);
        $where['params'] = input('params/a',[]);

        $r = LogininforModel::search($where);
        if (!$r) {
            $this->error();
        }
        // util.exportExcel($r, "登录日志");
        // TODO:
    }

    #[Route('DELETE',':infoIds')]
    #[PreAuthorize('hasPermi','monitor:logininfor:remove')]
    public function remove($infoIds)
    {
        $infoIds = explode(',',$infoIds);
        $r = $this->model->select($infoId)->delete();
        if (!$r) {
            $this->error();
        }
        $this->success();
    }

    #[Route('DELETE','clean')]
    #[PreAuthorize('hasPermi','monitor:logininfor:remove')]
    public function clean()
    {
        $r = $this->model->select()->delete();
        if (!$r) {
            $this->error();
        }
        $this->success();
    }

    #[Route('GET','unlock/:userName')]
    #[PreAuthorize('hasPermi','monitor:logininfor:unlock')]
    public function unlock($userName)
    {
        // clearLoginRecordCache
        // TODO:
        $this->success();
    }
}
