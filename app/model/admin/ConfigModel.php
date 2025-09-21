<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * ConfigModel
 */
class ConfigModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_config';
    protected $pk = 'config_id';

    // 字段默认值
    protected $default = [
            'config_id'   => null,
            'config_name' => null,
            'config_key'  => null,
            'config_value'=> null,
            'config_type' => null,
            'create_by'   => null,
            'create_time' => null,
            'update_by'   => null,
            'update_time' => null,
            'remark'      => null,
        ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('config_id','asc');
        $configName && $m = $m->where('config_name','like','%'.$configName.'%');
        $configKey && $m = $m->where('config_key','like','%'.$configKey.'%');
        $configType && $m = $m->where('config_type',$configType);
        isset($params) && $params && $m = $m->whereBetweenTime ('create_time',$params['beginTime'],$params['endTime']);
        $total = $m->count();
        if ($pageNum??0 && $pageSize??0) {
            $m = $m->page($pageNum)->limit($pageSize);
        }
        $rows = $m->select()->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

}
