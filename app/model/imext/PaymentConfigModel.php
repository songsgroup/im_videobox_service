<?php
declare (strict_types = 1);

namespace app\model\imext;

/**
 * PaymentConfigModel
 */
class PaymentConfigModel extends \app\BaseModel
{
    // 表名
    protected $name = 'imext_payment_config';
    protected $pk = 'id';

    // 字段默认值
    protected $default = [
        'enabled' => 1,
        'ordering' => 0
    ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('ordering','asc')->order('id','desc');
        
        // 标题筛选
        $title && $m = $m->where('title','like','%'.$title.'%');
        
        // 代码筛选
        $code && $m = $m->where('code','like','%'.$code.'%');
        
        // 可用状态筛选
        isset($enabled) && $m = $m->where('enabled',$enabled);
        
        $total = $m->count();
        if ($pageNo??0 && $pageSize??0) {
            $m = $m->page($pageNo)->limit($pageSize);
        }
        $rows = $m->select()->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

    /**
     * 获取启用的支付配置列表
     */
    public static function getEnabledList()
    {
        return self::where('enabled', 1)
                   ->order('ordering','asc')
                   ->order('id','desc')
                   ->select()
                   ->toArray();
    }

    /**
     * 根据代码获取配置
     */
    public static function getByCode($code)
    {
        return self::where('code', $code)->where('enabled', 1)->find();
    }

    /**
     * 获取支付配置统计
     */
    public static function getStatistics()
    {
        $total = self::count();
        $enabled = self::where('enabled', 1)->count();
        $disabled = self::where('enabled', 0)->count();
        
        return [
            'total_count' => $total,
            'enabled_count' => $enabled,
            'disabled_count' => $disabled
        ];
    }

    /**
     * 检查代码是否已存在
     */
    public static function checkCodeExists($code, $excludeId = null)
    {
        $m = self::where('code', $code);
        if ($excludeId) {
            $m = $m->where('id', '<>', $excludeId);
        }
        return $m->count() > 0;
    }

    /**
     * 批量更新排序
     */
    public static function batchUpdateOrdering($data)
    {
        $result = 0;
        foreach ($data as $item) {
            if (isset($item['id']) && isset($item['ordering'])) {
                $result += self::where('id', $item['id'])->update(['ordering' => $item['ordering']]);
            }
        }
        return $result;
    }

    /**
     * 批量更新状态
     */
    public static function batchUpdateStatus($ids, $enabled)
    {
        return self::whereIn('id', $ids)->update(['enabled' => $enabled]);
    }

    /**
     * 获取最大排序值
     */
    public static function getMaxOrdering()
    {
        $max = self::max('ordering');
        return $max ? $max + 1 : 1;
    }
}
