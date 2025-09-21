<?php
declare (strict_types = 1);

namespace app\model\admin;

/**
 * UserPostModel
 */
class UserPostModel extends \think\model\Pivot
{
    // 表名
    protected $name = 'sys_user_post';

    protected $convertNameToCamel = true;
    
    protected $mapping = [
// user_id
// post_id
        ];

    public static function setUserPosts(int $userId,array $postIds)
    {
        $old = self::where('user_id',$userId)
                ->field(['post_id'])
                ->select()
                ->map(fn($x)=>$x->post_id)
                ->toArray();
        $dels = array_diff($old,$postIds);
        if ($dels) {
            self::where('user_id',$userId)
                    ->where('post_id','in',$dels)
                    ->delete();
        }
        $news = array_diff($postIds,$old);
        if ($news) {
            (new self)->saveAll(array_map(fn($postId)=>['post_id'=>$postId,'user_id'=>$userId],$news));
        }
        return ;
    }

}
