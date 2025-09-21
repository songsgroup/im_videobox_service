<?php
declare (strict_types = 1);

namespace app\model\admin;

use think\annotation\model\relation\BelongsTo;

/**
 * DictDataModel
 */
#[BelongsTo("type", DictTypeModel::class, "dict_type",'dict_type')]
class DictDataModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_dict_data';
    protected $pk = 'dict_code';

    protected $mapping = [
// dict_code
// dict_sort
// dict_label
// dict_value
// dict_type
// css_class
// list_class
// is_default
// status
// create_by
// create_time
// update_by
// update_time
// remark
        ];

    protected $default = [
            'dict_code' => null,
            'dict_sort' => 0,
            'dict_label' => '',
            'dict_value' => '',
            'dict_type' => '',
            'css_class' => '',
            'list_class' => '',
            'is_default' => 'N',
            'status' => 0,
            'create_by' => '',
            // 'create_time' => null,
            'update_by' => '',
            // 'update_time' => null,
            'remark' => '',
        ];

    protected $append = [
            'default'
        ];
        
    public function getDefaultAttr()
    {
        return $this->is_default=='Y';
    }

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('dict_sort','asc');
        $dictLabel && $m = $m->where('dict_label','like','%'.$dictLabel.'%');
        $dictType && $m = $m->where('dict_type',$dictType);
        $status!==null && $m = $m->where('status',$status);
        $total = $m->count();
        $rows = $m->page($pageNum)
                    ->limit($pageSize)
                    ->select()
                    ->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

}
