<?php
declare (strict_types = 1);

namespace app\model\imext;

/**
 * UserBankModel - 银行卡管理
 */
class UserBankModel extends \app\BaseModel
{
    // 表名
    protected $name = 'imext_user_bank';
    protected $pk = 'id';

    // 字段默认值
    protected $default = [
        'money' => 0,
        'local_money' => 0,
        'exchange_rate' => 0,
        'z_money' => 0,
        's_charge' => 0,
        'type' => 1,
        'status' => 0
    ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('id','desc');
        
        // 用户ID筛选
        $userId && $m = $m->where('user_id','like','%'.$userId.'%');
        
        // 用户姓名筛选
        $name && $m = $m->where('name','like','%'.$name.'%');
        
        // 银行卡ID筛选
        $cardId && $m = $m->where('card_id',$cardId);
        
        // 提现类型筛选
        $type && $m = $m->where('type',$type);
        
        // 状态筛选
        isset($status) && $m = $m->where('status',$status);
        
        // 提现单号筛选
        $orderId && $m = $m->where('order_id','like','%'.$orderId.'%');
        
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
     * 根据用户ID获取银行卡记录
     */
    public static function getByUserId($userId, $limit = 20)
    {
        return self::where('user_id', $userId)
                   ->order('id','desc')
                   ->limit($limit)
                   ->select()
                   ->toArray();
    }

    /**
     * 根据提现单号获取记录
     */
    public static function getByOrderId($orderId)
    {
        return self::where('order_id', $orderId)->find();
    }

    /**
     * 根据银行卡ID获取记录
     */
    public static function getByCardId($cardId)
    {
        return self::where('card_id', $cardId)->select()->toArray();
    }

    /**
     * 获取银行卡统计
     */
    public static function getStatistics($where=[])
    {
        extract($where);
        
        $m = self::where('status', 1); // 只统计审核通过的记录
        
        // 时间范围筛选
        isset($params) && $params && $m = $m->whereBetweenTime('create_time',$params['beginTime'],$params['endTime']);
        
        $totalAmount = $m->sum('money');
        $totalCount = $m->count();
        $avgAmount = $totalCount > 0 ? $totalAmount / $totalCount : 0;
        
        // 按类型统计
        $bankCount = self::where('status', 1)->where('type', 1)->count();
        $usdtCount = self::where('status', 1)->where('type', 2)->count();
        
        // 按状态统计
        $statusStats = [
            'pending' => self::where('status', 0)->count(),      // 审核中
            'approved' => self::where('status', 1)->count(),     // 审核通过
            'rejected' => self::where('status', 2)->count(),     // 拒绝
            'pending_payment' => self::where('status', 3)->count(), // 待付款
            'completed' => self::where('status', 4)->count()     // 付款完成
        ];
        
        return [
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'avg_amount' => round($avgAmount, 2),
            'bank_count' => $bankCount,
            'usdt_count' => $usdtCount,
            'status_statistics' => $statusStats
        ];
    }

    /**
     * 获取待审核数量
     */
    public static function getPendingCount()
    {
        return self::where('status', 0)->count();
    }

    /**
     * 创建银行卡记录
     */
    public static function createRecord($data)
    {
        $recordData = [
            'user_id' => $data['user_id'] ?? '',
            'name' => $data['name'] ?? '',
            'card_id' => $data['card_id'] ?? 0,
            'usdt_add' => $data['usdt_add'] ?? '',
            'money' => $data['money'] ?? 0,
            'local_money' => $data['local_money'] ?? 0,
            'exchange_rate' => $data['exchange_rate'] ?? 0,
            'z_money' => $data['z_money'] ?? 0,
            's_charge' => $data['s_charge'] ?? 0,
            'refuse' => $data['refuse'] ?? '',
            'type' => $data['type'] ?? 1,
            'order_id' => $data['order_id'] ?? '',
            'status' => $data['status'] ?? 0,
            'create_user' => $data['create_user'] ?? 'system',
            'create_time' => date('Y-m-d H:i:s'),
            'mark' => $data['mark'] ?? ''
        ];
        
        return self::create($recordData);
    }

    /**
     * 批量更新状态
     */
    public static function batchUpdateStatus($ids, $status)
    {
        return self::whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * 生成提现单号
     */
    public static function generateOrderId()
    {
        return 'WD' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * 检查提现单号是否存在
     */
    public static function checkOrderIdExists($orderId)
    {
        return self::where('order_id', $orderId)->count() > 0;
    }
}