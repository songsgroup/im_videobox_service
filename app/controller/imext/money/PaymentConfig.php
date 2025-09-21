<?php
namespace app\controller\imext\money;

use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\PaymentConfigModel;

/**
 * 充值配置管理
 */
#[Group('imext/money/paymentconfig')]
class PaymentConfig extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize(){
        parent::initialize();
        $this->model = new PaymentConfigModel;
    }

    /**
     * 获取充值配置列表
     */
    #[Route('GET','list')]
    public function list()
    {
        $where = [];
        // $where['pageNo'] = input('pageNo/d',1);
        // $where['pageSize'] = input('pageSize/d',10);
        

        $r = PaymentConfigModel::search($where);
        $this->success($r);
    }

    /**
     * 获取启用的充值配置列表
     */
    #[Route('GET','enabled')]
    public function enabled()
    {
        $data = PaymentConfigModel::getEnabledList();
        $this->success(['data' => $data]);
    }

    /**
     * 根据ID获取充值配置详情
     */
    #[Route('GET',':id')]
    public function getInfo($id)
    {
        $r = [
            'data' => $this->model->find($id)
        ];
        $this->success($r);
    }

    /**
     * 根据代码获取配置
     */
    #[Route('GET','code/:code')]
    public function getByCode($code)
    {
        $data = PaymentConfigModel::getByCode($code);
        if (!$data) {
            $this->error('配置不存在或已禁用');
        }
        $this->success(['data' => $data]);
    }

    /**
     * 新增充值配置
     */
    #[Route('POST','add')]
    public function add()
    {
        $data = $this->request->param();
        
        // 验证必填字段
        if (empty($data['title'])) {
            $this->error('名称不能为空');
        }
        if (empty($data['code'])) {
            $this->error('代码不能为空');
        }
        
        // 检查代码是否已存在
        if (PaymentConfigModel::checkCodeExists($data['code'])) {
            $this->error('代码已存在');
        }
        
        // 设置默认值
        $data['enabled'] = $data['enabled'] ?? 1;
        $data['ordering'] = $data['ordering'] ?? PaymentConfigModel::getMaxOrdering();
        
        $r = $this->model->create($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success(['id' => $r->id]);
    }

    /**
     * 修改充值配置
     */
    #[Route('POST','update')]
    public function edit()
    {
        $id = input('id/d',0);
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('资源不存在');
        }
        
        $updateData = $this->request->param();
        
        // 如果修改了代码，检查是否重复
        if (isset($updateData['code']) && $updateData['code'] != $data->code) {
            if (PaymentConfigModel::checkCodeExists($updateData['code'], $id)) {
                $this->error('代码已存在');
            }
        }
        
        $r = $data->save($updateData);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除充值配置
     */
    #[Route('DELETE',':id')]
    public function remove(int $id)
    {
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('资源不存在');
        }
        $r = $data->delete();
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 批量删除充值配置
     */
    #[Route('POST','batchDelete')]
    public function batchDelete()
    {
        $ids = input('ids/a',[]);
        if (empty($ids)) {
            $this->error('请选择要删除的记录');
        }
        
        $r = $this->model->whereIn('id', $ids)->delete();
        if (!$r) {
            $this->error('删除失败');
        }
        
        $this->success(['deleted_count' => $r]);
    }

    /**
     * 更新排序
     */
    #[Route('POST','updateOrdering')]
    public function updateOrdering()
    {
        $data = input('data/a',[]);
        if (empty($data)) {
            $this->error('排序数据不能为空');
        }
        
        $r = PaymentConfigModel::batchUpdateOrdering($data);
        if (!$r) {
            $this->error('更新排序失败');
        }
        
        $this->success(['updated_count' => $r]);
    }

    /**
     * 批量更新状态
     */
    #[Route('POST','batchUpdateStatus')]
    public function batchUpdateStatus()
    {
        $ids = input('ids/a',[]);
        $enabled = input('enabled/d',1);
        
        if (empty($ids)) {
            $this->error('请选择要更新的记录');
        }
        
        $r = PaymentConfigModel::batchUpdateStatus($ids, $enabled);
        if (!$r) {
            $this->error('更新状态失败');
        }
        
        $this->success(['updated_count' => $r]);
    }

    /**
     * 切换状态
     */
    #[Route('POST','toggleStatus')]
    public function toggleStatus()
    {
        $id = input('id/d',0);
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('资源不存在');
        }
        
        $newStatus = $data->enabled ? 0 : 1;
        $r = $data->save(['enabled' => $newStatus]);
        if (!$r) {
            $this->error('状态切换失败');
        }
        
        $this->success(['enabled' => $newStatus]);
    }

    /**
     * 获取统计信息
     */
    #[Route('GET','statistics')]
    public function statistics()
    {
        $r = PaymentConfigModel::getStatistics();
        $this->success($r);
    }

    /**
     * 检查代码是否存在
     */
    #[Route('POST','checkCode')]
    public function checkCode()
    {
        $code = input('code','');
        $excludeId = input('exclude_id/d',0);
        
        if (!$code) {
            $this->error('代码不能为空');
        }
        
        $exists = PaymentConfigModel::checkCodeExists($code, $excludeId ?: null);
        $this->success(['exists' => $exists]);
    }

    /**
     * 导出充值配置
     */
    #[Route('POST','export')]
    public function export()
    {
        $where = [];
        $where['title'] = input('title','');
        $where['code'] = input('code','');
        $where['enabled'] = input('enabled','');
        
        // 获取所有数据（不分页）
        $data = PaymentConfigModel::search($where);
        
        // 这里可以实现Excel导出逻辑
        // 暂时返回数据
        $this->success($data);
    }

    /**
     * 复制配置
     */
    #[Route('POST','copy')]
    public function copy()
    {
        $id = input('id/d',0);
        $newCode = input('new_code','');
        $newTitle = input('new_title','');
        
        if (!$id) {
            $this->error('请选择要复制的配置');
        }
        if (!$newCode) {
            $this->error('新代码不能为空');
        }
        if (!$newTitle) {
            $this->error('新名称不能为空');
        }
        
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('原配置不存在');
        }
        
        // 检查新代码是否已存在
        if (PaymentConfigModel::checkCodeExists($newCode)) {
            $this->error('新代码已存在');
        }
        
        // 复制数据
        $newData = $data->toArray();
        unset($newData['id']);
        $newData['code'] = $newCode;
        $newData['title'] = $newTitle;
        $newData['ordering'] = PaymentConfigModel::getMaxOrdering();
        
        $r = $this->model->create($newData);
        if (!$r) {
            $this->error('复制失败');
        }
        
        $this->success(['id' => $r->id]);
    }
}
