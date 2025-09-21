<?php
declare (strict_types = 1);

namespace app\model\imext;

/**
 * ImGroupConfigModel
 */
class ImGroupConfigModel extends \app\BaseModel
{
    // 表名
    protected $name = 'imext_group_config';
    protected $pk = 'id';

    // 字段默认值
    protected $default = [
        'group_type' => '1'
    ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('id','desc');
        
 
        $total = $m->count();
        if ($pageNo??0 && $pageSize??0) {
            $m = $m->page($pageNo)->limit($pageSize);
        }
        $rows = $m->select()->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

    /**
     * 根据群编号获取配置
     */
    public static function getByGroupId($groupId)
    {
        return self::where('group_id', $groupId)->find();
    }

    /**
     * 根据用户ID获取群配置列表
     */
    public static function getByUserId($userId)
    {
        return self::where('user_id', $userId)->select()->toArray();
    }

    /**
     * 获取群类型统计
     */
    public static function getGroupTypeStatistics()
    {
        $type1 = self::where('group_type', '1')->count();
        $type2 = self::where('group_type', '2')->count();
        $type3 = self::where('group_type', '3')->count();
        
        return [
            'type1_count' => $type1,
            'type2_count' => $type2,
            'type3_count' => $type3,
            'total_count' => $type1 + $type2 + $type3
        ];
    }

    /**
     * 检查群编号是否已存在
     */
    public static function checkGroupIdExists($groupId, $excludeId = null)
    {
        $m = self::where('group_id', $groupId);
        if ($excludeId) {
            $m = $m->where('id', '<>', $excludeId);
        }
        return $m->count() > 0;
    }

    /**
     * 批量删除群配置
     */
    public static function batchDelete($ids)
    {
        return self::whereIn('id', $ids)->delete();
    }
}
