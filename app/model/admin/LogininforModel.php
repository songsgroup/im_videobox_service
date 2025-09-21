<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * LogininforModel
 */
class LogininforModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_logininfor';
    protected $pk = 'info_id';

    protected $mapping = [
// info_id
// user_name
// ipaddr
// login_location
// browser
// os
// status
// msg
// login_time
        ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('login_time','desc');
        $userName && $m = $m->where('user_name','like','%'.$userName.'%');
        $ipaddr && $m = $m->where('ipaddr',$ipaddr);
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
