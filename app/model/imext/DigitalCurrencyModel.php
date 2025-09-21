<?php
declare (strict_types = 1);

namespace app\model\imext;

/**
 * DigitalCurrencyModel - 数字币管理
 */
class DigitalCurrencyModel extends \app\BaseModel
{
    // 表名
    protected $name = 'imext_digital_currency';
    protected $pk = 'id';

    // 字段默认值
    protected $default = [
        'status' => 0
    ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('id','desc');
        
        // 用户ID筛选
        $userId && $m = $m->where('user_id','like','%'.$userId.'%');
        
        // 类型筛选
        $type && $m = $m->where('type',$type);
        
        // 地址筛选
        $address && $m = $m->where('address','like','%'.$address.'%');
        
        // 状态筛选
        isset($status) && $m = $m->where('status',$status);
        
        // 创建用户筛选
        $createUser && $m = $m->where('create_user','like','%'.$createUser.'%');
        
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
     * 根据用户ID获取数字币记录
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
     * 根据地址获取记录
     */
    public static function getByAddress($address)
    {
        return self::where('address', $address)->select()->toArray();
    }

    /**
     * 获取数字币统计
     */
    public static function getStatistics($where=[])
    {
        extract($where);
        
        $m = self::where('status', 1); // 只统计审核通过的记录
        
        // 时间范围筛选
        isset($params) && $params && $m = $m->whereBetweenTime('create_time',$params['beginTime'],$params['endTime']);
        
        $totalCount = $m->count();
        
        // 按类型统计
        $typeStats = [];
        $types = self::distinct(true)->column('type');
        foreach ($types as $type) {
            $typeStats[$type] = [
                'total' => self::where('type', $type)->count(),
                'approved' => self::where('type', $type)->where('status', 1)->count(),
                'pending' => self::where('type', $type)->where('status', 0)->count()
            ];
        }
        
        // 按状态统计
        $statusStats = [
            'pending' => self::where('status', 0)->count(),      // 审核中
            'approved' => self::where('status', 1)->count()      // 审核通过
        ];
        
        return [
            'total_count' => $totalCount,
            'type_statistics' => $typeStats,
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
     * 创建数字币记录
     */
    public static function createRecord($data)
    {
        $recordData = [
            'user_id' => $data['user_id'] ?? '',
            'type' => $data['type'] ?? '',
            'address' => $data['address'] ?? '',
            'status' => $data['status'] ?? 0,
            'create_user' => $data['create_user'] ?? 'system',
            'create_time' => date('Y-m-d H:i:s'),
            'mark' => $data['mark'] ?? ''
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
                'type' => $record['type'] ?? '',
                'address' => $record['address'] ?? '',
                'status' => $record['status'] ?? 0,
                'create_user' => $record['create_user'] ?? 'system',
                'create_time' => $now,
                'mark' => $record['mark'] ?? ''
            ];
        }
        
        return self::insertAll($data);
    }

    /**
     * 批量更新状态
     */
    public static function batchUpdateStatus($ids, $status)
    {
        return self::whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * 检查地址是否已存在
     */
    public static function checkAddressExists($address, $excludeId = null)
    {
        $m = self::where('address', $address);
        if ($excludeId) {
            $m = $m->where('id', '<>', $excludeId);
        }
        return $m->count() > 0;
    }

    /**
     * 获取用户有效的数字币地址
     */
    public static function getUserValidAddresses($userId)
    {
        return self::where('user_id', $userId)
                   ->where('status', 1)
                   ->order('id','desc')
                   ->select()
                   ->toArray();
    }

    /**
     * 获取所有数字币类型
     */
    public static function getAllTypes()
    {
        return self::distinct(true)->column('type');
    }
}
