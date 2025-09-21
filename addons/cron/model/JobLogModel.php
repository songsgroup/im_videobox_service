<?php
declare (strict_types = 1);

namespace addons\cron\model;

/**
 * JobLogModel
 *
 * @author 心衍
 * @version 2024-05-02 17:45:50
 */
class JobLogModel extends \app\BaseModel
{
    // 表名
    protected $name = 'job_log';
    protected $pk = 'job_log_id';

    protected $updateTime = false;

    protected $mapping = [
        //'job_log_id'=>'jobLogId',
        //'job_name'=>'jobName',
        //'job_group'=>'jobGroup',
        //'invoke_target'=>'invokeTarget',
        //'job_message'=>'jobMessage',
        //'status'=>'status',
        //'exception_info'=>'exceptionInfo',
        //'create_time'=>'createTime',
        ];
    
    // 字段默认值
    protected $default = [
            'job_log_id'=>null,
            'job_name'=>'',
            'job_group'=>'',
            'invoke_target'=>'',
            'job_message'=>'',
            'status'=>0,
            'exception_info'=>'',
        ];
        
    //search
    public static function search($where=[]){
        extract($where);

        $m = new self;
        if(isset($jobName) && $jobName!=null && trim($jobName)!='')
            $m = $m->where('job_name','like','%'.$jobName.'%');
        if(isset($jobGroup) && $jobGroup!=null && trim($jobGroup)!='')
            $m = $m->where('job_group','like','%'.$jobGroup.'%');
        if(isset($status) && $status!=null && trim($status)!='')
            $m = $m->where('status','status');
        if(isset($params) && $params['beginCreateTime'] && $params['endCreateTime'])
            $m = $m->whereBetweenTime('create_time',$params['beginCreateTime'],$params['endCreateTime']);

        $total = $m->count();
        if($pageNum??0 && $pageSize??0){
            $m = $m->page($pageNum)->limit($pageSize);
        }
        $rows = $m->select()->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }
}
