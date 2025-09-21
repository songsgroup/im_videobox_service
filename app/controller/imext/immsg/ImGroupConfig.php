<?php
namespace app\controller\imext\immsg;

use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\ImGroupConfigModel;

/**
 * 群配置管理
 */
#[Group('imext/immsg/groupconfig')]
class ImGroupConfig extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize(){
        parent::initialize();
        $this->model = new ImGroupConfigModel;
    }

    /**
     * 获取群配置列表
     */
    #[Route('GET','list')]
    public function list()
    {
        $where = [];
        $where['pageNo'] = input('pageNo/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['groupId'] = input('groupId','');
        $where['groupName'] = input('groupName','');
        $where['userId'] = input('userId','');
        $where['groupType'] = input('groupType','');
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];

        $r = ImGroupConfigModel::search($where);
        $this->success($r);
    }

    /**
     * 根据ID获取群配置详情
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
     * 根据群编号获取配置
     */
    #[Route('GET','group/:groupId')]
    public function getByGroupId($groupId)
    {
        $data = ImGroupConfigModel::getByGroupId($groupId);
        if (!$data) {
            $this->error('群配置不存在');
        }
        $this->success(['data' => $data]);
    }

    /**
     * 根据用户ID获取群配置列表
     */
    #[Route('GET','user/:userId')]
    public function getByUserId($userId)
    {
        $data = ImGroupConfigModel::getByUserId($userId);
        $this->success(['data' => $data]);
    }

    /**
     * 新增群配置
     */
    #[Route('POST','add')]
    public function add()
    {
        $data = $this->request->param();
        
        // 验证必填字段
        if (empty($data['group_id'])) {
            $this->error('群编号不能为空');
        }
        if (empty($data['group_name'])) {
            $this->error('群名称不能为空');
        }
        if (empty($data['user_id'])) {
            $this->error('用户ID不能为空');
        }
        
        // 检查群编号是否已存在
        if (ImGroupConfigModel::checkGroupIdExists($data['group_id'])) {
            $this->error('群编号已存在');
        }
        
        // 设置默认值
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['group_type'] = $data['group_type'] ?? '1';
        
        $r = $this->model->create($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success(['id' => $r->id]);
    }

    /**
     * 修改群配置
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
        
        // 如果修改了群编号，检查是否重复
        if (isset($updateData['group_id']) && $updateData['group_id'] != $data->group_id) {
            if (ImGroupConfigModel::checkGroupIdExists($updateData['group_id'], $id)) {
                $this->error('群编号已存在');
            }
        }
        
        $r = $data->save($updateData);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除群配置
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
     * 批量删除群配置
     */
    #[Route('POST','batchDelete')]
    public function batchDelete()
    {
        $ids = input('ids/a',[]);
        if (empty($ids)) {
            $this->error('请选择要删除的记录');
        }
        
        $r = ImGroupConfigModel::batchDelete($ids);
        if (!$r) {
            $this->error('删除失败');
        }
        
        $this->success(['deleted_count' => $r]);
    }

    /**
     * 获取群类型统计
     */
    #[Route('GET','statistics')]
    public function statistics()
    {
        $r = ImGroupConfigModel::getGroupTypeStatistics();
        $this->success($r);
    }

    /**
     * 检查群编号是否存在
     */
    #[Route('POST','checkGroupId')]
    public function checkGroupId()
    {
        $groupId = input('group_id/d',0);
        $excludeId = input('exclude_id/d',0);
        
        if (!$groupId) {
            $this->error('群编号不能为空');
        }
        
        $exists = ImGroupConfigModel::checkGroupIdExists($groupId, $excludeId ?: null);
        $this->success(['exists' => $exists]);
    }

    /**
     * 导出群配置
     */
    #[Route('POST','export')]
    public function export()
    {
        $where = [];
        $where['groupId'] = input('groupId','');
        $where['groupName'] = input('groupName','');
        $where['userId'] = input('userId','');
        $where['groupType'] = input('groupType','');
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];
        
        // 获取所有数据（不分页）
        $data = ImGroupConfigModel::search($where);
        
        // 这里可以实现Excel导出逻辑
        // 暂时返回数据
        $this->success($data);
    }

    /**
     * 复制群配置
     */
    #[Route('POST','copy')]
    public function copy()
    {
        $id = input('id/d',0);
        $newGroupId = input('new_group_id/d',0);
        $newGroupName = input('new_group_name','');
        
        if (!$id) {
            $this->error('请选择要复制的配置');
        }
        if (!$newGroupId) {
            $this->error('新群编号不能为空');
        }
        if (!$newGroupName) {
            $this->error('新群名称不能为空');
        }
        
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('原配置不存在');
        }
        
        // 检查新群编号是否已存在
        if (ImGroupConfigModel::checkGroupIdExists($newGroupId)) {
            $this->error('新群编号已存在');
        }
        
        // 复制数据
        $newData = $data->toArray();
        unset($newData['id']);
        $newData['group_id'] = $newGroupId;
        $newData['group_name'] = $newGroupName;
        $newData['create_time'] = date('Y-m-d H:i:s');
        
        $r = $this->model->create($newData);
        if (!$r) {
            $this->error('复制失败');
        }
        
        $this->success(['id' => $r->id]);
    }
}
