<?php

namespace app\controller\imext\money;

use think\facade\Db;   // 引入 Db 门面

use think\annotation\route\Group;
use think\annotation\route\Route;

use app\model\imext\ImExtUserModel;
use app\model\imext\RechargeModel;
use app\model\imext\PaymentConfigModel;

use app\utils\Logger;

/**
 * 充值管理
 */
#[Group('imext/money/recharge')]
class Recharge extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new RechargeModel;
    }

    /**
     * 获取充值记录列表
     */
    #[Route('GET', 'list')]
    public function list()
    {
        $where = [];
        $where['pageNo'] = input('pageNo/d', 1);
        $where['pageSize'] = input('pageSize/d', 10);
        $where['userId'] = input('userId', '');


        $r = RechargeModel::search($where);
        $this->success($r);
    }

    /**
     * 根据ID获取充值记录详情
     */
    #[Route('GET', ':id')]
    public function getInfo($id)
    {
        $r = [
            'data' => $this->model->find($id)
        ];
        $this->success($r);
    }

    /**
     * 新增充值记录
     */
    #[Route('POST', 'add')]
    public function add()
    {
        $result = [];

        $data = $this->request->param();

        // 设置默认值
        // 读取充值通道和信息
        //


        if (empty($data['code'])) {
            $result = ['code' => -99, 'data' => false, 'msg'  => "充值代码不能为空"];
            return json($result);
        }
        if (empty($data['number'])) {
            $result = ['code' => -99, 'data' => false, 'msg'  => "充值代码不能为空"];
            return json($result);
        }
        if (empty($data['money']) || $data['money'] <= 0) {
            $result = ['code' => -99, 'data' => false, 'msg'  => "充值金额不能为空"];
            return json($result);
        }
        $payment = PaymentConfigModel::where('code',$data["code"])->where('number',$data["number"])->find();
        if (!$payment) {
            $result = ['code' => -99, 'data' => false, 'msg'  => ",请刷新后重试"];
            return json($result);
        }
        if ($payment['enabled'] != 1) {
            $result = ['code' => -99, 'data' => false, 'msg'  => "通道已关闭，请更换通道！"];
            return json($result);
        }

        $imExtUser = ImExtUserModel::where('user_id',$data["userID"])->find(); 
        if ($imExtUser==null) {
            $result = ['code' => -99, 'data' => false, 'msg'  => "用户不存在！"];
            return json($result);
        }


        $data['create_time'] = date('Y-m-d H:i:s');

        $data['status'] = 0;

        $money = $data['money'];
        $orderNo = $this->getOrderNo();
        $data["order_no"] = $orderNo;
        $number = $payment["number"];

        $r = $this->model->create($data);
        if (!$r) {
            $this->error('保存失败');
        }

        try {
            $payin_class = "\\app\\common\\pay\\" . ucfirst($data['code']);
            $payin_class = new $payin_class;
            $payresult = $payin_class->recharge($orderNo,$money, $number);
            if (!empty($payresult['status']) && $payresult['status'] == 1) {
                $result = ['errCode' => 200,'errMsg' => "充值通道！",'code' => 200, 'data' => $payresult['h5_url'], 'msg'  => "充值通道！"];
                //$result = ['errCode' => 200,'errMsg' => $payresult, 'code' => -99, 'data' => false, 'msg' =>  $payresult];
                return json($result);
            } else {
                $result = ['errCode' => -99,'errMsg' => "网络繁忙，请选择其他通道！" , 'code' => -99, 'data' => false, 'msg' =>  "网络繁忙，请选择其他通道！"];

                Logger::log('充值' . $orderNo);
                Logger::log(var_export( $payresult, 1));
            }
        } catch (\Exception $e) {
            Logger::log('充值 ' . $e->getMessage());
            $result = ['errCode' => -99,'errMsg' => $e->getMessage(), 'code' => -99, 'data' => false, 'msg' => $e->getMessage()];

        }

        return json($result);
    }

    /**
     * 修改充值记录
     */
    #[Route('POST', 'update')]
    public function edit()
    {
        $id = input('id/d', 0);
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('资源不存在');
        }

        $updateData = $this->request->param();
        $updateData['update_time'] = date('Y-m-d H:i:s');

        // 如果状态变为完成，设置完成时间
        if (isset($updateData['status']) && $updateData['status'] == '1') {
            $updateData['time_closed'] = time();
        }

        $r = $data->save($updateData);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除充值记录
     */
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

    /**
     * 审核充值
     */
    #[Route('POST', 'audit')]
    public function audit()
    {
        $id = input('id/d', 0);
        $status = input('status', '');
        $msg = input('msg', '');
        $adminId = input('adminId/d', 0);

        $data = $this->model->find($id);
        if (!$data) {
            $this->error('充值记录不存在');
        }

        $updateData = [
            'status' => $status,
            'msg' => $msg,
            'admin_id' => $adminId,
            'update_time' => date('Y-m-d H:i:s')
        ];

        // 如果审核通过，设置完成时间
        if ($status == '1') {
            $updateData['time_closed'] = time();
        }

        $r = $data->save($updateData);
        if (!$r) {
            $this->error('审核失败');
        }

        $this->success();
    }

    /**
     * 批量处理分佣
     */
    #[Route('POST', 'processCommission')]
    public function processCommission()
    {
        $ids = input('ids/a', []);
        if (empty($ids)) {
            $this->error('请选择要处理的记录');
        }

        $r = $this->model->whereIn('id', $ids)->update([
            'is_processed' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ]);

        if (!$r) {
            $this->error('处理失败');
        }

        $this->success(['processed_count' => $r]);
    }

    /**
     * 获取充值统计
     */
    #[Route('GET', 'statistics')]
    public function statistics()
    {
        $where = [];
        $where['params'] = [
            'beginTime' => input('beginTime', ''),
            'endTime' => input('endTime', '')
        ];

        $r = RechargeModel::getStatistics($where);
        $this->success($r);
    }

    /**
     * 导出充值记录
     */
    #[Route('POST', 'export')]
    public function export()
    {
        $where = [];
        $where['userId'] = input('userId', '');
        $where['type'] = input('type', '');
        $where['source'] = input('source', '');
        $where['status'] = input('status', '');
        $where['params'] = [
            'beginTime' => input('beginTime', ''),
            'endTime' => input('endTime', '')
        ];

        // 获取所有数据（不分页）
        $data = RechargeModel::search($where);

        // 这里可以实现Excel导出逻辑
        // 暂时返回数据
        $this->success($data);
    }

    public function getOrderNo()
    {
        $result = "SI" . (date("YmdHms") . rand(1, 9) . rand(1, 9) . rand(1, 9) . rand(1, 9));
        return $result;
    }
}
