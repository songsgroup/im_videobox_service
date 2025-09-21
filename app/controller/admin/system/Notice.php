<?php
namespace app\controller\admin\system;


use think\annotation\route\Route;
use think\annotation\route\Group;

use app\PreAuthorize;
use app\model\admin\NoticeModel;

/**
 * Notice
 */
#[Group('admin/system/notice')]
class Notice extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize(){
        parent::initialize();
        $this->model = new NoticeModel;
    }
    
    /**
     * 获取通知公告列表
     */
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','system:notice:list')]
    public function list(){
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['noticeTitle'] = input('noticeTitle','');
        $where['noticeType'] = input('noticeType',0);
        $where['createBy'] = input('createBy',0);

        $r = NoticeModel::search($where);

        $this->success($r);
    }

    /**
     * 根据通知公告编号获取详细信息
     */
    #[Route('GET',':noticeId')]
    #[PreAuthorize('hasPermi','system:notice:query')]
    public function getInfo($noticeId)
    {
        $r = [
            'data' => $this->model->find($noticeId)
        ];
        $this->success($r);
    }

    /**
     * 新增通知公告
     */
    #[Route('POST','$')]
    #[PreAuthorize('hasPermi','system:notice:add')]
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
    #[PreAuthorize('hasPermi','system:notice:edit')]
    public function edit()
    {
        $id = input('noticeId/d',0);

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
    #[Route('DELETE',':noticeId')]
    #[PreAuthorize('hasPermi','system:notice:remove')]
    public function remove(int $noticeId)
    {
        $data = $this->model->select($noticeId);
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
