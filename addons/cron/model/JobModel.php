<?php
declare (strict_types = 1);

namespace addons\cron\model;

/**
 * JobModel
 */
class JobModel extends \app\BaseModel
{
    // 表名
    protected $name = 'job';
    protected $pk = 'job_id';

    protected $default = [
        'job_id' => null,
        'job_name' => '',
        'job_group' => '',
        'invoke_target' => '',
        'cron_expression' => '',
        'misfire_policy' => '',
        'concurrent' => '',
        'status' => 0,
        'create_by' => '',
        'update_by' => '',
        'remark' => '',
    ];

    public static function search($where=[]){
        extract($where);

        $m = self::order('job_id','desc');
        $jobName && $m = $m->where('job_name','like','%'.$jobName.'%');
        $jobGroup && $m = $m->where('job_group',$jobGroup);
        $status!==null && $m = $m->where('status',$status);
        $total = $m->count();
        $rows = $m->page($pageNum)
                    ->limit($pageSize)
                    ->select()
                    ->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

}
