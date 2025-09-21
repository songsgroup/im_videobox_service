<?php
namespace app\controller\imext\money;

use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\DigitalCurrencyModel;

/**
 * 数字币管理
 */
#[Group('imext/money/digitalcurrency')]
class DigitalCurrency extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize(){
        parent::initialize();
        $this->model = new DigitalCurrencyModel;
    }

    /**
     * 获取数字币记录列表
     */
    #[Route('POST','list')]
    public function list()
    {
        $where = [];
        $where['pageNo'] = input('pageNo/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['userId'] = input('userID','');
        $where['type'] = input('type','');
        $where['address'] = input('address','');
        $where['status'] = input('status','');
        $where['createUser'] = input('createUser','');
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];

        $r = DigitalCurrencyModel::search($where);
        
        $result = ['errCode' => 200,'errMsg' => '成功', 'code' => 200, 'data' => $r, 'msg' => '成功'];
        return json($result);
    }

    /**
     * 根据ID获取数字币记录详情
     */
    #[Route('POST',':id')]
    public function getInfo($id)
    {
        $r = [
            'data' => $this->model->find($id)
        ];
        $this->success($r);
    }

    /**
     * 根据用户ID获取数字币记录
     */
    #[Route('GET','user/:userId')]
    public function getByUserId($userId)
    {
        $limit = input('limit/d', 20);
        $data = DigitalCurrencyModel::getByUserId($userId, $limit);
        $this->success(['data' => $data]);
    }

    /**
     * 根据类型获取记录
     */
    #[Route('GET','type/:type')]
    public function getByType($type)
    {
        $limit = input('limit/d', 50);
        $data = DigitalCurrencyModel::getByType($type, $limit);
        $this->success(['data' => $data]);
    }

    /**
     * 根据地址获取记录
     */
    #[Route('GET','address/:address')]
    public function getByAddress($address)
    {
        $data = DigitalCurrencyModel::getByAddress($address);
        $this->success(['data' => $data]);
    }

    /**
     * 获取用户有效的数字币地址
     */
    #[Route('GET','userValidAddresses/:userId')]
    public function getUserValidAddresses($userId)
    {
        $data = DigitalCurrencyModel::getUserValidAddresses($userId);
        $this->success(['data' => $data]);
    }

    /**
     * 获取所有数字币类型
     */
    #[Route('GET','types')]
    public function getAllTypes()
    {
        $types = DigitalCurrencyModel::getAllTypes();
        $this->success(['data' => $types]);
    }

    /**
     * 新增数字币记录
     */
    #[Route('POST','add')]
    public function add()
    {
        $data = $this->request->param();
        
        // 验证必填字段
        if (empty($data['user_id'])) {
            $this->error('用户ID不能为空');
        }
        if (empty($data['type'])) {
            $this->error('数字币类型不能为空');
        }
        if (empty($data['address'])) {
            $this->error('数字币地址不能为空');
        }
        
        // 检查地址是否已存在
        if (DigitalCurrencyModel::checkAddressExists($data['address'])) {
            $this->error('该地址已存在');
        }
        
        $r = DigitalCurrencyModel::createRecord($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success(['id' => $r->id]);
    }

    /**
     * 批量新增数字币记录
     */
    #[Route('POST','batchAdd')]
    public function batchAdd()
    {
        $records = input('records/a',[]);
        if (empty($records)) {
            $this->error('记录数据不能为空');
        }
        
        // 验证每条记录
        foreach ($records as $record) {
            if (empty($record['user_id'])) {
                $this->error('用户ID不能为空');
            }
            if (empty($record['type'])) {
                $this->error('数字币类型不能为空');
            }
            if (empty($record['address'])) {
                $this->error('数字币地址不能为空');
            }
            
            // 检查地址是否已存在
            if (DigitalCurrencyModel::checkAddressExists($record['address'])) {
                $this->error('地址 ' . $record['address'] . ' 已存在');
            }
        }
        
        $r = DigitalCurrencyModel::batchCreate($records);
        if (!$r) {
            $this->error('批量保存失败');
        }
        
        $this->success(['created_count' => count($records)]);
    }

    /**
     * 修改数字币记录
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
        
        // 如果修改了地址，检查是否重复
        if (isset($updateData['address']) && $updateData['address'] != $data->address) {
            if (DigitalCurrencyModel::checkAddressExists($updateData['address'], $id)) {
                $this->error('该地址已存在');
            }
        }
        
        $r = $data->save($updateData);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除数字币记录
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
     * 审核数字币记录
     */
    #[Route('POST','audit')]
    public function audit()
    {
        $id = input('id/d',0);
        $status = input('status/d',0);
        $mark = input('mark','');
        
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('记录不存在');
        }
        
        $updateData = [
            'status' => $status,
            'mark' => $mark
        ];
        
        $r = $data->save($updateData);
        if (!$r) {
            $this->error('审核失败');
        }
        
        $this->success();
    }

    /**
     * 批量审核
     */
    #[Route('POST','batchAudit')]
    public function batchAudit()
    {
        $ids = input('ids/a',[]);
        $status = input('status/d',0);
        $mark = input('mark','');
        
        if (empty($ids)) {
            $this->error('请选择要审核的记录');
        }
        
        $updateData = [
            'status' => $status,
            'mark' => $mark
        ];
        
        $r = $this->model->whereIn('id', $ids)->update($updateData);
        
        if (!$r) {
            $this->error('批量审核失败');
        }
        
        $this->success(['processed_count' => $r]);
    }

    /**
     * 批量更新状态
     */
    #[Route('POST','batchUpdateStatus')]
    public function batchUpdateStatus()
    {
        $ids = input('ids/a',[]);
        $status = input('status/d',0);
        
        if (empty($ids)) {
            $this->error('请选择要更新的记录');
        }
        
        $r = DigitalCurrencyModel::batchUpdateStatus($ids, $status);
        if (!$r) {
            $this->error('更新状态失败');
        }
        
        $this->success(['updated_count' => $r]);
    }

    /**
     * 获取数字币统计
     */
    #[Route('GET','statistics')]
    public function statistics()
    {
        $where = [];
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];
        
        $r = DigitalCurrencyModel::getStatistics($where);
        $this->success($r);
    }

    /**
     * 获取待审核数量
     */
    #[Route('GET','pendingCount')]
    public function pendingCount()
    {
        $count = DigitalCurrencyModel::getPendingCount();
        $this->success(['count' => $count]);
    }

    /**
     * 检查地址是否存在
     */
    #[Route('POST','checkAddress')]
    public function checkAddress()
    {
        $address = input('address','');
        $excludeId = input('exclude_id/d',0);
        
        if (!$address) {
            $this->error('地址不能为空');
        }
        
        $exists = DigitalCurrencyModel::checkAddressExists($address, $excludeId ?: null);
        $this->success(['exists' => $exists]);
    }

    /**
     * 导出数字币记录
     */
    #[Route('POST','export')]
    public function export()
    {
        $where = [];
        $where['userId'] = input('userID','');
        $where['type'] = input('type','');
        $where['address'] = input('address','');
        $where['status'] = input('status','');
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];
        
        // 获取所有数据（不分页）
        $data = DigitalCurrencyModel::search($where);
        
        // 这里可以实现Excel导出逻辑
        // 暂时返回数据
        $this->success($data);
    }

    /**
     * 复制数字币记录
     */
    #[Route('POST','copy')]
    public function copy()
    {
        $id = input('id/d',0);
        $newAddress = input('new_address','');
        $newUserId = input('new_user_id','');
        
        if (!$id) {
            $this->error('请选择要复制的记录');
        }
        if (!$newAddress) {
            $this->error('新地址不能为空');
        }
        if (!$newUserId) {
            $this->error('新用户ID不能为空');
        }
        
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('原记录不存在');
        }
        
        // 检查新地址是否已存在
        if (DigitalCurrencyModel::checkAddressExists($newAddress)) {
            $this->error('新地址已存在');
        }
        
        // 复制数据
        $newData = $data->toArray();
        unset($newData['id']);
        $newData['user_id'] = $newUserId;
        $newData['address'] = $newAddress;
        $newData['status'] = 0; // 新记录状态为审核中
        $newData['create_time'] = date('Y-m-d H:i:s');
        
        $r = $this->model->create($newData);
        if (!$r) {
            $this->error('复制失败');
        }
        
        $this->success(['id' => $r->id]);
    }
}
