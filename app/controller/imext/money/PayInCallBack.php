<?php

namespace app\controller\imext\money;

use app\model\imext\ImExtUserModel;
use think\facade\Db;   // 引入 Db 门面


use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\RecorderModel;

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
        $this->imExtUser =new ImExtUserModel;
    }

    /**
     * 获取充值记录列表
     */
    #[Route('POST', 'apply/:orderNo')]
    // 第三方支付平台回调处理
    public function apply($orderNo)
    {
   
        $post_data = $this->request->param();
        $orderNo=$post_data["orderNo"];
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
 
            $r1 = Db::name('imext_user')->where(["user_id"=>$user_id])->update(['money' => Db::raw('money +' . $money)]);
            //流水记录
           
            $sql1 = array('user_id'=>$user_id,'money'=>$money,'money_front'=>$money_front, "data_id"=>$orderNo,           
            'type'=>"收入",'status'=>0,'create_time'=>date('Y-m-d H:i:s'),'remark'=>"用户充值完成！");
                
            $r1 = Db::name('imext_money_record')->insertGetId($sql1);

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

    // 日志
    public function Log($Log_content)
    {
        // $lktlog = new LaiKeLogUtils();
        // $lktlog->log("admin/payment_callback.log", $Log_content);
        return;
    }
}