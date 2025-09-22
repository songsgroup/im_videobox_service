<?php
declare (strict_types = 1);

namespace app\model\imext;

/**
 * RecorderModel - 资金流向记录管理
 */
class RecorderModel extends \app\BaseModel
{
    // 表名
    protected $name = 'imext_money_record';
    protected $pk = 'id';

    // 字段默认值
    protected $default = [
        'money' => 0,
        'money_front' => 0,
        'status' => 0
    ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('id','desc');
        
        // 用户ID筛选
        $userId && $m = $m->where('user_id','=',$userId);
        
        // 用户名筛选
        $nickName && $m = $m->where('nick_name','like','%'.$nickName.'%');
        
        // 类型筛选
        $type && $m = $m->where('type',$type);
        
        // 状态筛选
        isset($status) && $m = $m->where('status',$status);
        
        // 原始操作ID筛选
        $dataId && $m = $m->where('data_id','like','%'.$dataId.'%');
        
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
     * 根据用户ID获取资金记录
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
     * 根据类型获取记录
     */
    public static function getByType($type, $limit = 50)
    {
        return self::where('type', $type)
                   ->order('id','desc')
                   ->limit($limit)
                   ->select()
                   ->toArray();
    }

    /**
     * 根据原始操作ID获取记录
     */
    public static function getByDataId($dataId)
    {
        return self::where('data_id', $dataId)->select()->toArray();
    }

    /**
     * 获取资金流向统计
     */
    public static function getStatistics($where=[])
    {
        extract($where);
        
        $m = self::where('status', 0); // 只统计成功的记录
        
        // 时间范围筛选
        isset($params) && $params && $m = $m->whereBetweenTime('create_time',$params['beginTime'],$params['endTime']);
        
        $totalAmount = $m->sum('money');
        $totalCount = $m->count();
        $avgAmount = $totalCount > 0 ? $totalAmount / $totalCount : 0;
        
        // 按类型统计
        $typeStats = [];
        $types = self::distinct(true)->column('type');
        foreach ($types as $type) {
            $typeStats[$type] = [
                'count' => self::where('type', $type)->where('status', 0)->count(),
                'amount' => self::where('type', $type)->where('status', 0)->sum('money')
            ];
        }
        
        return [
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'avg_amount' => round($avgAmount, 2),
            'type_statistics' => $typeStats
        ];
    }

    /**
     * 获取用户资金流水
     */
    public static function getUserFlow($userId, $where=[])
    {
        extract($where);
        
        $m = self::where('user_id', $userId)->order('id','desc');
        
        // 类型筛选
        $type && $m = $m->where('type',$type);
        
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
     * 创建资金记录
     */
    public static function createRecord($data)
    {
        $recordData = [
            'user_id' => $data['user_id'] ?? '',
            'nick_name' => $data['nick_name'] ?? '',
            'money' => $data['money'] ?? 0,
            'money_front' => $data['money_front'] ?? 0,
            'type' => $data['type'] ?? '',
            'status' => $data['status'] ?? 0,
            'data_id' => $data['data_id'] ?? '',
            'create_time' => date('Y-m-d H:i:s'),
            'remark' => $data['remark'] ?? ''
        ];
        
        return self::create($recordData);
    }

    /**
     * 批量创建记录
     */
    public static function batchCreate($records)
    {
        $data = [];
        $now = date('Y-m-d H:i:s');
        
        foreach ($records as $record) {
            $data[] = [
                'user_id' => $record['user_id'] ?? '',
                'nick_name' => $record['nick_name'] ?? '',
                'money' => $record['money'] ?? 0,
                'money_front' => $record['money_front'] ?? 0,
                'type' => $record['type'] ?? '',
                'status' => $record['status'] ?? 0,
                'data_id' => $record['data_id'] ?? '',
                'create_time' => $now,
                'remark' => $record['remark'] ?? ''
            ];
        }
        
        return self::insertAll($data);
    }
}
