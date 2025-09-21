<?php

namespace app\controller\imext\users;

use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\ImExtUserModel;

use think\facade\Db;   // 引入 Db 门面
use app\utils\Logger;

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

        // 生成唯一邀请码
        do {
            $c_code = $this->generateInviteCode();
            $exists = Db::name('imext_user')->where('invitation_code', $c_code)->find();
        } while ($exists);

        $user = Db::name('imext_user')
            ->field('id, username, c_code')
            ->where('invitation_code',  $data["referrerId"])
            ->find();

        if ($user) {
            $referrer_id = $user["userId"];
        } else {
            $referrer_id = "";
        }

        $editdata = [
            'user_id' =>  $data["userID"],
            'user_name' =>  $data["userName"],
            'referrer_id' => $referrer_id,
            'create_time' =>  date('Y-m-d H:i:s'),
            'invitation code' => $c_code,
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

    function generateInviteCode($length = 4)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }
}
