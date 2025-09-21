<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * OperLogModel
 */
class OperLogModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_oper_log';
    protected $pk = 'oper_id';

    protected $mapping = [
// oper_id
// title
// business_type
// method
// request_method
// operator_type
// oper_name
// dept_name
// oper_url
// oper_ip
// oper_location
// oper_param
// json_result
// status
// error_msg
// oper_time
// cost_time
        ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order($orderByColumn,$isAsc?'asc':'desc');
        $title && $m = $m->where('title','like','%'.$title.'%');
        $operName && $m = $m->where('oper_name','like','%'.$operName.'%');
        $businessType && $m = $m->where('business_type',$businessType);
        $status!==null && $m = $m->where('status',$status);
        $params && $m = $m->whereBetweenTime ('create_time',$params['beginTime'],$params['endTime']);
        $total = $m->count();
        $rows = $m->page($pageNum)
                    ->limit($pageSize)
                    ->select()
                    ->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

}
