<?php
namespace app\common\pay;
use app\utils\Logger;

 
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

//G-01888番茄境外资金盘 通道B
class PayIn32
{
    private static $id = '20000022';
    private static $key = 'NODMW1VOHQAZ22QTUKM1V335JW9VMLR8DFYEUQLBRFCASE4H4SXVNOUJMYCO3A76QARQFKA00I6J4QDZZSTF2T701EJLGDYOX4SFRTRZMF1KKB3HWVYE4DPH5BZ8YXAC';
    private static $clientIp ='8.210.221.104';
    private static $notifyUrl ="https://test.wfjoma.com/imext/money/payincallback/apply";
    
    public static function recharge($outTradeNo,$amount,$channel)
    {
        // $myurl = parse_url($_SERVER['HTTP_REFERER']);
        // $base_url = $myurl['scheme'] . '://' . $myurl['host'] . '/';
        
       Logger::Log('开始发送，Pay32 ' . $outTradeNo);
        
        if($amount < 100){
            $amount = 100; //通道有最小限制
        }
        
        $params = [
            'mchId'      => self::$id,
            'productId'  => $channel,
            'mchOrderNo' => $outTradeNo,
            'amount'     =>  100 * $amount,
            'currency'    => 'cny',
            'notifyUrl'  => self::$notifyUrl.'/'.$outTradeNo, //地址里带入订单编号
            'subject'    => '用户充值',
            'body'       => '用户充值',
            'reqTime'    => date("YmdHis"),
            'version'    => '1.0'
        ];
       Logger::Log('发送数据='.json_encode($params));
       
        $sign = self::sign($params);
        $params["sign"] = $sign;
        self::Log('发送数据='.json_encode($params));
        //Logger::Log('发送数据2='.json_encode($params));
        
        $url = 'https://gr5q8ygo.zhuanqianpay.net/api/pay/startOrder';
        $result = self::request($params,$url);
        
       Logger::Log('开始发送，Pay32 返回：' . json_encode($result) );
        self::Log('开始发送，Pay32 返回：' .  json_encode($result));
        if (isset($result['retCode']) && $result['retCode'] == 0) {
            return ['status' => 1, 'h5_url' => $result['payJumpUrl']];
        } else {
            return ['status' => 0, 'error' => $result['retMsg']];
        }
        
    }

    public static function notify($data) {
        $sign = self::sign($data);
        if ($sign != $data['sign']) {
            return false;
        }
        if (intval($data['status']) != 2) {
            return false;
        }
        return ['order_id' => $data['mchOrderNo'], 'echo' => 'success'];
    }

    private static function sign($data) {
        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            if ($key == 'sign' || $val == '') continue;
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $ss = $md5str . "key=" . self::$key;
        $sign = strtoupper(md5($ss));
        return $sign;
    }

    private static function request(array $params,$url)
    {
        $client = new Client();
        $response = $client->post($url, [
            'form_params' => $params
        ]);
        $result = $response->getBody()->getContents();
        return json_decode($result, 1);
    }
      // 日志
    public static function Log($Log_content)
    {
        
        return;
    }
}