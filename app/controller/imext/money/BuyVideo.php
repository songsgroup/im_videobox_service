<?php

namespace app\controller\imext\money;

use think\annotation\route\Group;
use think\annotation\route\Route;

use think\facade\Db;   // 引入 Db 门面

use app\common\Pub;
use app\model\imext\ImExtUserModel;
use app\model\imext\RecorderModel;


/**
 * 资金流向记录管理
 */
#[Group('imext/money/buyvideo')]
class BuyVideo extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new RecorderModel;
    }

    /**
     * 用户付费
     */
    #[Route('POST', 'buy')]
    public function buy()
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
            $inputType = $data["type"];
            $userMoney = $imExtUser["money"];
            $clientMsgID = $data["clientMsgID"];


            //如果用户输入钱了。直接扣钱
            if ($inputMoney != "") {
                //钱不够了。提示充值
                if ($userMoney < $inputMoney) {
                    $result = ['errCode' => -99,'errMsg' => '你的余额不足，请充值！', 'code' => -99, 'data' => false, 'msg' => '你的余额不足，请充值！'];
                } else {
                    Db::startTrans();
                    //减少发送者钱
                    try {

                        Db::name('imext_user')
                            ->where('user_id', $userId)
                            ->update([
                                'money' => Db::raw('money - ' . $inputMoney),
                                'uesr_type' => $inputType,
                                'view_long' =>1
                            ]);

                        $Rorders = array(
                            'user_id' => $userId,
                            'money' => $inputMoney,
                            'money_front' => $userMoney,
                            'type' => "支出",
                            'data_id' => $clientMsgID,
                            'type' => "",
                            'status' => 0,
                            'create_time' => date('Y-m-d H:i:s'),
                            'remark' => "播放扣款"
                        );
                        $r1 = Db::name('imext_money_record')->insertGetId($Rorders);
                        //增加系统用户钱

                        Db::name('imext_user')
                            ->where('user_id', Pub::$SysUserId)
                            ->update(['money' => Db::raw('money + ' . $inputMoney)]);

                        $Rorders = array(
                            'user_id' => Pub::$SysUserId,
                            'money' => $inputMoney,
                            'money_front' => $userMoney,
                            'type' => "收入",
                            'data_id' => $clientMsgID,
                            'type' => "",
                            'status' => 0,
                            'create_time' => date('Y-m-d H:i:s'),
                            'remark' => "播放扣款"
                        );
                        $r1 = Db::name('imext_money_record')->insertGetId($Rorders);

                        //如果是包月，写入包月的时间区间
                        if($inputType==1){
                            $now = date('Y-m-d H:i:s');
                            $nextMonth = date('Y-m-d H:i:s', strtotime('+1 month'));

                            Db::name('imext_user')
                            ->where('user_id', $userId)
                            ->update([                                
                                'month_start' => $now,
                                'month_end' =>$nextMonth
                            ]);
                        }
                        
                        //
                        $result = ['errCode' => 200,'errMsg' => '付款成功','code' => 200, 'data' => true, 'msg' => "付款成功"];
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        $result = ['errCode' => -99,'errMsg' => $e->getMessage(),'code' => -99, 'data' => false, 'msg' => $e->getMessage()];
                    }
                }
            } else {
            }
        } else {
            $result = ['errCode' => -99,'errMsg' =>"用户不存在！",'code' => -99, 'data' => false, 'msg'  => "用户不存在！"];
        }

        return json($result);
    }
}
