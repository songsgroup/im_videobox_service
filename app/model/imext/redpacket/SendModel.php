<?php
declare (strict_types = 1);

namespace app\model\imext\redpacket;

/**
 * ConfigModel
 */
class SendModel extends \app\BaseModel
{
    // 表名
    protected $name = 'redpacket_send';
    protected $pk = 'id';

    // 字段默认值·
  

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('id','asc');
        $videoName && $m = $m->where('video_name','like','%'.$videoName.'%');
        $videoType && $m = $m->where('video_type',$videoType);
        $createBy && $m = $m->where('create_by',$createBy);
        isset($params) && $params && $m = $m->whereBetweenTime ('create_time',$params['beginTime'],$params['endTime']);
        $total = $m->count();
        if ($pageNo??0 && $pageSize??0) {
            $m = $m->page($pageNo)->limit($pageSize);
        }
        $rows = $m->select()->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

}
