<?php
namespace app\controller\admin\monitor;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;

/**
 * Server
 */
#[Group('admin/monitor/server')]
class Server extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize()
    {
        parent::initialize();
    }
    
    /**
     * index
     *
     * @method (GET)
     */
    #[Route('GET','$')]
    #[PreAuthorize('hasPermi','monitor:server:list')]
    public function index()
    {
        list($cpu,$mem) = self::getCpu();
        $cpu['cpuNum'] = self::getCpuNum();

        $disk = self::getDisk();

        $jvm = self::getVM();

        $sys = [
            'computerIp'  => $this->request->server('SERVER_ADDR'),
            'computerName'=> php_uname('n'),
            'osArch'      => php_uname('m'),
            'osName'      => php_uname('s'),
            'userDir'     => dirname($this->request->server('DOCUMENT_ROOT')).'/',
        ];

        $sysFiles = $disk;

        $r = [
            'data'=>[
                'cpu'=>$cpu,
                'mem'=>$mem,
                'sys'=>$sys,
                'jvm'=>$jvm,
                'sysFiles'=>$sysFiles,
            ],
        ];

        $this->success($r);
    }

    private static function getCpu()
    {
        if (PHP_OS=='Linux') {
            $top = current(explode("\n\n",shell_exec('top -n 1 -b')));
            preg_match('/MiB Mem :\s+(\d+\.\d) total,\s+(\d+\.\d) free,\s+(\d+\.\d) used,\s+\d+\.\d buff\/cache/',$top,$mem);
            preg_match('/%Cpu\(s\):  (\d+\.\d) us,  (\d+\.\d) sy,  \d+\.\d ni, (\d+\.\d) id,  (\d+\.\d) wa,  \d+\.\d hi,  \d+\.\d si,  \d+\.\d st/',$top,$cpu);

            $cpu = [
                'cpuNum'=> 0,
                'free'  => $cpu[3],//id
                'sys'   => $cpu[2],//sy
                'total' => 0,
                'used'  => $cpu[1],//us
                'wait'  => $cpu[4],//wa
            ];

            $mem = [
                'free'  =>sprintf('%.2f',($mem[1]-$mem[3])/1024),//$mem[2]
                'total' =>sprintf('%.2f',$mem[1]/1024),
                'usage' =>sprintf('%02.2f',$mem[1]?100*$mem[3]/$mem[1]:0),
                'used'  =>sprintf('%.2f',$mem[3]/1024),
            ];
        }elseif (PHP_OS=='WINNT') {
            $cpu = [
                'cpuNum'=> 0,
                'free'  => 0,
                'sys'   => 0,
                'total' => 0,
                'used'  => 0,
                'wait'  => 0,
            ];

            $mem = array_values(array_filter(explode(' ',explode("\n",trim(shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize')))[1])));
            $mem = [
                'free'  =>sprintf('%.2f',$mem[0]/1024/1024),
                'total' =>sprintf('%.2f',$mem[1]/1024/1024),
                'usage' =>sprintf('%02.2f',$mem[1]?100*($mem[1]-$mem[0])/$mem[1]:0),
                'used'  =>sprintf('%.2f',($mem[1]-$mem[0])/1024/1024),
            ];
        }
        return [$cpu,$mem];
    }

    private static function getDisk()
    {
        if (PHP_OS=='Linux') {
            $df = shell_exec('df -h');
            preg_match_all('/(?:(\/dev\/\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\d+)%\s+(\S+))/',$df,$disk);
            $disk = array_map(function($fs,$total,$used,$free,$usage,$path){
                    $fs = shell_exec('lsblk -f '.$fs);
                    preg_match('/\S+\s+(\S+)\s+\S+\s+\S+/',explode("\n",$fs)[1],$fs);
                    $fs = $fs[1];
                    return [
                        'dirName'     =>$path,
                        'free'        =>$free,
                        'sysTypeName' =>$fs,
                        'total'       =>$total,
                        'typeName'    =>$path,
                        'usage'       =>$usage,
                        'used'        =>$used,
                    ];
                },$disk[1],$disk[2],$disk[3],$disk[4],$disk[5],$disk[6]);
        }elseif (PHP_OS=='WINNT') {
            $disk = array_map(
                        fn($x)=>array_values(array_filter(explode(' ',trim($x)))),
                        array_slice(explode("\n",trim(shell_exec('wmic logicaldisk get size,freespace,caption'))),1)
                    );
            $disk = array_map(function($x){
                $fs = trim(explode("\n",shell_exec('wmic logicaldisk where "DeviceID=\''.$x[0].'\'" get FileSystem'))[1]);
                $free = sprintf('%.1f',$x[1]/1024/1024/1024).'G';
                $total = sprintf('%.1f',$x[2]/1024/1024/1024).'G';
                return [
                        'dirName'     =>$x[0],
                        'free'        =>$free,
                        'sysTypeName' =>$fs,
                        'total'       =>$total,
                        'typeName'    =>$x[0],
                        'usage'       =>ceil(100*($x[2]-$x[1])/$x[2]),
                        'used'        =>sprintf('%.1f',($x[2]-$x[1])/1024/1024/1024).'G',
                    ];
            },$disk);
        }
        return $disk;
    }

    private static function getCpuNum()
    {
        if (PHP_OS=='Linux') {
            return intval(shell_exec('nproc'));
        }elseif (PHP_OS=='WINNT') {
            return intval(explode("\n",shell_exec('wmic cpu get NumberOfLogicalProcessors'))[1]);
        }
    }

    private static function getVM()
    {
        if (PHP_OS=='Linux') {
            $fpmstatus = fpm_get_status();

            $bin = '/usr/bin/php'.implode('',array_slice(explode('.',PHP_VERSION),0,2));

            $jvm = [
                'free'=> 0,
                'home'=> trim(shell_exec('readlink '.$bin)),
                'inputArgs'=> explode("\n",shell_exec($bin.' --ini'))[0],
                'max'=> $fpmstatus['max-listen-queue'],
                'name'=> 'PHP '.PHP_VERSION,
                'runTime'=> (new \DateTime('@0'))->diff(new \DateTime('@'.$fpmstatus['start-since']))->format('%a天%h时%i分%s秒'),
                'startTime'=> date('Y-m-d H:i:s',$fpmstatus['start-time']),
                'total'=> 0,
                'usage'=> 0,
                'used'=> 0,
                'version'=> PHP_VERSION,
            ];
        }elseif (PHP_OS=='WINNT') {
            $bin = PHP_BINARY??'';

            $starttime = array_values(array_filter(explode(' ',explode("\n",trim(shell_exec('wmic process where "Name=\'php-cgi.exe\'" get CreationDate')))[1])));
            $starttime = substr($starttime[0],0,14);
            $starttime = strtotime($starttime);
            $runtime = time()-$starttime;

            $jvm = [
                'free'=> 0,
                'home'=> $bin,
                'inputArgs'=> explode("\n",shell_exec(dirname($bin).'\php.exe --ini'))[1],
                'max'=> 0,
                'name'=> 'PHP '.PHP_VERSION,
                'runTime'=> (new \DateTime('@0'))->diff(new \DateTime('@'.$runtime))->format('%a天%h时%i分%s秒'),
                'startTime'=> date('Y-m-d H:i:s',$starttime),
                'total'=> 0,
                'usage'=> 0,
                'used'=> 0,
                'version'=> PHP_VERSION,
            ];
        }
        return $jvm;
    }
}
