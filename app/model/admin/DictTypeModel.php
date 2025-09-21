<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * DictTypeModel
 */
class DictTypeModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_dict_type';
    protected $pk = 'dict_id';

    protected $mapping = [
// dict_id
// dict_name
// dict_type
// status
// create_by
// create_time
// update_by
// update_time
// remark
        ];

    protected $default = [
            'dict_id' => null,
            'dict_name' => 0,
            'dict_type' => '',
            'status' => 0,
            'create_by' => '',
            // 'create_time' => null,
            'update_by' => '',
            // 'update_time' => null,
            'remark' => '',
        ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('dict_id','asc');
        $dictName && $m = $m->where('dict_name','like','%'.$dictName.'%');
        $dictType && $m = $m->where('dict_type',$dictType);
        $status!==null && $m = $m->where('status',$status);
        $params && $m = $m->whereBetweenTime ('create_time',$params['beginTime'],$params['endTime']);
        $total = $m->count();
        $rows = $m->page($pageNum)
                    ->limit($pageSize)
                    ->select()
                    ->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

    public static function add(array $data)
    {
        $type = [
            'dictName' => $data['dictName'],
            'dictType' => $data['dictType'],
            'remark' => $data['remark']??$data['dictName'],
        ];
        $data = $data['data']??[];
        $type = self::create($type);
        foreach ($data as $key=>$d) {
            DictDataModel::create([
                'dictLabel' => $d['dictLabel'],
                'dictValue' => $d['dictValue'],
                'remark'    => $d['remark']??$d['dictLabel'],
                'isDefault' => $d['isDefault']??'N',
                'cssClass'  => $d['cssClass']??'',
                'listClass' => $d['listClass']??'',
                'dictSort'  => $key+1,
                'dictType'  => $type['dictType'],
            ]);
        }
        return true;
    }

    public static function remove(string $type)
    {
        if (!$type) {
            throw new Exception("字典类型不能为空", 1);
        }
        $dict = self::where('dict_type',$type)->find();
        if (!$dict) {
            return true;
        }
        DictDataModel::where('dict_type',$type)->delete();
        $r = $dict->delete();
        return true;
    }

}
