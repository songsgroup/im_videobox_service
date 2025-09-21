<?php

namespace app\service\admin;

use think\facade\Cache;
use constants\CacheConstants;

use app\model\admin\ConfigModel;

/**
 * 参数配置 服务层
 *
 * @author 心衍
 */
class ConfigService
{

    /**
     * 项目启动时，初始化参数到缓存
     */
    public static function init(){
    	$keys = Cache::getTagItems(CacheConstants::SYS_CONFIG_KEY);
    	if (!$keys || !count($keys)) {
	        self::loadingConfigCache();
    	}
    }

    /**
     * 查询参数配置信息
     *
     * @param configId 参数配置ID
     * @return 参数配置信息
     */
    public static function selectConfigById(int $configId):ConfigModel {
    	return ConfigModel::find($configId);
    }

    /**
     * 根据键名查询参数配置信息
     *
     * @param configKey 参数key
     * @return 参数键值
     */
    public static function selectConfigByKey(string $configKey):string {
        $configValue = self::getCache(self::getCacheKey($configKey),'');
        if ($configValue){
            return $configValue;
        }
        $retConfig = ConfigModel::where('config_key',$configKey)->find();
        if ($retConfig) {
        	self::setCache(self::getCacheKey($configKey),$retConfig['configValue']);
        	return $retConfig['configValue'];
        }
        return '';
    }

    /**
     * 获取验证码开关
     *
     * @return true开启，false关闭
     */
    public static function selectCaptchaEnabled():bool{
        $captchaEnabled = self::selectConfigByKey("sys.account.captchaEnabled");
        if (!$captchaEnabled){
            return true;
        }
        return strtolower($captchaEnabled)!=='false';
    }

    /**
     * 查询参数配置列表
     *
     * @param config 参数配置信息
     * @return 参数配置集合
     */
    public static function selectConfigList(array $where=[]):array{
        $r = ConfigModel::search($where);
        return $r['rows'];
    }

    /**
     * 新增参数配置
     *
     * @param config 参数配置信息
     * @return 结果
     */
    public static function insertConfig(array $config):int{
        $config = ConfigModel::create($config);
        if ($config && $config['configId']){
            self::setCache(self::getCacheKey($config['configKey']), $config['configValue']);
        }
        return $config['configId'];
    }

    /**
     * 修改参数配置
     *
     * @param config 参数配置信息
     * @return 结果
     */
    public static function updateConfig(array $config):bool{
        $temp = self::selectConfigById($config['configId']);
        if ($temp['configKey'] !== $config['configKey']) {
            Cache::delete(self::getCacheKey($temp['configKey']));
        }

        $row = ConfigModel::update($config,['config_id'=>$config['configId']]);
        if ($row){
            self::setCache(self::getCacheKey($config['configKey']), $config['configValue']);
        }
        return !!$row;
    }

    /**
     * 批量删除参数信息
     *
     * @param configIds 需要删除的参数ID
     */
    public static function deleteConfigByIds(array $configIds){
    	$configs = ConfigModel::where('config_id','in',$configIds)->select()->toArray();
    	foreach ($configs as $config) {
            if ($config['configType']=='Y'){
                throw new \Exception(sprintf("内置参数【%s】不能删除 ", $config['configKey']));
            }
            Cache::delete(self::getCacheKey($config['configKey']));
	        // ConfigModel::destroy($config['configId']);
    	}
        ConfigModel::destroy($configIds);
    }

    /**
     * 加载参数缓存数据
     */
    public static function loadingConfigCache(){
        $configsList = self::selectConfigList((new ConfigModel())->toArray());
        foreach ($configsList as $config){
            self::setCache(self::getCacheKey($config['configKey']), $config['configValue']);
        }
    }

    /**
     * 清空参数缓存数据
     */
    public static function clearConfigCache(){
        $keys = Cache::getTagItems(CacheConstants::SYS_CONFIG_KEY);//Cache::keys(CacheConstants::SYS_CONFIG_KEY . "*");
        Cache::delete($keys);
    	Cache::tag(CacheConstants::SYS_CONFIG_KEY)->clear();
    }

    /**
     * 重置参数缓存数据
     */
    public static function resetConfigCache(){
        self::clearConfigCache();
        self::loadingConfigCache();
    }

    /**
     * 校验参数键名是否唯一
     *
     * @param config 参数配置信息
     * @return 结果
     */
    public static function checkConfigKeyUnique(array $config):bool {
        $configId = $config['configId']?:-1;
        $info = ConfigModel::where('config_key',$config['configKey'])->find();
        if ($info && intval($info['configId']) != intval($configId)){
            return false;
        }
        return true;
    }

    /**
     * 设置cache key
     *
     * @param configKey 参数键
     * @return 缓存键key
     */
    private static function getCacheKey(String $configKey):string {
        return CacheConstants::SYS_CONFIG_KEY . $configKey;
    }

    /**
     * 读取cache
     *
     * @param configKey 参数键
     * @return 结果
     */
    private static function getCache(String $configKey,$configValue):string {
        return Cache::get(self::getCacheKey($configKey))??'';
    }

    /**
     * 设置cache
     *
     * @param configKey 参数键
     * @param configValue 参数值
     * @return 结果
     */
    private static function setCache(String $configKey,$configValue):bool {
        return Cache::tag(CacheConstants::SYS_CONFIG_KEY)->set(self::getCacheKey($configKey),$configValue);
    }
}
ConfigService::init();