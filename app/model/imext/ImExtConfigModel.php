<?php
declare (strict_types = 1);

namespace app\model\imext;

/**
 * imext_config
 * 系统配置表
 */
class ImExtConfigModel extends \app\BaseModel
{
    // 表名
    protected $name = 'imext_config';
    protected $pk = 'id';

    // 字段默认值·
  

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('id','asc');
        $imId && $m = $m->where('user_id','like','%'.$imId.'%');
        
        if ($pageNo??0 && $pageSize??0) {
            $m = $m->page($pageNo)->limit($pageSize);
        }
        $rows = $m->select()->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

}
