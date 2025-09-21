<?php
namespace app\common\pay;

 
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

use app\utils\Logger;

//跨境资金盘 通道
class PayIn33
{
    private static $id = '44446706128651013';
    private static $key = '36d6f6b1928d4ee59b957106118b8aaf';
    private static $clientIp ='8.210.221.104';
    private static $notifyUrl ="https://test.wfjoma.com/imext/money/payincallback/apply";
    
    public static function recharge($outTradeNo,$amount,$channel)
    {
        // $myurl = parse_url($_SERVER['HTTP_REFERER']);
        // $base_url = $myurl['scheme'] . '://' . $myurl['host'] . '/';
        
        Logger::Log('开始发送，Pay33 ' . $outTradeNo);
        
        if($amount < 100){
            $amount = 100; //通道有最小限制
        }
        
        $params = [
            'mchId'      => self::$id,
            'wayCode'    => $channel,
            'subject'    => '统一下单',
            'outTradeNo' => $outTradeNo,
            'amount'     =>  100 * $amount,
            'clientIp'   => self::$clientIp,
            'notifyUrl'  => self::$notifyUrl.'/'.$outTradeNo, //地址里带入订单编号
            'reqTime'    => time()*1000
        ];
        Logger::Log('发送数据='.json_encode($params));
        
        $sign = self::sign($params);
        $params["sign"] = $sign;
        
        // Logger::Log('发送数据2='.json_encode($params));
        
        $url = 'https://hongtu.jkosiuwn.xyz/api/createorder';
        $result = self::request($params,$url);
        self::Log('开始发送，Pay33 ：' .  json_encode($params));
        Logger::Log('开始发送，Pay33 ' . $result['code']  );
        self::Log('返回结果，Pay33 返回：' .  json_encode($result));
        if (isset($result['code']) && $result['code'] == 0) {
             
            return ['status' => 1, 'h5_url' => $result['data']['payUrl']];
        } else {
            return ['status' => 0, 'error' => $result];
        }
    }

    public static function notify($data) {
        $sign = self::sign($data);
        if ($sign != $data['sign']) {
            return false;
        }
        if (intval($data['state']) != 2) {
            return false;
        }
        return ['order_id' => $data['outTradeNo'], 'echo' => 'SUCCESS'];
    }

    private static function sign($data) {
        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            if ($key == 'sign' || $val == '') continue;
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $ss = $md5str . "key=" . self::$key;
        $sign = strtolower(md5($ss));
        return $sign;
    }

    private static function request(array $params,$url)
    {
        $client = new Client();
        $response = $client->post($url, [
            RequestOptions::JSON => $params
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