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
        
        // 状态筛选
        $status && $m = $m->where('status',$status);
        
        // 管理员ID筛选
        $adminId && $m = $m->where('admin_id',$adminId);
        
        // 创建用户筛选
        $createUser && $m = $m->where('create_user','like','%'.$createUser.'%');
        
        // 金额范围筛选
        $minMoney && $m = $m->where('money','>=',$minMoney);
        $maxMoney && $m = $m->where('money','<=',$maxMoney);
        
        // 时间范围筛选
        isset($params) && $params && $m = $m->whereBetweenTime('create_time',$params['beginTime'],$params['endTime']);
        
        $total = $m->count();
        if ($pageNo??0 && $pageSize??0) {
            $m = $m->page($pageNo)->limit($pageSize);
        }
        $rows = $m->select()->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

    /**
     * 获取提现统计信息
     */
    public static function getStatistics($where=[])
    {
        extract($where);
        
        $m = self::where('status','1'); // 只统计审核通过的提现
        
        // 时间范围筛选
        isset($params) && $params && $m = $m->whereBetweenTime('create_time',$params['beginTime'],$params['endTime']);
        
        $totalAmount = $m->sum('money');
        $totalCount = $m->count();
        $avgAmount = $totalCount > 0 ? $totalAmount / $totalCount : 0;
        
        // 按提现类型统计
        $bankCount = self::where('status','1')->where('withdraw_status',1)->count();
        $usdtCount = self::where('status','1')->where('withdraw_status',2)->count();
        
        return [
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'avg_amount' => round($avgAmount, 2),
            'bank_count' => $bankCount,
            'usdt_count' => $usdtCount
        ];
    }

    /**
     * 获取待审核提现数量
     */
    public static function getPendingCount()
    {
        return self::where('status', 0)->count();
    }

    /**
     * 获取各状态提现数量
     */
    public static function getStatusCounts()
    {
        return [
            'pending' => self::where('status', 0)->count(),      // 审核中
            'approved' => self::where('status', 1)->count(),     // 审核通过
            'rejected' => self::where('status', 2)->count(),     // 拒绝
            'pending_payment' => self::where('status', 3)->count() // 待付款
        ];
    }
}
