<?php

namespace app\controller\imext\money;

use app\model\imext\ImExtUserModel;
use think\facade\Db;   // 引入 Db 门面


use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\RecorderModel;
use app\model\imext\ImExtConfigModel;

use app\utils\Logger;

/**
 * 资金流向记录管理
 */
#[Group('imext/money/payincallback')]
class PayInCallBack extends \app\BaseController
{

    protected $noNeedLogin = ['*'];
    protected $imExtUser;

    protected function initialize()
    {
        parent::initialize();
        $this->model = new RecorderModel;
        $this->imExtUser = new ImExtUserModel;
    }

    /**
     * 获取充值记录列表
     */
    #[Route('POST', 'apply/:orderNo')]
    // 第三方支付平台回调处理
    public function apply($orderNo)
    {

        $post_data = $this->request->param();
        $orderNo = $post_data["orderNo"];
        Logger::Log('post_data[' . json_encode($post_data) . ']');
        $this->Log(__LINE__ . ":'post_data[' ：" . json_encode($post_data));

        $recharge = Db::table('imext_recharge')->where('order_no', $orderNo)->find();

        if (empty($recharge)) {
            // if($rechargeInfo['status'] == 'success')
            // {
            //     $this->Log(__LINE__ . ":订单已处理成功：" . $orderNo);

            //     Logger::Log("订单已处理成功：" . $orderNo);
            //     exit('SUCCESS');
            //     return;
            // }
            Logger::Log("订单不存在：" . $orderNo);
            $this->Log(__LINE__ . ":'订单不存在' ：" . $orderNo);
            exit('FAIL');
            return;
        } else {
            // Logger::Log("订单不存在：" . $orderNo);
            // $this->Log(__LINE__ . ":'订单不存在' ：" . $orderNo);
            // exit('FAIL');
            // return;
            Logger::Log("订单存在：" . $orderNo);
        }

        $user_id =   $recharge['user_id'];
        $this->Log(__LINE__ . ":'用户id' ：" . $user_id);
        // 查询用户信息
        $user_info = Db::table('imext_user')->where('user_id', $user_id)->find();
        if (empty($user_info)) {

            $this->Log(__LINE__ . ":'用户不存在' ：" . json_encode($user_info));
            exit('SUCCESS');
            return;
        } else {
            $this->Log(__LINE__ . ":'用户存在' ：" . json_encode($user_info));
        }

        $member_id = $user_info['id'];
        $money_front = $user_info['money'];

        //$this->Log(__LINE__ . ":'$rechargeInfo' ：" . json_encode($rechargeInfo));

        // 充值金额
        $money = $recharge['money'];

        $oldStatus = $recharge['status'];

        if ($oldStatus == 'success') {
            //订单被支付过，不能重复处理，返回；
            $this->Log(__LINE__ . ":'重要，订单已经被处理，不能重复处理：" . $orderNo);
            exit('SUCCESS');
        }


        // 开启事务
        Db::startTrans();

        try {

            // 更新充值记录为成功状态
            $r = Db::table('imext_recharge')->where('order_no', $orderNo . '')->update([
                'status' => 'success', // 支付成功
                'msg' => '支付成功',
                'update_time' => date('Y-m-d H:i:s')
            ]);
            if (!$r) {
                $this->Log(__LINE__ . ":'更新用户充值记录' ：" . $r . "order_no :" . $orderNo);
                throw new \Exception('更新充值记录失败');
            }


            // 更新用户余额

            $r1 = Db::name('imext_user')->where(["user_id" => $user_id])->update(['money' => Db::raw('money +' . $money)]);
            //流水记录

            $sql1 = array(
                'user_id' => $user_id,
                'money' => $money,
                'money_front' => $money_front,
                "data_id" => $orderNo,
                'type' => "收入",
                'status' => 0,
                'create_time' => date('Y-m-d H:i:s'),
                'remark' => "用户充值完成！"
            );

            $r1 = Db::name('imext_money_record')->insertGetId($sql1);

            //记录分佣结果

            $this->ProcessBrokerage($user_id, $money, $orderNo);

            $this->Log(__LINE__ . ":支付回调处理成功，订单号：" . $orderNo . "，美元金额：" . $money);
            //sreturn json_encode(['code' => 'SUCCESS', 'message' => '处理成功']);
            // 提交事务
            Db::commit();

            exit('SUCCESS');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();


            $this->Log(__LINE__ . ":支付回调处理失败：" . $e->getMessage());
            //return json_encode(['code' => 'FAIL', 'message' => '处理失败']);
            exit('FAIL');
        }
    }


