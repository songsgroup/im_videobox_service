<?php
namespace helper;

class BaseN
{
    /**
     * 十进制转36进制
     * 
     **/
    public static function dec2hexatriges($n) {
        $n = intval($n);
        if ($n <= 0)return'';
        $r = '';
        do {
            $key = $n % 36;
            $r= chr($key+($key<10?48:55)).$r;
            $n = floor(($n - $key) / 36);
        } while ($n > 0);
        return $r;
    }
    
    /**
     * 36进制转十进制
     * 
     **/
    public static function hexatriges2dec($s){
    	$s = strtoupper($s);
        $len=strlen($s);
        $r=0;
        for($i=0;$i<$len;$i++){
            $index=(fn($x)=>$x-($x<60?48:55))(ord($s[$i]));
            $r+=$index*pow(36,$len-$i-1);
        }
        return $r;
    }
    
    /**
     * 时间戳转36进制
     * 
     **/
    public static function sten36($t=null){
    	$t = $t??time();
        return self::dec2hexatriges($t-strtotime('2000-01'));
    }
    
    /**
     * 36进制转时间戳
     * 
     **/
    public static function stde36($s){
        $r = self::hexatriges2dec($s)+strtotime('2000-01');
        return $r;
    }
}
