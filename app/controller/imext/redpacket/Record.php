<?php
namespace app\controller\imext\redpacket;


use think\annotation\route\Route;
use think\annotation\route\Group;
use app\model\imext\VideoModel;

/**
 * Notice
 */
#[Group('imext/redpacket/record')]
class Record extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize(){
        parent::initialize();
        $this->model = new VideoModel;
    }
    
    /**
     * 获取通知公告列表
     */
    #[Route('GET','list')]
    public function list(){
        $where = [];
        $where['pageNo'] = input('pageNo/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['videoName'] = input('videoName','');
        $where['videoType'] = input('videoType',0);
        $where['createBy'] = input('createBy',0);

        $r = VideoModel::search($where);

        $this->success($r);
    }

    /**
     * 根据通知公告编号获取详细信息
     */
    #[Route('GET',':videoId')]
    // #[PreAuthorize('hasPermi','imext:video:query')]
    public function getInfo($videoId)
    {
        $r = [
            'data' => $this->model->find($videoId)
        ];
        $this->success($r);
    }

    /**
     * 新增通知公告
     */
    #[Route('POST','$')]
    // #[PreAuthorize('hasPermi','imext:video:add')]
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
    // #[PreAuthorize('hasPermi','imext:video:edit')]
    public function edit()
    {
        $id = input('videoId/d',0);

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
    #[Route('DELETE',':videoId')]
    // #[PreAuthorize('hasPermi','imext:video:remove')]
    public function remove(int $videoId)
    {
        $data = $this->model->select($videoId);
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
