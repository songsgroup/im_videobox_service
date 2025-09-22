<?php

namespace app\controller\imext\immsg;

use GuzzleHttp\Client;

use think\annotation\route\Route;
use think\annotation\route\Group;

use app\model\imext\VideoModel;
use app\model\imext\ImExtUserModel;
use app\model\imext\ImGroupConfigModel;

use think\facade\Db;   // 引入 Db 门面
use app\utils\Logger;

/**
 * Notice
 */
#[Group('imext/immsg/autoimmsg')]
class AutoImMsg extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected $imUrl = "http://47.83.135.58:21002/api";
    protected $senderNickname = "番茄电商";
    protected $senderFaceURL = "http://www.head.com";

    protected function initialize()
    {
        parent::initialize();
        $this->model = new VideoModel;
    }

    /**
     * 获取通知公告列表
     */
    #[Route('GET', 'automsg')]
    // 定时任务里执行
    function autoMsg()
    {

        $groups = ImGroupConfigModel::select()->toArray();

        //
        foreach ($groups as $group) {
            // $group 是单条数据的数组

            $sendId =  $group["sendId"] . "";
            $sendName =  $group["sendName"] . "";
            $groupId = $group["groupId"] . "";
            $red_num = $group["redNum"];
            $money = $group["money"];
            $remark = $group["remark"];
            //
            $this->doSendMsg($sendId, $sendName, $groupId, $red_num,  $money,  $remark);
        }
    }

    /**
     * 定时发红包
     */
    #[Route('GET', 'send')]
    public function send()
    {

        //


    }

    private function doSendMsg($sendId, $sendName, $groupId, $red_num, $money,  $remark)
    {
        $url = $this->imUrl . '/auth/get_admin_token';
        $resToken = $this->request(["secret" => "openIM123", "userID" => "imAdmin"], $url, null);
        $token = $resToken["data"]["token"];
        // print($resToken);

        Db::startTrans();


        //    {
        //         "clientMsgID": "c177a72eb22e5a3afa71bf0ff25c5687",
        //         "createTime": 1758453488427,
        //         "sendTime": 1758453488427,
        //         "sessionType": 0,
        //         "sendID": "1894780926",
        //         "msgFrom": 100,
        //         "contentType": 110,
        //         "senderPlatformID": 2,
        //         "senderNickname": "13844444444",
        //         "senderFaceUrl": "http://cs.tomaoto3.cc/object/1894780926/1755698559723png",
        //         "seq": 0,
        //         "isRead": false,
        //         "status": 1,
        //         "customElem": {
        //             "data": "{\"number\":\"1\",\"totalMoney\":\"1\",\"sendID\":\"1894780926\",\"nickname\":\"13844444444\",\"faceURL\":\"http://cs.tomaoto3.cc/object/1894780926/1755698559723png\",\"type\":\"REDPACKET\"}",
        //             "description": "SEND",
        //             "extension": "REDPACKET"
        //         }
        //     }

        $newMsgid = $this->generateClientMsgID();
        $newconversationID= $this->generateClientMsgID();
        try {
            $param = [
                "clientMsgID" =>  $newMsgid,
                "sendID" => $sendId . "",
                "recvID" => "", // "1894780926",
                "groupID" => $groupId . "",
                "senderNickname" => $this->senderNickname, // "openIMAdmin-Gordon",
                "senderFaceURL" => $this->senderFaceURL,
                "senderPlatformID" => 1,
                "content" => [
                    "data" =>  "{ \"createRedPacketClientMsgID\":\" $newMsgid\", \"groupID\":\"$groupId\",\"conversationID\":\"$$groupId\",\"number\":\"$red_num\",\"totalMoney\":\"$money\",\"sendID\":\"$sendId\",\"nickname\":\"$sendName\",\"faceURL\":\"http://cs.tomaoto3.cc/object/1894780926/1755698559723png\",\"type\":\"REDPACKET\"}",
                    "description" => "SEND",
                    "extension" => "REDPACKET"
                ],
                "contentType" => 110,
                "sessionType" => 3, // 1:个人，3 群消息
                "isOnlineOnly" => false,
                "notOfflinePush" => false,
                // "sendTime": 1695212630740,
                "offlinePushInfo" => [
                    "title" => "你收到了一个红包！",
                    "desc" => "",
                    "ex" => "",
                    "iOSPushSound" => "default",
                    "iOSBadgeCount" => true
                ],              

            ];
            $url = $this->imUrl . '/msg/send_msg';
            $resSend = $this->request($param, $url, $token);
            //print($resSend);

            $clientMsgId = $resSend["data"]["clientMsgID"];

            $imExtUser =  ImExtUserModel::Where("user_id", $sendId)->find();
            if ($imExtUser) {
                $userMoney = $imExtUser["money"];
            }

            $sql1 = array('client_msg_id' => $clientMsgId, 'send_id' => $sendId,   'nick_name' => $this->senderNickname, 'face_url' => $this->senderFaceURL, 'red_num' => $red_num, 'total_money' => $money, 'type' => "", 'status' => 0, 'create_time' => date('Y-m-d H:i:s'), 'remark' => $remark, "receive_money" => 0, 'receive_num' => 0);

            $r1 = Db::name('imext_redpacket_send')->insertGetId($sql1);

            //减少发送者钱
            $r1 = Db::name('imext_user')->where(["user_id" => $sendId])->update(['money' => Db::raw('money -' . $money)]);

            //记录流水
            if ($r1 > 0) {

                $Rorders = array(
                    'user_id' => $sendId,
                    'money' => $money,
                    'money_front' => $userMoney,
                    'type' => "支出",
                    'data_id' => $clientMsgId,
                    'status' => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                    'remark' => "播放扣款"
                );
                $r1 = Db::name('imext_money_record')->insertGetId($Rorders);

                Db::commit();

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

                $Log_content = __METHOD__ . '->' . __LINE__ . ' 添加失败！参数:' . json_encode($sql1);
                Logger::Log($Log_content);
                $message = Lang('label.5');
                // return output(109,$message);
                return json([
                    'code' => -99,
                    'errCode' => -99,
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
                'code' => -99,
                'errCode' => -99,
                'errMsg' => $message,
                'msg' => "失败"
            ]);
        }
    }


    //发送POST请求
    private static function request(array $params, $url, $token)
    {
        $client = new Client();
        $response = $client->post($url, [
            // 'form_params' => $params,
            'json' => $params,
            'headers' => [
                // 'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
                // 'Content-Type' => 'application/json'
                'operationid' => AutoImMsg::getUid(),
                'token' => $token ? $token : '',
            ]
        ]);
        $result = $response->getBody()->getContents();
        return json_decode($result, 1);
    }

    //生成UUID
    public static function getUid()
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-'
            . substr($chars, 8, 4) . '-'
            . substr($chars, 12, 4) . '-'
            . substr($chars, 16, 4) . '-'
            . substr($chars, 20, 12);
        return $uuid;
    }



    function generateClientMsgID(): string
    {
        // 时间戳（毫秒级）
        $milliseconds = (int) round(microtime(true) * 1000);

        // 随机字节（例如 8 字节）
        $randomBytes = random_bytes(8);

        // 可以把时间戳 + 随机字节合起来，再做 md5 或 sha1 哈希
        $raw = $milliseconds . bin2hex($randomBytes);

        // 哈希生成定长十六进制字符串
        $clientMsgID = md5($raw);

        // 返回下划线分隔或者直接全部小写十六进制
        return $clientMsgID;
    }

    // 使用示例


}
