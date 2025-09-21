<?php
declare (strict_types = 1);

namespace addons\cron\controller\admin;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use addons\cron\model\JobLogModel;

/**
 * JobLogController
 *
 * @author 心衍
 * @version 2024-05-02 17:45:50
 */
class JobLog extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize(){
        parent::initialize();
        $this->model = new JobLogModel;
    }

    #[PreAuthorize('hasPermi','monitor:jobLog:add')]
    public function add(){
        $data = $this->request->param();

        $r = $this->model->create($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success();
    }

    #[PreAuthorize('hasPermi','monitor:jobLog:remove')]
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

    #[PreAuthorize('hasPermi','monitor:jobLog:remove')]
    public function clean()
    {
        $r = $this->model->where(true)->delete();
        if (!$r) {
            $this->error();
        }
        $this->success();
    }

    #[PreAuthorize('hasPermi','monitor:jobLog:edit')]
    public function edit(){
        $jobLog = $this->request->param();
        $id = $jobLog['jobLogId'];
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->save($jobLog);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    #[PreAuthorize('hasPermi','monitor:jobLog:query')]
    public function getInfo($id){
        $r = [
            'data' => $this->model->find($id)
        ];
        $this->success($r);
    }

    #[PreAuthorize('hasPermi','monitor:jobLog:query')]
    public function list(){
        $where = [];
        $where['params'] = [];

        $where['jobName'] = input('jobName',null);
        $where['jobGroup'] = input('jobGroup',null);
        $where['status'] = input('status',null);
        $where['params']['beginTime'] = input('params.beginTime/s','');
        $where['params']['endTime'] = input('params.endTime/s','');

        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);

        $r = $this->model->search($where);
        $this->success($r);
    }

}

