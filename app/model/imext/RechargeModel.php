<?php
declare (strict_types = 1);

namespace app\model\imext;

/**
 * RechargeModel
 */
class RechargeModel extends \app\BaseModel
{
    // 表名
    protected $name = 'imext_recharge';
    protected $pk = 'id';

    // 字段默认值
    protected $default = [
        'type' => '0',
        'source' => '0',
        'money' => 0,
        'money_front' => 0,
        'select_price' => 0,
        'ip' => 0,
        'is_processed' => 0,
        'status' => '0',
        'admin_id' => 0
    ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('id','desc');
        
        // 用户ID筛选
        $userId && $m = $m->where('user_id','like','%'.$userId.'%');
        
        // 类型筛选
        $type && $m = $m->where('type',$type);
        
        // 来源筛选
        $source && $m = $m->where('source',$source);
        
        // 状态筛选
        $status && $m = $m->where('status',$status);
        
        // 是否处理筛选
        isset($isProcessed) && $m = $m->where('is_processed',$isProcessed);
        
        // 管理员ID筛选
        $adminId && $m = $m->where('admin_id',$adminId);
        
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
     * 获取充值统计信息
     */
    public static function getStatistics($where=[])
    {
        extract($where);
        
        $m = self::where('status','1'); // 只统计成功的充值
        
        // 时间范围筛选
        isset($params) && $params && $m = $m->whereBetweenTime('create_time',$params['beginTime'],$params['endTime']);
        
        $totalAmount = $m->sum('money');
        $totalCount = $m->count();
        $avgAmount = $totalCount > 0 ? $totalAmount / $totalCount : 0;
        
        return [
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'avg_amount' => round($avgAmount, 2)
        ];
    }
}
