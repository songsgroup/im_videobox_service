<?php
namespace app\controller\imext\money;

use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\RecorderModel;

/**
 * 资金流向记录管理
 */
#[Group('imext/money/recorder')]
class Recorder extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize(){
        parent::initialize();
        $this->model = new RecorderModel;
    }

    /**
     * 获取资金流向记录列表
     */
    #[Route('POST','list')]
    public function list()
    {

        $where = [];
        $where['pageNo'] = input('pageNo/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['userId'] = input('userID','');
       
     
        $r = RecorderModel::search($where);
        
        $result = ['errCode' => 200,'errMsg' => '成功', 'code' => 200, 'data' => $r, 'msg' => '成功'];
     
        return json($result);
    }

    /**
     * 根据ID获取资金记录详情
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
     * 根据用户ID获取资金记录
     */
    #[Route('GET','user/:userId')]
    public function getByUserId($userId)
    {
        $limit = input('limit/d', 20);
        $data = RecorderModel::getByUserId($userId, $limit);
        $this->success(['data' => $data]);
    }

    /**
     * 根据类型获取记录
     */
    #[Route('GET','type/:type')]
    public function getByType($type)
    {
        $limit = input('limit/d', 50);
        $data = RecorderModel::getByType($type, $limit);
        $this->success(['data' => $data]);
    }

    /**
     * 根据原始操作ID获取记录
     */
    #[Route('GET','data/:dataId')]
    public function getByDataId($dataId)
    {
        $data = RecorderModel::getByDataId($dataId);
        $this->success(['data' => $data]);
    }

    /**
     * 获取用户资金流水
     */
    #[Route('GET','userFlow/:userId')]
    public function getUserFlow($userId)
    {
        $where = [];
        $where['pageNo'] = input('pageNo/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $where['type'] = input('type','');
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];

        $r = RecorderModel::getUserFlow($userId, $where);
        $this->success($r);
    }

    /**
     * 新增资金记录
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
            $this->error('类型不能为空');
        }
        
        $r = RecorderModel::createRecord($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success(['id' => $r->id]);
    }

    /**
     * 批量新增资金记录
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
                $this->error('类型不能为空');
            }
        }
        
        $r = RecorderModel::batchCreate($records);
        if (!$r) {
            $this->error('批量保存失败');
        }
        
        $this->success(['created_count' => count($records)]);
    }

    /**
     * 修改资金记录
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
        $r = $data->save($updateData);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除资金记录
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
     * 批量删除资金记录
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
     * 获取资金流向统计
     */
    #[Route('GET','statistics')]
    public function statistics()
    {
        $where = [];
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];
        
        $r = RecorderModel::getStatistics($where);
        $this->success($r);
    }

    /**
     * 导出资金记录
     */
    #[Route('POST','export')]
    public function export()
    {
        $where = [];
        $where['userId'] = input('userId','');
        $where['nickName'] = input('nickName','');
        $where['type'] = input('type','');
        $where['status'] = input('status','');
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];
        
        // 获取所有数据（不分页）
        $data = RecorderModel::search($where);
        
        // 这里可以实现Excel导出逻辑
        // 暂时返回数据
        $this->success($data);
    }

    /**
     * 创建充值记录
     */
    #[Route('POST','createRechargeRecord')]
    public function createRechargeRecord()
    {
        $data = $this->request->param();
        
        $recordData = [
            'user_id' => $data['user_id'],
            'nick_name' => $data['nick_name'] ?? '',
            'money' => $data['money'],
            'money_front' => $data['money_front'] ?? 0,
            'type' => 'recharge',
            'status' => 0,
            'data_id' => $data['data_id'] ?? '',
            'remark' => $data['remark'] ?? '用户充值'
        ];
        
        $r = RecorderModel::createRecord($recordData);
        if (!$r) {
            $this->error('创建充值记录失败');
        }
        
        $this->success(['id' => $r->id]);
    }

    /**
     * 创建提现记录
     */
    #[Route('POST','createWithdrawRecord')]
    public function createWithdrawRecord()
    {
        $data = $this->request->param();
        
        $recordData = [
            'user_id' => $data['user_id'],
            'nick_name' => $data['nick_name'] ?? '',
            'money' => -abs($data['money']), // 提现为负数
            'money_front' => $data['money_front'] ?? 0,
            'type' => 'withdraw',
            'status' => 0,
            'data_id' => $data['data_id'] ?? '',
            'remark' => $data['remark'] ?? '用户提现'
        ];
        
        $r = RecorderModel::createRecord($recordData);
        if (!$r) {
            $this->error('创建提现记录失败');
        }
        
        $this->success(['id' => $r->id]);
    }

    /**
     * 创建消费记录
     */
    #[Route('POST','createConsumeRecord')]
    public function createConsumeRecord()
    {
        $data = $this->request->param();
        
        $recordData = [
            'user_id' => $data['user_id'],
            'nick_name' => $data['nick_name'] ?? '',
            'money' => -abs($data['money']), // 消费为负数
            'money_front' => $data['money_front'] ?? 0,
            'type' => 'consume',
            'status' => 0,
            'data_id' => $data['data_id'] ?? '',
            'remark' => $data['remark'] ?? '用户消费'
        ];
        
        $r = RecorderModel::createRecord($recordData);
        if (!$r) {
            $this->error('创建消费记录失败');
        }
        
        $this->success(['id' => $r->id]);
    }
}
