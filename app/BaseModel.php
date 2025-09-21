<?php
declare (strict_types = 1);

namespace app;

/**
 * BaseModel
 *
 * @author 心衍
 * @version 2024-05-02 17:45:50
 */
class BaseModel extends \think\Model
{

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // 自动转驼峰
    protected $convertNameToCamel = true;

    protected $mapping = [
        ];

    // 字段默认值
    protected $default = [
        ];

    // 添加扩展字段
    protected $append = [
        ];

    // 构造函数，用于初始化模型
    public function __construct(array $data = []){
        // 合并默认值和传入的数据
        $data = array_merge($this->default, $data);

        // 调用父类的构造函数
        parent::__construct($data);
    }

    //模型事件
    public static function onBeforeInsert($data){
        if (array_key_exists('create_by',$data->getData())) {
            $auth = \app\Auth::instance();
            $user = $auth->isLogin()?$auth->userName:'';
            $data['createBy'] = $user;
        }
        if (array_key_exists('update_by',$data->getData())) {
            $auth = \app\Auth::instance();
            $user = $auth->isLogin()?$auth->userName:'';
            $data['updateBy'] = $user;
        }
    }

    //模型事件
    public static function onBeforeUpdate($data){
        if (array_key_exists('update_by',$data->getData())) {
            $auth = \app\Auth::instance();
            $user = $auth->isLogin()?$auth->userName:'';
            $data['updateBy'] = $user;
        }
    }

    //模型事件
    public static function onAfterInsert($data){
        if (array_key_exists('weigh',$data->getData())) {
            $data['weigh'] = $data[$data->getPk()];
            $data->save();
        }
        if (array_key_exists('sort',$data->getData())) {
            $data['sort'] = $data[$data->getPk()];
            $data->save();
        }
    }

    public function getSelectOptions(string $show_field,int|array|null $ids,string $title,int $page=1,int $pageSize=10): array
    {
        $model = new static;
        $pk = $model->getPk();
        $model = $model->field([$pk=>'id',$show_field=>'title']);
        $ids && $model = $model->where([$pk=>$ids]);
        $title && $model = $model->where($show_field,'like','%'.$title.'%');
        $total = $model->count();
        $ids || $model = $model->limit(($page-1)*$pageSize,$pageSize);
        $rows = $model->select();
        $rows = array_map(fn($x)=>['id'=>$x['id'],'title'=>$x['title']],$rows->toArray());
        return [$total,$rows];
    }

}
