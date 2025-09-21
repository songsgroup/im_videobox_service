<?php
namespace app\controller\admin\monitor;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;

use constants\CacheConstants;

/**
 * Cache
 */
#[Group('admin/monitor/cache')]
class Cache extends \app\BaseController
{
    protected $noNeedLogin = [];
    
    private static $caches = [];

    protected function initialize()
    {
        parent::initialize();
        
        array_push(static::$caches,['cacheKey'=>'','cacheName'=>CacheConstants::LOGIN_TOKEN_KEY,'cacheValue'=>'','remark'=>'用户信息']);
        array_push(static::$caches,['cacheKey'=>'','cacheName'=>CacheConstants::SYS_CONFIG_KEY,'cacheValue'=>'','remark'=>'配置信息']);
        array_push(static::$caches,['cacheKey'=>'','cacheName'=>CacheConstants::SYS_DICT_KEY,'cacheValue'=>'','remark'=>'数据字典']);
        array_push(static::$caches,['cacheKey'=>'','cacheName'=>CacheConstants::CAPTCHA_CODE_KEY,'cacheValue'=>'','remark'=>'验证码']);
        array_push(static::$caches,['cacheKey'=>'','cacheName'=>CacheConstants::REPEAT_SUBMIT_KEY,'cacheValue'=>'','remark'=>'防重提交']);
        array_push(static::$caches,['cacheKey'=>'','cacheName'=>CacheConstants::RATE_LIMIT_KEY,'cacheValue'=>'','remark'=>'限流处理']);
        array_push(static::$caches,['cacheKey'=>'','cacheName'=>CacheConstants::PWD_ERR_CNT_KEY,'cacheValue'=>'','remark'=>'密码错误次数']);
    }

    #[Route('GET','$')]
    #[PreAuthorize('hasPermi','monitor:cache:list')]
    public function getInfo()
    {
        // $redis = \think\facade\Cache::handler();
        $redis = \think\facade\Cache::store('redis')->handler();
        
        $info = $redis->info();
        $dbSize = $redis->dbSize();
        $commandStats = $redis->info('commandstats');
        $commandStats = array_map(fn($k,$v)=>[
                'name'=>str_replace('cmdstat_','',$k),
                'value'=>str_replace('calls=','',current(explode(',usec',$v))),
            ],array_keys($commandStats),array_values($commandStats));
        
        $r = [
            'data'=>[
                'info'=>$info,
                'dbSize'=>$dbSize,
                'commandStats'=>$commandStats,
            ],
        ];
        
        $this->success($r);
    }

    #[Route('GET','getNames')]
    #[PreAuthorize('hasPermi','monitor:cache:list')]
    public function getNames()
    {
        // $class = new \ReflectionClass('\constants\CacheConstants');
        // $constants = $class->getConstants();
        
        $r = [
            'data'=>static::$caches,
        ];
        
        $this->success($r);
    }
    
    #[Route('GET','getKeys/:cacheName')]
    #[PreAuthorize('hasPermi','monitor:cache:list')]
    public function getCacheKeys($cacheName)
    {
        $r = [
            'data'=>\think\facade\Cache::getTagItems($cacheName),
        ];
        
        $this->success($r);
    }
    
    #[Route('GET','getValue/:cacheName/:cacheKey')]
    #[PreAuthorize('hasPermi','monitor:cache:list')]
    public function getCacheValue($cacheName,$cacheKey)
    {
        $r = [
            'data'=>[
                    'cacheKey'  => $cacheKey,
                    'cacheName' => $cacheName,
                    'cacheValue'=> json_encode(\think\facade\Cache::get($cacheKey),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
                    'remark'    => '',
                ],
        ];
        
        $this->success($r);
    }
    
    #[Route('DELETE','clearCacheName/:cacheName')]
    #[PreAuthorize('hasPermi','monitor:cache:list')]
    public function clearCacheName($cacheName)
    {
        \think\facade\Cache::tag($cacheName)->clear();
        
        $this->success();
    }
    
    #[PreAuthorize('hasPermi','monitor:cache:list')]
    #[Route('DELETE','clearCacheName/:cacheKey')]
    public function clearCacheKey($cacheKey)
    {
        \think\facade\Cache::delete($cacheKey);
        
        $this->success();
    }
    
    #[Route('DELETE','clearCacheAll')]
    #[PreAuthorize('hasPermi','monitor:cache:list')]
    public function clearCacheAll()
    {
        foreach (static::$caches as $cache){
            \think\facade\Cache::tag($cache['cacheName'])->clear();
        }
        $this->success();
    }
}
