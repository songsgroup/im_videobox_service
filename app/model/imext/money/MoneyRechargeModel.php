<?php
declare (strict_types = 1);

namespace app\model\imext\money;

/**
 * ConfigModel
 */
class MoneyRechargeModel extends \app\BaseModel
{
    // 表名
    protected $name = 'imext_recharge';
    protected $pk = 'id';

    // 字段默认值·
  

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('id','asc');
        $userId && $m = $m->where('user_id','like','%'.$userId.'%');
        $total = $m->count();
        if ($pageNo??0 && $pageSize??0) {
            $m = $m->page($pageNo)->limit($pageSize);
        }
        $rows = $m->select()->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

}
