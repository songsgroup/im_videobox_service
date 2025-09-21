<?php

namespace app\controller\imext\video;

use think\facade\Db;   // 引入 Db 门面

use think\annotation\route\Route;
use think\annotation\route\Group;

use app\model\imext\ImExtUserModel;

use app\model\imext\VideoModel;
use app\model\imext\VideoViewModel;

use app\model\imext\RecorderModel;

/**
 * Notice
 */
#[Group('imext/video')]
class Video extends \app\BaseController
{
    protected $noNeedLogin = ['*'];
    protected $videoView = null;
    protected $recorderModel = null;

    protected function initialize()
    {
        parent::initialize();
        $this->model = new VideoModel;
        $this->videoView = new VideoViewModel;
        $this->recorderModel = new RecorderModel;
    }

    /**
     * 获取通知公告列表
     */
    #[Route('GET', 'list')]
    public function list()
    {
        $where = [];
        $where['pageNo'] = input('pageNo/d', 1);
        $where['pageSize'] = input('pageSize/d', 10);
        $where['videoName'] = input('videoName', '');
        $where['videoType'] = input('videoType', 0);
        $where['createBy'] = input('createBy', 0);

        $r = VideoModel::search($where);

        $this->success($r);
    }

    /**
     * 新增通知公告
     */
    #[Route('POST', '$')]
    // #[PreAuthorize('hasPermi','imext:video:add')]
    public function play()
    {
        //传入 视频编号，用户编号，
        //
        $result = [];
        $data = $this->request->param();

        // "userID": "7725228767",
        // "nickname": "暴风",
        // clientMsgID
        // 查询用户状态，如果是包月用户，查询是否包月期间
        $userId = $data["userID"];

        $imExtUser = ImExtUserModel::where('user_id',  $userId)->find();
        if ($imExtUser) {
            $today = date('Y-m-d');
            //用户输入钱，扣除费用
            $inputMoney = $data["money"];
            $userMoney = $imExtUser["money"];
            $clientMsgID = $data["clientMsgID"];

            $datacount =  VideoViewModel::where('user_id',  $userId)
                ->whereBetweenTime('create_time', $today . ' 00:00:00', $today . ' 23:59:59')
                ->count();
            //如果用户输入钱了。直接扣钱

            //0:普通用户，1：包月用户，2：代理商
            $userType = $imExtUser["user_type"];
            // 如果是代理用户             
            if ($userType == 2) {
            }
            // 如果包月用户
            else  if ($userType == 1) {
                $start = strtotime($imExtUser['month_start']);
                $end   = strtotime($imExtUser['month_end']);
                $now   = time();

                if ($now >= $start && $now <= $end) {
                    $result = ['code' => 200, 'data' => true, 'msg' => $datacount];
                } else {
                    $result = ['code' => -99, 'data' => false, 'msg' => '你的包月已经过期'];
                }
            }
            // 如果是免费用户
            else if ($userType == 0) {
                if ($datacount <= 3) {
                    $result = ['code' => 200, 'data' => true, 'msg'  => $datacount];
                } else {
                    //检查是不是充过钱
                    if ($imExtUser["user_type"] == 0 && $imExtUser["view_long"] == 1) {
                        Db::name('imext_user')
                            ->where('user_id', $userId)
                            ->update([
                                'view_long' => 0
                            ]);
                            
                         $result = ['code' => 200, 'data' => true, 'msg'  => $datacount];    
                    } else {
                        $result = ['code' => -99, 'data' => false, 'msg'  => $datacount];
                    }
                }
            }

            //查询数据库是否超过


            //不超过，那就添加

            $viewdata = [
                'user_id' =>  $data["userID"],
                'video_id' =>  $data["clientMsgID"],
                'create_time' =>  date('Y-m-d H:i:s'),
            ];
            $r = $this->videoView->create($viewdata);
        } else {
            $result = ['code' => 200, 'data' => false, 'msg'  => "用户不存在！"];
        }

        return json($result);
    }



    /**
     * 根据通知公告编号获取详细信息
     */
    #[Route('GET', ':videoId')]
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
    #[Route('POST', '$')]
    // #[PreAuthorize('hasPermi','imext:video:add')]
    public function add()
    {
        $data = $this->request->param();

        $r = $this->model->create($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success();
    }

    /**
     * 修改通知公告
     */
    #[Route('POST', '$')]
    // #[PreAuthorize('hasPermi','imext:video:edit')]
    public function edit()
    {
        $inputdata = $this->request->param();
        $id = $inputdata["id"];

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
    #[Route('DELETE', ':videoId')]
    // #[PreAuthorize('hasPermi','imext:video:remove')]
    public function remove(int $videoId)
    {
        $data = $this->model->find($videoId);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->delete();
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除通知公告
     */
    #[Route('POST', '$')]
    // #[PreAuthorize('hasPermi','imext:video:remove')]
    public function uploadmedia()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 400, 'msg' => '请上传视频文件']);
        }

        // 保存到 public/storage/videos 目录
        $path = \think\facade\Filesystem::disk('public')->putFile('videos', $file);

        return json([
            'code' => 200,
            'msg'  => '上传成功',
            'url'  => '/storage/' . $path
        ]);
    }
}
