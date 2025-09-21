<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * NoticeModel
 */
class NoticeModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_notice';
    protected $pk = 'notice_id';

    protected $mapping = [
// notice_id
// notice_title
// notice_type
// notice_content
// status
// create_by
// create_time
// update_by
// update_time
// remark
        ];

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('notice_id','asc');
        $noticeTitle && $m = $m->where('notice_title','like','%'.$noticeTitle.'%');
        $noticeType && $m = $m->where('notice_type',$noticeType);
        $createBy && $m = $m->where('create_by',$createBy);
        $total = $m->count();
        $rows = $m->page($pageNum)
                    ->limit($pageSize)
                    ->select()
                    ->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }

}
