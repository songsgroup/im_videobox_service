<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * PostModel
 */
class PostModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_post';
    protected $pk = 'post_id';

    protected $mapping = [
// post_id
// post_code
// post_name
// post_sort
// status
// create_by
// create_time
// update_by
// update_time
// remark
        ];

    public static function getPosts()
    {
        return self::where('status',0)->select()->toArray();
    }

    public static function search($where=[])
    {
        extract($where);

        $m = self::order('post_id','asc');
        $postName && $m = $m->where('post_name','like','%'.$postName.'%');
        $postCode && $m = $m->where('post_code','like','%'.$postCode.'%');
        $status!==null && $m = $m->where('status',$status);
        $total = $m->count();
        $rows = $m->page($pageNum)
                    ->limit($pageSize)
                    ->select()
                    ->toArray();
        return ['total'=>$total,'rows'=>$rows];
    }
}
