<?php
namespace app\controller\admin\system;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use app\model\admin\ConfigModel;
use app\service\admin\ConfigService;
use think\helper\Str;

/**
 * Config
 */
#[Group('admin/system/config')]
class Config extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize()
    {
        parent::initialize();
        $this->model = new ConfigModel;
    }
    
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','system:config:list')]
    public function list()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['configName'] = input('configName','');
        $where['configKey'] = input('configKey','');
        $where['configType'] = input('configType','Y');
        $where['params'] = input('params/a',[]);

        $r = ConfigModel::search($where);

        $this->success($r);
    }

    #[Route('POST','export')]
    #[PreAuthorize('hasPermi','system:config:export')]
    public function export()
    {
        $where = [];
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['configName'] = input('configName','');
        $where['configKey'] = input('configKey','');
        $where['configType'] = input('configType','Y');
        $where['params'] = input('params/a',[]);

        $r = ConfigModel::search($where);

        // util.exportExcel($r, "参数数据");
        // TODO:
    }

    #[Route('GET',':configId')]
    #[PreAuthorize('hasPermi','system:config:query')]
    public function getInfo(int $configId)
    {
        $r = [
            'data' => $this->model->find($configId)
        ];
        $this->success($r);
    }

    #[Route('GET','getConfigKey/:configKey')]
    public function getConfigKey(string $configKey)
    {
        $r = ConfigService::selectConfigByKey($configKey);
        $this->success(null,$r);
    }

    #[Route('POST','$')]
    #[PreAuthorize('hasPermi','system:config:add')]
    public function add()
    {
        $config = [
            'configId' => null,
            'configKey' => input('configKey'),
            'configName' => input('configName'),
            'configType' => input('configType'),
            'configValue' => input('configValue'),
            'remark' => input('remark'),
        ];
        if (!ConfigService::checkConfigKeyUnique($config)) {
            $this->error('新增参数' . $config['configName'] . '失败，参数键名已存在');
        }

        $configId = ConfigService::insertConfig($config);
        if (!$configId) {
            $this->error('保存失败');
        }

        $this->success();
    }

    #[Route('PUT','$')]
    #[PreAuthorize('hasPermi','system:config:edit')]
    public function edit()
    {
        $config = [
            'configId' => input('configId/d'),
            'configKey' => input('configKey'),
            'configName' => input('configName'),
            'configType' => input('configType'),
            'configValue' => input('configValue'),
            'remark' => input('remark'),
        ];
        if (!ConfigService::checkConfigKeyUnique($config)) {
            $this->error('新增参数' . $config['configName'] . '失败，参数键名已存在');
        }
        $r = ConfigService::updateConfig($config);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    #[Route('DELETE',':configIds')]
    public function remove($configIds)
    {
        $configIds = explode(',',$configIds);
        try {
            ConfigService::deleteConfigByIds($configIds);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success();
    }
}
