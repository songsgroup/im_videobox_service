<?php

namespace app\controller\imext\redpacket;

use think\facade\Db;   // 引入 Db 门面
use app\utils\Logger;

use think\annotation\route\Route;
use think\annotation\route\Group;

use app\model\imext\AdminRecordModel;
use app\model\imext\ImExtUserModel;
use app\model\imext\redpacket\SendModel;
use app\model\imext\redpacket\ReceiveModel;


/**
 * 发送红包
 */
#[Group('imext/redpacket')]
class Redpacket extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected $imUserModel;
    protected $sendModel;
    protected $receiveModel;

    protected function initialize()
    {
        parent::initialize();
        $this->imUserModel = new ImExtUserModel;
        $this->sendModel = new SendModel;
        $this->receiveModel = new ReceiveModel;
    }

 
    #[Route('GET', 'list')]
    // 分类标签列表
    public function list()
    {
        // $store_id = addslashes(Request::param('storeId'));

        $data = $this->request->param();
        $pageNo = $data["pageNo"];
        $pageSize = $data["pageSize"];
 
        $client_msg_id = $data["clientMsgID"];



        $total = 0;
        $list = array();

        $total = ReceiveModel::whereRaw("client_msg_id='" . $client_msg_id . "' And status=0")->count();

        //$r1 = ProLabelModel::where($condition)->page((int)$pageNo,(int)$pageSize)->order('add_time','desc')->select()->toArray();
        $r1 = ReceiveModel::whereRaw("client_msg_id='" . $client_msg_id . "' And status=0")->page((int)$pageNo, (int)$pageSize)->order('id', 'desc')->select()->toArray();
        if ($r1) {
            $list = $r1;
        }

        $data = array('list' => $list, 'total' => $total);
        $message = Lang('Success');
        // return output(200,$message,$data);
        return json([
            'code' => 200,
            'errCode' => 200,
            'errMsg' => "",
            'data' => $data,
            'msg' => "成功"
        ]);
    }
    /**
     * 发送红包
     */
    #[Route('POST', '$')]
    // #[PreAuthorize('hasPermi','imext:video:remove')]
    public function send()
    {
        /*
        clientMsgID， //发红包的人的消息ID
        sendID，  //发红包人的用户ID
        nickname，//发红包人的用户名
        faceURL，//发红包人的头像
        number，//红包数量
        totalMoney, //红包总金额
        type，//单个红包金额类型, 0--平均，1-随机
        返回：成功、失败
        */

        // $data=$this->request->param();

        // $store_id = addslashes(trim($this->request->param('storeId')));
        // $store_type = addslashes(trim($this->request->param('storeType')));
        // $access_id = addslashes(trim($this->request->param('accessId')));

        $client_msg_id = addslashes(trim($this->request->param('clientMsgID'))); // 标题
        $send_id = addslashes(trim($this->request->param('sendID'))); // 标题
        $nick_name = addslashes(trim($this->request->param('nickname'))); // 标题
        $face_url = addslashes(trim($this->request->param('faceURL'))); // 标题
        $red_num = addslashes(trim($this->request->param('number'))); // 标题

        $money = addslashes(trim($this->request->param('totalMoney'))); // 标题
        $type = addslashes(trim($this->request->param('type'))); // 
        $remark = addslashes(trim($this->request->param('remark'))); // 

        $out_user_id = $send_id;



        $data["create_time"] = date("Y-m-d H:i:s");

        if ($money == '') {
            $Log_content = __METHOD__ . '->' . __LINE__ . ' 红包额度不能为空！';
            Logger::Log($Log_content);
            $message = Lang('label.0');

            return json([
                'code' => 109,
                'errCode' => 109,
                'errMsg' => "钱包余额不足",
                'msg' => "失败"
            ]);
        }

        //判断发送者钱是否够。
        $r1 = ImExtUserModel::where("user_id", $out_user_id)->find();
        //
        if ($r1) {
            $old_money =  $r1["money"];
            if ($old_money < $money) {
                $Log_content = __METHOD__ . '->' . __LINE__ . ' 钱包余额不足！';
                Logger::Log($Log_content);
                $message = Lang('label.0');
                //return output(109,"钱包余额不足！");
                return json([
                    'code' => 200,
                    'errCode' => 109,
                    'errMsg' => "钱包余额不足",
                    'msg' => "失败"
                ]);
            } else {
                Db::startTrans();
                try {
                    //记录发送历史
                    $remark = "IM发送红包";

                    $sql1 = array('client_msg_id' => $client_msg_id, 'send_id' => $send_id, 'shop_id' => $out_user_id, 'nick_name' => $nick_name, 'face_url' => $face_url, 'red_num' => $red_num, 'total_money' => $money, 'type' => "", 'status' => 0, 'create_time' => $time, 'remark' => $remark, "receive_money" => 0, 'receive_num' => 0);

                    $r1 = Db::name('imext_redpacket_send')->insertGetId($sql1);

                    //减少发送者钱
                    $r1 = Db::name('imext_user')->where(["user_id" => $out_user_id])->update(['money' => Db::raw('money -' . $money)]);
                    Db::commit();
                    //
                    if ($r1 > 0) {
                        // $Jurisdiction->admin_record($store_id, $operator, '发送红成功：'.$out_user_id,1,1,0,$operator_id);
                        $Log_content = __METHOD__ . '->' . __LINE__ . ' 添加成功！';
                        Logger::Log($Log_content);
                        $message = Lang('label.4');
                        //return output(200,$r1);
                        return json([
                            'code' => 200,
                            'errCode' => 200,
                            'errMsg' => "",
                            'msg' => "成功"
                        ]);
                    } else {
                        // $Jurisdiction->admin_record($store_id, $operator, '发送红包：'.$out_user_id.' 失败',1,1,0,$operator_id);
                        $Log_content = __METHOD__ . '->' . __LINE__ . ' 添加失败！参数:' . json_encode($sql1);
                        Logger::Log($Log_content);
                        $message = Lang('label.5');
                        // return output(109,$message);
                        return json([
                            'code' => 200,
                            'errCode' => 109,
                            'errMsg' => "发送红包失败",
                            'msg' => "失败"
                        ]);
                    }
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $Log_content = $e->getMessage();
                    Logger::Log($Log_content);
                    $message = $Log_content; // Lang('Busy network');
                    //return output(ERROR_CODE_CZSB,$message);
                    return json([
                        'code' => 200,
                        'errCode' => 109,
                        'errMsg' => $message,
                        'msg' => "失败"
                    ]);
                }
            }
        } else {
            $Log_content = __METHOD__ . '->' . __LINE__ . ' 发送者不存在！';
            Logger::Log($Log_content);
            $message = Lang('label.0');
            // return output(109,"发送者不存在！");  
            return json([
                'code' => 200,
                'errCode' => 109,
                'errMsg' => "发送者不存在",
                'msg' => "失败"
            ]);
        }
    }


    #[Route('POST', '$')]
    // 接受红包
    public function receive()
    {

        /*
        clientMsgID， //发红包的人的消息ID
        rcvID，  //接收红包人的用户ID
        nickname，//收红包人的用户名
        faceURL，//收红包人的头像
        返回
        money, //收到的红包金额
        */


        $client_msg_id = addslashes(trim($this->request->param('clientMsgID'))); // 标题
        $rcv_id = addslashes(trim($this->request->param('rcvID'))); // 标题
        $nick_name = addslashes(trim($this->request->param('nickname'))); // 标题
        $face_url = addslashes(trim($this->request->param('faceURL'))); // 标题

        $remark = addslashes(trim($this->request->param('remark'))); // 


        // $Jurisdiction = new Jurisdiction();
        // $operator_id = cache($access_id.'admin_id');
        // $operator = cache($access_id.'admin_name');  

        $time = date("Y-m-d H:i:s");

        $in_user_id = $rcv_id; // $this->user_list['user_id']; 


        $r1 = Db::name('imext_redpacket_send')->where("client_msg_id", $client_msg_id)->whereExp('red_num', '> receive_num')->select()->toArray();
        //如果红包创建超过24小时，红包过期
        if (time() - strtotime($r1[0]["create_time"]) > 24 * 3600) {
            return json([
                'code' => 200,
                'errCode' => 109,
                'errMsg' => "红包已过期",
                'money' => 0,
                'receiveMoney' => 0,
                'msg' => "失败"
            ]);
        }
        if ($r1) {

            $total_money = $r1[0]["total_money"];
            $red_num = $r1[0]["red_num"];
            $type = $r1[0]["type"];

            if ($type == 0) {
                $money = $total_money / $red_num;
            } else {
                //获取待领取人数
                // $left=
                $left_num = $r1[0]["red_num"] - $r1[0]["receive_num"];
                $left_money = $r1[0]["total_money"] - $r1[0]["receive_money"];
                $money = randomPart($left_money, $left_num);
            }
            //

            Db::startTrans();
            try {
                $sql3 = array('client_msg_id' => $client_msg_id, 'rcv_id' => $rcv_id, 'nick_name' => $nick_name, 'face_url' => $face_url, 'money' => $money, 'status' => 0, 'create_at' => $time);
                $r2 = Db::name('imext_redpacket_receive')->insertGetId($sql3);

                //增加接受者钱
                $r3 = Db::name('imext_user')->where(["user_id" => $in_user_id])->update(['money' => Db::raw('money + ' . $money)]);

                //
                $r4 = Db::name('imext_redpacket_send')->where(["client_msg_id" => $client_msg_id])->update(['receive_money' => Db::raw('receive_money + ' . $money), 'receive_num' => Db::raw('receive_num + ' . 1)]);
                //如果是最后一个红包，设置状态为已领取完
                if ($r1[0]["red_num"] - $r1[0]["receive_num"] == 1) {
                    $r6 = Db::name('imext_redpacket_send')->where("client_msg_id", $client_msg_id)->update(['status' => 1]);
                }

                $r5 = Db::name('imext_redpacket_send')->where("client_msg_id", $client_msg_id)->select()->toArray();
                Db::commit();
                if ($r5) {
                    $receive_money = $r5[0]["receive_money"];
                }
                if ($r3 == -1) {
                    // $Jurisdiction->admin_record($store_id, $operator, '收红包ID：'.$client_msg_id.' 的信息失败',2,1,0,$operator_id);
                    $Log_content = __METHOD__ . '->' . __LINE__ . ' 收红包失败！参数:' . json_encode($sql1);
                    Logger::Log($Log_content);
                    $message = Lang('label.2');
                    // return output(109,$message);
                    return json([
                        'code' => 200,
                        'errCode' => 109,
                        'errMsg' => "收红包失败",
                        'msg' => "失败"
                    ]);
                } else {
                    // $Jurisdiction->admin_record($store_id, $operator, '收红包ID：'.$client_msg_id.' 的信息',2,1,0,$operator_id);
                    $Log_content = __METHOD__ . '->' . __LINE__ . ' 收红包成功！';
                    Logger::Log($Log_content);
                    $message = $money;
                    // return output(200,$message);
                    return json([
                        'code' => 200,
                        'errCode' => 200,
                        'errMsg' => "收红包成功",
                        'money' => $money,
                        'receiveMoney' => $receive_money,
                        'msg' => "成功"
                    ]);
                }
                //

            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $Log_content = $e->getMessage();
                Logger::Log($Log_content);
                $message = Lang('Busy network');
                // return output(ERROR_CODE_CZSB,$message);
                return json([
                    'code' => 200,
                    'errCode' => 109,
                    'errMsg' => $Log_content,
                    'money' => 0,
                    'receiveMoney' => 0,
                    'msg' => "失败"
                ]);
            }
        } else {
            return json([
                'code' => 200,
                'errCode' => 109,
                'errMsg' => "红包不存在或已接收取",
                'money' => 0,
                'receiveMoney' => 0,
                'msg' => "失败"
            ]);
        }
    }
    #[Route('POST', '$')]
    // 拒绝红包
    public function refuse()
    {


        $client_msg_id = addslashes(trim($this->request->param('clientMsgID'))); // 标题
        $rcv_id = addslashes(trim($this->request->param('rcvID'))); // 标题

        // $Jurisdiction = new Jurisdiction();
        // $operator_id = cache($access_id.'admin_id');
        // $operator = cache($access_id.'admin_name');

        $time = date("Y-m-d H:i:s");


        $remark = "";

        if ($client_msg_id == '') {
            $Log_content = __METHOD__ . '->' . __LINE__ . ' client_msg_id不能为空！';
            Logger::Log($Log_content);
            $message = Lang('label.0');

            return json([
                'code' => -99,
                'errCode' => -99,
                'errMsg' => "client_msg_id不能为空！",
                'msg' => ""
            ]);
        }

        if ($client_msg_id != '' && $client_msg_id != 0) {
            $r0 = Db::name('imext_redpacket_send')->where("client_msg_id", $client_msg_id)->select()->toArray();

            if ($r0) {
                Db::startTrans();
                try {
                    $total_money = $r0[0]["total_money"];
                    $red_num = $r0[0]["red_num"];
                    $money = $total_money / $red_num;

                    $out_user_id = $r0[0]["send_id"];

                    $sql1 = array('client_msg_id' => $client_msg_id, 'rcv_id' => $rcv_id, 'money' => $money, 'status' => -1, 'create_at' => $time, 'remark' => $remark);
                    $r1 = Db::name('imext_redpacket_receive')->insertGetId($sql1);


                    //如果是单个红包拒收后增加发送者钱
                    if ($red_num == 1) {
                        $r1 = Db::name('imext_user')->where(["user_id" => $out_user_id])->update(['money' => Db::raw('money + ' . $money)]);
                        $r1 = Db::name('imext_redpacket_send')->where(["client_msg_id" => $client_msg_id])->update(['status' => 2]);
                    }

                    $r2 = Db::name('imext_redpacket_send')->where(["client_msg_id" => $client_msg_id])->update(['status' => 2]);

                    Db::commit();

                    if ($r1 == -1) {
                        // $Jurisdiction->admin_record($store_id, $operator, '修改了二维码管理ID：'.$client_msg_id.' 的信息失败',2,1,0,$operator_id);
                        $Log_content = __METHOD__ . '->' . __LINE__ . ' 修改失败！参数:';
                        Logger::Log($Log_content);
                        $message = Lang('label.2');
                        //return output(109,$message);
                        return json([
                            'code' => 200,
                            'errCode' => 109,
                            'errMsg' => "拒收红包失败",
                            'money' => 0,
                            'receiveMoney' => 0,
                            'msg' => "失败"
                        ]);
                    } else {
                        // $Jurisdiction->admin_record($store_id, $operator, '修改了二维码管理ID：'.$client_msg_id.' 的信息',2,1,0,$operator_id);
                        $Log_content = __METHOD__ . '->' . __LINE__ . ' 修改成功！';
                        Logger::Log($Log_content);
                        $message = "成功";
                        // return output(200,$message);
                        return json([
                            'code' => 200,
                            'errCode' => 200,
                            'errMsg' => "拒收红包成功",
                            'money' => 0,
                            'receiveMoney' => $money,
                            'msg' => "成功"
                        ]);
                    }
                    //

                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $Log_content = $e->getMessage();
                    Logger::Log($Log_content);
                    $message = Lang('Busy network');
                    return output(ERROR_CODE_CZSB, $message);
                }
            } else {
            }
        }
    }

    /**
     * 根据红包编号获取详细信息
     */
    #[Route('GET', ':id')]
    // #[PreAuthorize('hasPermi','imext:video:query')]
    public function getInfo($id)
    {
        $r = [
            'data' => $this->model->find($id)
        ];
        $this->success($r);
    }

    /**
     * 新增红包
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
     * 修改红包
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
     * 删除红包
     */
    #[Route('DELETE', ':id')]
    // #[PreAuthorize('hasPermi','imext:video:remove')]
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
     * 根据红包编号获取详细信息
     */
    #[Route('GET', '$')]
    // #[PreAuthorize('hasPermi','imext:video:query')]
    public function batchCheck()
    {
        $r1 = Db::name('imext_redpacket_send')->where([["status",'=', 0],['create_time','<=',time() - 24*3600]])->select()->toArray();
        Logger::Log('检查过期红包：'. count($r1));
        //如果红包创建超过24小时，自动退回未接收的金额
        if ($r1) {
            foreach ($r1 as $key => $value) {
                Db::startTrans();
                try {
                    $total_money = $value["total_money"];
                    $red_num = $value["red_num"];
                    $receive_num = $value["receive_num"];
                    $receive_money = $value["receive_money"];
                    $left_num = $red_num - $receive_num;
                    $left_money = $total_money - $receive_money;

                    $out_user_id = $value["send_id"];

                    //更新红包状态
                    $r2 = Db::name('imext_redpacket_send')->where(["client_msg_id" => $value["client_msg_id"]])->update(['status' => 2]);
                    //增加发送者钱
                    if ($left_money > 0) {
                        $r1 = Db::name('imext_user')->where(["user_id" => $out_user_id])->update(['money' => Db::raw('money + ' . $left_money)]);
                    }

                    Db::commit();
                    Logger::Log('红包ID：'.$value["client_msg_id"].' 过期，退回金额：'.$left_money .'发送者：'.$out_user_id);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $Log_content = $e->getMessage();
                    Logger::Log($Log_content);
                }
            }
        }
        $this->success(['count' => count($r1)]);
    }
}
