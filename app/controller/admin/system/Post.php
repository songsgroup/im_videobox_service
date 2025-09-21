<?php
namespace app\controller\admin\system;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use think\facade\Cache;
use app\model\admin\PostModel;
use think\facade\Request;

/**
 * Post
 */
#[Group('admin/system/post')]
class Post extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize(){
        parent::initialize();
        $this->model = new PostModel;
    }
    
    /**
     * list
     *
     * @method (GET)
     */
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','system:post:list')]
    public function list(){
    	$where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
    	$where['postName'] = input('postName','');
        $where['postCode'] = input('postCode','');
    	$where['status'] = input('status',null);

        $r = PostModel::search($where);
        $this->success($r);
    }

    /**
     * 根据通知公告编号获取详细信息
     */
    #[Route('GET',':postId')]
    #[PreAuthorize('hasPermi','system:post:query')]
    public function getInfo($postId)
    {
        $r = [
            'data' => $this->model->find($postId)
        ];
        $this->success($r);
    }

    /**
     * 新增通知公告
     */
    #[Route('POST','$')]
    #[PreAuthorize('hasPermi','system:post:add')]
    public function add()
    {
        $data=$this->request->param();

        $r = $this->model->create($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success();
    }

    /**
     * 修改通知公告
     */
    #[Route('PUT','$')]
    #[PreAuthorize('hasPermi','system:post:edit')]
    public function edit()
    {
        $id = input('postId/d',0);

        $data = $this->model->find($id);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->save($this->request->param());
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除通知公告
     */
    #[Route('DELETE',':postId')]
    #[PreAuthorize('hasPermi','system:post:remove')]
    public function remove(int $postId)
    {
        $data = $this->model->select($postId);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->delete();
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }
}
