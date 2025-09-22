<?php
/*
 * 此文件用于验证短信服务API接口，供开发时参考
 * 执行验证前请确保文件为utf-8编码，并替换相应参数为您自己的信息，并取消相关调用的注释
 * 建议验证前先执行Test.php验证PHP环境
 *
 * 2017/11/30
 */

// namespace Aliyun\DySDKLite\Sms;

// require_once "../SignatureHelper.php";
require_once('../app/common/aliyun-dysms-php-sdk-lite/SignatureHelper.php');

// use Aliyun\DySDKLite\SignatureHelper;

use app\common\LaiKeLogUtils;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;


/**
 * 签名助手 2017/11/19
 *
 * Class SignatureHelper
 */

/**
 * 发送短信
 */
//function sendSms($code,$mobile) {
function sendSms_bak($accessKeyId,$accessKeySecret,$SignName,$PhoneNumbers,$TemplateCode,$TemplateParam) {
    $params = array ();

    // *** 需用户填写部分 ***

    // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    $accessKeyId = $accessKeyId;
    $accessKeySecret = $accessKeySecret;
    // fixme 必填: 短信接收号码
//    $params["PhoneNumbers"] = $mobile;
    $params["PhoneNumbers"] = $PhoneNumbers;

    // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
//    $params["SignName"] = "来客推";
    $params["SignName"] = $SignName;

    // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
//    $params["TemplateCode"] = "SMS_148861863";
    $params["TemplateCode"] = $TemplateCode;

    // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
    $params['TemplateParam'] = $TemplateParam;
//    $params['TemplateParam'] = Array (
//        "code" => $code,
//        // "code" => "12345",
//        // "product" => "阿里通信"
//    );

    // fixme 可选: 设置发送短信流水号
    $params['OutId'] = "12345";

    // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
    $params['SmsUpExtendCode'] = "1234567";


    // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
    if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
        $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
    }

    // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
    $helper = new SignatureHelper();

    // 此处可能会抛出异常，注意catch
    $content = $helper->request(
        $accessKeyId,
        $accessKeySecret,
        "dysmsapi.aliyuncs.com",
        array_merge($params, array(
            "RegionId" => "cn-hangzhou",
            "Action" => "SendSms",
            "Version" => "2017-05-25",
        ))
        // fixme 选填: 启用https
        // ,true
    );

    return $content;
}


function sendSms($accessKeyId,$accessKeySecret,$SignName,$PhoneNumbers,$TemplateCode,$TemplateParam) {
    
    LaiKeLogUtils::lktLog('调用sendSms...');
    LaiKeLogUtils::lktLog('accessKeyId=['.$accessKeyId.']');
    LaiKeLogUtils::lktLog('accessKeySecret=['.$accessKeySecret.']');
    LaiKeLogUtils::lktLog('SignName=['.$SignName.']');
    LaiKeLogUtils::lktLog('PhoneNumbers=['.$PhoneNumbers.']');
    LaiKeLogUtils::lktLog('TemplateCode=['.$TemplateCode.']');
    LaiKeLogUtils::lktLog('TemplateParam=['.json_encode($TemplateParam).']');
    
    $countryArea = [];
    $countryArea['86'] = '中国OTP通道2';
    $countryArea['11'] = '美国OTP通道1';
    $countryArea['81'] = '日本OTP通道1';
    $countryArea['82'] = '韩国OTP通道3';
    $countryArea['84'] = '越南OTP通道6';
    $countryArea['855'] = '柬埔寨OTP通道1';
    $countryArea['66'] = '泰国OTP通道3';
    $countryArea['856'] = '老挝OTP通道1';
    $countryArea['91'] = '印度OTP通道1';
    $countryArea['7'] = '俄罗斯OTP通道1';
    $countryArea['49'] = '德国OTP通道1';
    $countryArea['44'] = '英国OTP通道1';
    $countryArea['33'] = '法国OTP通道1';
    $countryArea['39'] = '意大利OTP通道1';
    
    $phoneArr = explode('-',$PhoneNumbers);
    if(count($phoneArr) == 1){ //容错处理
        $phoneArr[0] = '86';
        $phoneArr[1] = $PhoneNumbers;
    }
    
    $countryNo = '86';
    
    if(isset($countryArea[$phoneArr[0]])){
        $countryNo = $countryArea[$phoneArr[0]];
    }
    
    $url = 'http://haosms.net/client/extra_api/otp_msg';

    $params = array();
    $params['api_key'] = 'kvoDQa6z2HjyVpEu';
    $params['phone'] = $phoneArr[1];
    $params['content'] = $TemplateParam['code'];
    $params['platform'] = $countryNo;
    
    LaiKeLogUtils::lktLog('发送params=['.json_encode($params).']');
    
    $content = sms_request($params, $url);
    
    $result = [];
    
    if($content['result']=='成功'){
        LaiKeLogUtils::lktLog('['.$PhoneNumbers.']发送成功');
        $result['Code'] = 'OK';
    }else{
        $result['Code'] = 'isv.BUSINESS_LIMIT_CONTROL';
    }
    return $result;
}

function sms_request(array $params, $url) {
    $client = new Client();
    $response = $client->post($url, [
        'form_params' => $params
    ]);
    $result = $response->getBody()->getContents();
    // LaiKeLogUtils::lktLog('返回result=['.$result.']');
    return json_decode($result, 1);
}

// ini_set("display_errors", "on"); // 显示错误提示，仅用于测试时排查问题
// // error_reporting(E_ALL); // 显示所有错误提示，仅用于测试时排查问题
// set_time_limit(0); // 防止脚本超时，仅用于测试使用，生产环境请按实际情况设置
// header("Content-Type: text/plain; charset=utf-8"); // 输出为utf-8的文本格式，仅用于测试

// 验证发送短信(SendSms)接口
