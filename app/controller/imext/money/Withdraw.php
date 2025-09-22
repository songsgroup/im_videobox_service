<?php

namespace app\controller\imext\money;

use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\WithdrawModel;
use app\model\imext\UserBankModel;

use think\facade\Db;   // 引入 Db 门面
use app\utils\Logger;

/**
 * 提现管理
 */
#[Group('imext/money/withdraw')]
class Withdraw extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new WithdrawModel;
    }

    /**
     * 获取提现记录列表
     */
    #[Route('GET', 'list')]
    public function list()
    {

        $where = [];
        $where['pageNo'] = input('pageNo/d', 1);
        $where['pageSize'] = input('pageSize/d', 10);
        $where['userId'] = input('userID', '');

        if ($where['userId'] == "") {
            $this->error("请输入用户");
        }


        $r = WithdrawModel::search($where);
        $this->success($r);
    }

    /**
     * 根据ID获取提现记录详情
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
     * 新增提现记录
     */
    #[Route('POST', 'add')]
    public function add()
    {
        $data = $this->request->param();

        // 设置默认值
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['create_user'] = $data['create_user'] ?? 'system';

        $data["order_id"] = $this->getOrderNo();

        $money = $data["money"];
        //按照规则，加判断

        //提现需要设置个人拉新5人每天可以提100元内。45人每天可以提现500元，99人每天可以提现1000元。团队人数1000人以上随便提现。)
        //

        $r1Count = Db::name('imext_withdraw')->where("user_id", $data["userID"])->where("status", 0)->select()->count();
        if ($r1Count > 0) {
            $this->error('你的提现还在审批中，请稍后再试！');
        }

        $r1Count = Db::name('imext_user')->where("referrer_id", $data["userID"])->select()->count();

        if ($r1Count < 5) {
            $this->error('需要团队人员5人才能提现！');
        } else if ($r1Count >= 5 && $r1Count < 45) {
            if ($money > 500) {
                $this->error('需要团队人员45人才能提现500！');
            }
        } else if ($r1Count >= 45 && $r1Count < 99) {
            if ($money > 1000) {
                $this->error('需要团队人员99人才能提现1000！');
            }
        } else if ($r1Count >= 99 && $r1Count < 1000) {
            if ($money > 10000) {
                $this->error('需要团队人员999人才能提现10000！');
            }
        }


        $data["userId"] = $data["userID"];

        $r = $this->model->create($data);

        //
        $result = ['errCode' => 200, 'errMsg' => '', 'code' => 200, 'data' => $r, 'msg' => '成功'];
        //

        return json($result);
    }


    /**
     * 修改提现记录
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

        // 如果状态变为审核通过，设置审核时间
        if (isset($updateData['status']) && $updateData['status'] == 1) {
            $updateData['examine_date'] = date('Y-m-d H:i:s');
        }

        $r = $data->save($updateData);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除提现记录
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
     * 审核提现
     */
    #[Route('POST', 'audit')]
    public function audit()
    {
        $id = input('id/d', 0);
        $status = input('status/d', 0);
        $mark = input('mark', '');
        $adminId = input('adminId/d', 0);

        $data = $this->model->find($id);
        if (!$data) {
            $this->error('提现记录不存在');
        }

        $updateData = [
            'status' => $status,
            'mark' => $mark,
            'admin_id' => $adminId
        ];

        if ($status == 1) {


            $withdraw =  WithdrawModel::Where("id", $id)->find();

            $bankId = $withdraw["cardId"];
            $orderId = $withdraw["orderId"];
            $amount = $withdraw["money"]; //人民币
            //调用下发通道，进行提现操作
            //只进行银行卡提现操作，其他的不处理
            //订单号，银行名称，支行名称，用户名称，卡号。金额。卡类型
            //获取下发银行信息
            $bankres = UserBankModel::Where(["id" => $bankId])->select()->toArray();

            $bankname = $bankres[0]["BankName"];
            $subbranch = $bankres[0]["branch"];
            $accountname = $bankres[0]["cardHolder"];
            $cardnumber = $bankres[0]["bankCardNumber"]; //卡号

            //recharge($orderId,$bankname,$subbranch,$accountname,$cardnumber,$amount)

            $time = date('Y-m-d h:i:s');

            Logger::Log('开始请求' . __METHOD__ . '->' . __LINE__ . $orderId);
            try {
                $payout_class = "\\app\\common\\pay\\PayOut30";
                $payout_class = new $payout_class;

                //
                $result = $payout_class->recharge($orderId, $bankname, $subbranch, $accountname, $cardnumber, $amount);

                if ($result['status'] == 1) {
                    Logger::Log('h5_url[' . $result['h5_url'] . ']');
                    //header('Location: '.$result['h5_url']); //跳转到第三方支付平台的支付页面上
                    //状态修改3 ，待支付    
                    $r3 = Db::name('withdraw')->where(['order_id' => $orderId, 'id' => $id])->update(['status' => 3, 'examine_date' => $time]);
                    if ($r3 == -1) {
                        $Log_content = __METHOD__ . '->' . __LINE__ . $orderId . ' 修改提现记录状态失败！ID为:' . $id;
                        Logger::Log($Log_content);
                        Db::rollback();

                        return  json([
                            'code' => -99,
                            'msg' => '代收！',
                            'data' => $result['h5_url']
                        ]);
                    }

                    return  json([
                        'code' => 200,
                        'msg' => '代收！',
                        'data' => $result['h5_url']
                    ]);
                    exit;
                } else {
                    Logger::Log('代收失败' . __METHOD__ . '->' . __LINE__ . $orderId);
                }
            } catch (\Exception $e) {
                $msg = $e->getMessage();

                Logger::Log('代收失败 ' . __METHOD__ . '->' . __LINE__ . $msg);
            }


            // 如果审核通过，设置审核时间
            if ($status == 1) {
                $updateData['examine_date'] = date('Y-m-d H:i:s');
            }

            $r = $data->save($updateData);
            if (!$r) {
                $this->error('审核失败');
            }

            $this->success();
        } else {
            // 如果审核通过，设置审核时间
            if ($status == 1) {
                $updateData['examine_date'] = date('Y-m-d H:i:s');
            }

            $r = $data->save($updateData);
            if (!$r) {
                $this->error('审核失败');
            }
        }
    }

    /**
     * 批量审核提现
     */
    #[Route('POST', 'batchAudit')]
    public function batchAudit()
    {
        $ids = input('ids/a', []);
        $status = input('status/d', 0);
        $mark = input('mark', '');
        $adminId = input('adminId/d', 0);

        if (empty($ids)) {
            $this->error('请选择要审核的记录');
        }

        $updateData = [
            'status' => $status,
            'mark' => $mark,
            'admin_id' => $adminId
        ];

        // 如果审核通过，设置审核时间
        if ($status == 1) {
            $updateData['examine_date'] = date('Y-m-d H:i:s');
        }

        $r = $this->model->whereIn('id', $ids)->update($updateData);

        if (!$r) {
            $this->error('批量审核失败');
        }

        $this->success(['processed_count' => $r]);
    }

    /**
     * 拒绝提现
     */
    #[Route('POST', 'reject')]
    public function reject()
    {
        $id = input('id/d', 0);
        $refuse = input('refuse', '');
        $adminId = input('adminId/d', 0);

        $data = $this->model->find($id);
        if (!$data) {
            $this->error('提现记录不存在');
        }

        $updateData = [
            'status' => 2, // 拒绝状态
            'refuse' => $refuse,
            'admin_id' => $adminId,
            'examine_date' => date('Y-m-d H:i:s')
        ];

        $r = $data->save($updateData);
        if (!$r) {
            $this->error('拒绝操作失败');
        }

        $this->success();
    }

    /**
     * 获取提现统计
     */
    #[Route('GET', 'statistics')]
    public function statistics()
    {
        $where = [];
        $where['params'] = [
            'beginTime' => input('beginTime', ''),
            'endTime' => input('endTime', '')
        ];

        $r = WithdrawModel::getStatistics($where);
        $this->success($r);
    }

    /**
     * 获取状态统计
     */
    #[Route('GET', 'statusCounts')]
    public function statusCounts()
    {
        $r = WithdrawModel::getStatusCounts();
        $this->success($r);
    }

    /**
     * 获取待审核数量
     */
    #[Route('GET', 'pendingCount')]
    public function pendingCount()
    {
        $count = WithdrawModel::getPendingCount();
        $this->success(['count' => $count]);
    }

    /**
     * 导出提现记录
     */
    #[Route('POST', 'export')]
    public function export()
    {
        $where = [];
        $where['userId'] = input('userId', '');
        $where['name'] = input('name', '');
        $where['withdrawStatus'] = input('withdrawStatus', '');
        $where['status'] = input('status', '');
        $where['params'] = [
            'beginTime' => input('beginTime', ''),
            'endTime' => input('endTime', '')
        ];

        // 获取所有数据（不分页）
        $data = WithdrawModel::search($where);

        // 这里可以实现Excel导出逻辑
        // 暂时返回数据
        $this->success($data);
    }

    /**
     * 计算提现手续费
     */
    #[Route('POST', 'calculateFee')]
    public function calculateFee()
    {
        $money = input('money/f', 0);
        $withdrawStatus = input('withdrawStatus/d', 1);

        // 这里可以根据业务规则计算手续费
        // 示例：银行卡提现手续费2%，USDT提现手续费1%
        $feeRate = $withdrawStatus == 1 ? 0.02 : 0.01; // 银行卡2%，USDT1%
        $sCharge = $money * $feeRate;
        $actualAmount = $money - $sCharge;

        $this->success([
            'original_amount' => $money,
            'fee_rate' => $feeRate * 100,
            'fee_amount' => $sCharge,
            'actual_amount' => $actualAmount
        ]);
    }

    public function getOrderNo()
    {
        $result = "SO" . (date("YmdHms") . rand(1, 9) . rand(1, 9) . rand(1, 9) . rand(1, 9));
        return $result;
    }
}
