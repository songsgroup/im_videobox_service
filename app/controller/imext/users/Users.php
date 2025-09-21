<?php

namespace app\controller\imext\users;

use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\ImExtUserModel;

#[Group('imext/users')]
class Users extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new ImExtUserModel;
    }

    #[Route('GET', 'list')]
    public function list()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d', 1);
        $where['pageSize'] = input('pageSize/d', 10);


        $r = ImExtUserModel::search($where);
        $this->success($r);
    }

    #[Route('GET', ':userId')]
    public function getInfo($userId)
    {

        $r =  ImExtUserModel::where('user_id', $userId)->find();
        if ($r) {
            $sysdata = $r["userName"];
        } else {
        }
        return json([
            'code' => 200,
            'data' => $r,
            'msg'  => "",
        ]);
        //
    }

    #[Route('POST', 'add')]
    public function add()
    {
        $data = $this->request->param();

        $editdata = [
            'user_id' =>  $data["userID"],
            'user_name' =>  $data["userName"],
            'referrer_id' =>  $data["referrerId"],
            'create_time' =>  date('Y-m-d H:i:s'),
        ];

        $r = $this->model->create($editdata);
        if (!$r) {
            $this->error('保存失败');
        }
        $this->success();
    }

    #[Route('POST', 'update')]
    public function edit()
    {
        $id = input('id/d', 0);
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

    #[Route('DELETE', ':id')]
    public function remove(int $id)
    {
        $data = $this->model->find($id);
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
