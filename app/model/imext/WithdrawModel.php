<?php
declare (strict_types = 1);

namespace app\model\imext;

/**
 * WithdrawModel
 */
class WithdrawModel extends \app\BaseModel
{
    // 表名
    protected $name = 'imext_withdraw';
    protected $pk = 'id';

    // 字段默认值
    protected $default = [
        'money' => 0,
        'local_money' => 0,
        'exchange_rate' => 0,
        'z_money' => 0,
        's_charge' => 0,
        'withdraw_status' => 1,
        'status' => 0,
        'admin_id' => 0
    ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('id','desc');
        
        // 用户ID筛选
        $userId && $m = $m->where('user_id','like','%'.$userId.'%');
        
        // 用户姓名筛选
        $name && $m = $m->where('name','like','%'.$name.'%');
        
        // 提现类型筛选
        $withdrawStatus && $m = $m->where('withdraw_status',$withdrawStatus);
        
       
        
        $total = $m->count();
        if ($pageNo??0 && $pageSize??0) {
            $m = $m->page($pageNo)->limit($pageSize);
        }
        $rows = $m->select()->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }
 
}