    /**
     * 充值分佣处理
     */
    public function ProcessBrokerage($userId, $money, $orderId)
    {
        $result = true;

        $sqlParents = "
                WITH RECURSIVE cte AS (
                    SELECT
                        id,
                        user_id,
                        referrer_id,
                        0 AS LEVEL
                    FROM
                        imext_user
                    WHERE
                        user_id = '$userId' UNION ALL
                    SELECT
                        t.id,
                        t.user_id,
                        t.referrer_id,
                        c.LEVEL + 1
                    FROM
                        imext_user t
                        JOIN cte c ON t.user_id = c.referrer_id
                    ) SELECT
                    *
                    FROM
                    cte
                    WHERE
                    user_id != '$userId'
                    ORDER BY
                    LEVEL DESC;
        ";

        $resAllBrokerages = Db::query($sqlParents);
        //
        Db::startTrans();
        try {
            foreach ($resAllBrokerages as $key => $brokerages) {
                //3：把所有的金额按照分佣要求，计算每个用户分佣，
                //分佣比例
                $this->Log("第一个分佣用户：");

                $level = $brokerages["level"];
                //上级用户，收入
                $in_user_id = $brokerages["user_id"];
                $imExtBrokerageUser = ImExtUserModel::where('user_id',  $in_user_id)->find();
                //分佣前的金额
                $money_front = $imExtBrokerageUser["money"];

                //分佣比列
                $brokerageLeve = $this->GetBrokerage($level);
                //大于0的分佣才处理
                if ($brokerageLeve > 0) {
                    //分佣金额
                    $BrokerageMoneys = $money * $brokerageLeve;
                    // 4：插入到流水表，
                    $Rorders = array(
                        'user_id' => $in_user_id,
                        'money' => $BrokerageMoneys,
                        'money_front' => $money_front,
                        'type' => "收入",
                        'data_id' => $orderId,
                        'status' => 0,
                        'create_time' => date('Y-m-d H:i:s'),
                        'remark' => "分佣收入"
                    );
                    $rcorder = Db::name('imext_money_record')->insertGetId($Rorders);


                    //增加用户的金额
                    $rupdate =  Db::name('imext_user')
                        ->where('user_id', $in_user_id)
                        ->update([
                            'money' => Db::raw('money + ' . $BrokerageMoneys)
                        ]);
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $Log_content = $e->getMessage();
            Logger::Log($Log_content);
            $result = false;
        }

        return $result;
    }




    //这个应该从数据库获取配置
    public function GetBrokerage($level)
    {

        //1,0.2;2,0.1;3,0.05;4,0.01
        //-1,0.7;0,0.15;1,0.15;2,0
        //分佣的配置为 -1:商家保留比例，0：平台保留比例，1：直接上级保留比例，2：上级的上级保留比例。....；
        //这些比例合计为1,目前数据为 商家 70%;平台 15%,直接上级 15%
        //1:0.3;2:0.1;3:0.05;4:0.03;5:0.01;6:0.005;7:0.003;8:0.002


        $resConfigs = ImExtConfigModel::Where("config_key", "imext.brokerage")->find();

        if ($resConfigs) {
            $configValue = $resConfigs["config_value"];
            $pairs = explode(';', $configValue);

            foreach ($pairs as $pair) {
                list($configId, $value) = explode(':', $pair);
                if ($configId == $level) {
                    return (float)$value;
                }
            }
        }

        return 0;
    }

    // 日志
    public function Log($Log_content)
    {
        // $lktlog = new LaiKeLogUtils();
        // $lktlog->log("admin/payment_callback.log", $Log_content);
        return;
    }
}
