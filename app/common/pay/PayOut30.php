<?php
namespace app\common\api;

use app\common\LaiKeLogUtils;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

//G-01888番茄境外资金盘 通道B
//资金提现通道

class PayOut30
{
    private static $id = '9089';
    private static $key = 'MTunSzK0uMidacrSew7OgRyUOUOlIHmwhhrsdaKRufEDCGMTwZJp1ds4S9Etdazg';
    private static $clientIp ='8.210.221.104';
    private static $notifyUrl ="https://api.mytomato.vip/index.php/payoutcallback";
    
    //订单号，银行名称，支行名称，用户名称，卡号。金额。卡类型
    public static function recharge($outTradeNo,$bankname,$subbranch,$accountname,$cardnumber,$amount)
    {
        // $myurl = parse_url($_SERVER['HTTP_REFERER']);
        // $base_url = $myurl['scheme'] . '://' . $myurl['host'] . '/';
        $cardtype=0;
        $channel="";
        
        LaiKeLogUtils::lktLog('开始发送，PayOut30 ' . $outTradeNo);
        
        
        //mchid=&out_trade_no=&money=&notifyurl=&bankname=&subbranch=&accountname=&cardnumber=
        //$params = "mchid=". self::$id ."&out_trade_no=" . $outTradeNo . "&money=" . $amount . "&notifyurl=" . self::$notifyUrl . "&bankname=".$bankname."&subbranch=".$subbranch."&accountname=".$accountname."&cardnumber=".$cardnumber;
       
         
        $params = [
            'mchid'      => self::$id,
            'out_trade_no'  => $outTradeNo,
            'money'     =>   $amount,
            'bankname' => $bankname,
            'subbranch'    => $subbranch,
            'accountname'    =>$accountname,
            'cardnumber'       =>$cardnumber,
            'notifyurl'  => self::$notifyUrl.'/'.$outTradeNo, //地址里带入订单编号
        ];
        
        //LaiKeLogUtils::lktLog('发送数据='. json_encode($params));
        self::Log('开始发送，PayOut30= ' . json_encode($params));
         
        $sign = self::sign($params);
        $params["cardtype"] = $cardtype; //cardtype= 0 人民币银行卡 /6 支付宝 
        $params["sign"] = $sign;
        
        self::Log('最终发送数据='.json_encode($params));
        // LaiKeLogUtils::lktLog('发送数据2='.json_encode($params));
        
        $url = 'https://shapi.shilianpay666.top/v1/dfapi/add';
        $result = self::request($params,$url);
        
        LaiKeLogUtils::lktLog('开始发送，PayOut30 返回：' . json_encode($result) );
        self::Log('PayOut30 返回：' .  json_encode($result));
        if (isset($result['status']) && $result['status'] == 'success') {
            return $result;
        } else {
            return $result;
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
            'form_params' => $params,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
            ]
        ]);
        $result = $response->getBody()->getContents();
        return json_decode($result, 1);
    }
    
     public static function postFormData(array $data,$url) {
        self::Log('最终发送数据=postFormData'); 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        self::Log('最终发送数据end'); 
        if (curl_errno($ch)) {
             self::Log('最终发送数据='.curl_error($ch));
            throw new \RuntimeException('CURL Error: ' . curl_error($ch));
        }
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true) ?? $response
        ];
    }
    
      // 日志
    public static function Log($Log_content)
    {
        $lktlog = new LaiKeLogUtils();
        $lktlog->log("admin/MyRechargeOut.log",$Log_content);
        return;
    }
}