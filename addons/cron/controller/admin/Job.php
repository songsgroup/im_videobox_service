<?php
declare (strict_types = 1);

namespace addons\cron\controller\admin;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use addons\cron\model\JobModel;

use think\facade\Console;

/**
 * Job
 */
class Job extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize(){
        parent::initialize();
        $this->model = new JobModel;
    }

    #[PreAuthorize('hasPermi','monitor:job:query')]
    public function list(){
    	$where = [];
    	$where['pageNum'] = input('pageNum/d',1);
    	$where['pageSize'] = input('pageSize/d',10);
    	$where['jobName'] = input('jobName','');
    	$where['jobGroup'] = input('jobGroup','');
    	$where['status'] = input('status',null);
    	
        $r = JobModel::search($where);
        $this->success($r);
    }

    #[PreAuthorize('hasPermi','monitor:job:add')]
    public function add(){
        $data = [
            'jobName'       => input('jobName',''),
            'jobGroup'      => input('jobGroup',''),
            'invokeTarget'  => htmlspecialchars_decode(input('invokeTarget','')),
            'cronExpression'=> input('cronExpression',''),
            'misfirePolicy' => input('misfirePolicy/d',1),
            'concurrent'    => input('concurrent/d',1),
            'remark'        => input('remark',''),
            'status'        => input('status/d',1),
        ];

        $r = $this->model->save($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success();
    }

    #[PreAuthorize('hasPermi','monitor:job:remove')]
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

    #[PreAuthorize('hasPermi','monitor:job:edit')]
    public function edit(){
        $job = [
            'jobId'         => input('jobId/d',0),
            'jobName'       => input('jobName',''),
            'jobGroup'      => input('jobGroup',''),
            'invokeTarget'  => htmlspecialchars_decode(input('invokeTarget','')),
            'cronExpression'=> input('cronExpression',''),
            'misfirePolicy' => input('misfirePolicy/d',1),
            'concurrent'    => input('concurrent/d',1),
            'remark'        => input('remark',''),
            'status'        => input('status/d',1),
        ];
        $id = $job['jobId'];
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->save($job);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    #[PreAuthorize('hasPermi','monitor:job:query')]
    public function getInfo($id){
        $data = $this->model->find($id)->toArray();
        $ce = new \Cron\CronExpression(implode(' ',array_slice(explode(' ',$data['cronExpression']),1)));
        $data['nextValidTime'] = $ce->getNextRunDate()->getTimestamp();
        $r = [
            'data' => $data
        ];
        $this->success($r);
    }

    #[PreAuthorize('hasPermi','monitor:job:edit')]
    protected function changeStatus(){
        $id = input('jobId/d',0);
        $status = input('status/d',0);

        $data = $this->model->find($id);
        $data->status = $status;
        $r = $data->save();
        if (!$r) {
            $this->error('操作失败');
        }

        $this->success();
    }

    #[PreAuthorize('hasPermi','monitor:job:run')]
    public function run(){
        $jobId = input('jobId','');
        $jobGroup = input('jobGroup','DEFAULT');

        $output = Console::call('cron', ['--jobId='.$jobId]);
        $this->success(null,$output->fetch());
    }
}
