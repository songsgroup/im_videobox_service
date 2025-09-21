<?php
namespace app\utils;

/**
 * LKT 日志输出帮助类
 * 方法一： Logger::lktLog("日志内容");
 * 方法二：
 *         $lktlog = new Logger("app/order.log");
 *         $lktlog->customerLog("日志内容");
 * 方法三：
 *         Logger::log("app/xx.log","日志内容");
 */
class Logger
{

    /**
     * 日志记录内容
     */
    const LOGROOTPATH = '../runtime/log/';

    /**
     * 通用日志输出
     */
    const COMMONLOGPATH = "../runtime/log/common/lkt.log";

    /**
     * 限定日志大小,单位M
     */
    const LOGSIZE = 3;

    /**
     * 限定日志数量
     */
    const LOGNUM = 5;

    /**
     * 构造函数
     * @param $logpath   指定./webapp/log/目录下的日志输出，调用 customerLog
     */
    function __construct()
    {
        
    }

    /**
     * 通用日志记录
     */
    public static function Log($msg)
    {	
        try
        {
            self::baseLog(Logger::COMMONLOGPATH, $msg);
        } catch (Exception $e)
        {
            throw $e;
        }
    }

    /**
     * 在指定文件中写入日志
     */
    public static function Log2File($logfilepath, $msg)
    {
        try
        {
            if (empty($logfilepath))
            {
                $logfilepath = Logger::COMMONLOGPATH;
            }
            self::baseLog(Logger::LOGROOTPATH . $logfilepath, $msg);
        } catch (Exception $e)
        {
            throw $e;
        }
    }



    /**
     * 日志记录
     */
    public static function baseLog($path, $msg)
    {   
        $list = array();
        $name = strstr(substr($path,strripos($path,"/")+1),'.',true);
        $url = substr($path,0,strrpos($path,"/"));
        $path = $url.'/'.$name.'/';
        $file_name = $url.'/'.$name.'/1.log';
        
        if(is_dir($path))
        {
            $file_arr = scandir($path);
            if(is_array($file_arr))
            {
                foreach ($file_arr as $key => $value) 
                {   
                    if($value != '.' && $value != '..')
                    {
                        $list[$key]['name'] = strstr($value,'.',true);
                        $list[$key]['file_name'] = $path.$value;
                        $list[$key]['time'] = filemtime($path.$value);
                        $list[$key]['size'] = round(filesize($path.$value)/1024/1024,2);
                    } 
                }
                $sort = array_column($list, 'time');
                array_multisort($sort, SORT_ASC, $list);

                //判断文件数量是否超限
                if(count($list) > Logger::LOGNUM)
                {
                    @unlink($list[0]['file_name']);
                }
                //判断写入文件是否超限
                if (count($list) > 0) 
                {
                    if($list[count($list)-1]['size'] >= Logger::LOGSIZE)
                    {   
                        $num = (int)$list[count($list)-1]['name'] + 1;
                        $file_name = $path.$num.'.log';
                    }
                    else
                    {
                        $file_name = $list[count($list)-1]['file_name'];
                    }
                }
            }
        }
        try
        {
            if (!file_exists(dirname($file_name)))
            {
                mkdir(dirname($file_name), 0777, true);
            }
            $fp = fopen($file_name, 'a+');
            flock($fp, LOCK_EX);
            fwrite($fp, "日期：" . date("Y-m-d H:i:s") . "\r\n" . $msg . "\r\n");
            flock($fp, LOCK_UN);
            fclose($fp);
        } catch (Exception $e)
        {
            throw $e;
        }
    }

}

?>