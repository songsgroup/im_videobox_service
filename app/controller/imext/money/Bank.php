<?php
namespace app\controller\imext\money;

use think\annotation\route\Group;
use think\annotation\route\Route;
use app\model\imext\BankModel;

/**
 * 银行卡管理
 */
#[Group('imext/money/bank')]
class Bank extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    protected function initialize(){
        parent::initialize();
        $this->model = new BankModel;
    }

    /**
     * 获取银行列表
     */
    #[Route('POST','list')]
    public function list()
    {
        $where = [];
        $where['pageNo'] = input('pageNo/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        // $where['userId'] = input('userID','');
        $where['name'] = input('name','');
        $where['en_name'] = input('en_name','');
        // $where['type'] = input('type','');
        // $where['status'] = input('status','');
        // $where['orderId'] = input('orderId','');
        // $where['createUser'] = input('createUser','');
        // $where['minMoney'] = input('minMoney','');
        // $where['maxMoney'] = input('maxMoney','');
        // $where['params'] = [
        //     'beginTime' => input('beginTime',''),
        //     'endTime' => input('endTime','')
        // ];

        $r = BankModel::search($where);
        
        $result = ['errCode' => 200,'errMsg' => '成功', 'code' => 200, 'data' => $r, 'msg' => '成功'];
        return json($result);
    }

    /**
     * 根据ID获取银行卡记录详情
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
     * 根据用户ID获取银行卡记录
     */
    #[Route('POST','user/:userId')]
    public function getByUserId($userId)
    {
        $limit = input('limit/d', 20);
        $data = UserBankModel::getByUserId($userId, $limit);
        $this->success(['data' => $data]);
    }

    /**
     * 根据提现单号获取记录
     */
    #[Route('POST','order/:orderId')]
    public function getByOrderId($orderId)
    {
        $data = UserBankModel::getByOrderId($orderId);
        if (!$data) {
            $this->error('记录不存在');
        }
        $this->success(['data' => $data]);
    }

    /**
     * 根据银行卡ID获取记录
     */
    #[Route('POST','card/:cardId')]
    public function getByCardId($cardId)
    {
        $data = UserBankModel::getByCardId($cardId);
        $this->success(['data' => $data]);
    }

    /**
     * 新增银行
     */
    #[Route('POST','add')]
    public function add()
    {
        $data = $this->request->param();
        
        // 验证必填字段
        // if (empty($data['user_id'])) {
        //     $this->error('用户ID不能为空');
        // }
        // if (empty($data['name'])) {
        //     $this->error('用户姓名不能为空');
        // }
        // if (empty($data['money'])) {
        //     $this->error('提现金额不能为空');
        // }
        
        // // 生成提现单号
        // do {
        //     $orderId = UserBankModel::generateOrderId();
        // } while (UserBankModel::checkOrderIdExists($orderId));
        
        // $data['order_id'] = $orderId;
        
        $r = BankModel::createBank($data);
        if (!$r) {
            $this->error('保存失败');
        }

        $this->success(['id' => $r->id, 'bank_name' => $r->name]);
    }

    /**
     * 修改银行卡记录
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
        
        // 如果状态变为审核通过，设置审核时间
        if (isset($updateData['status']) && $updateData['status'] == 1) {
            $updateData['examine_date'] = date('Y-m-d H:i:s');
        }
        
        $r = $data->save($updateData);
        if (!$r) {
            $this->error('操作失败');
        }
        $this->success();
    }

    /**
     * 删除银行卡记录
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
     * 审核银行卡记录
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
        
        // 如果审核通过，设置审核时间
        if ($status == 1) {
            $updateData['examine_date'] = date('Y-m-d H:i:s');
        }
        
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
        
        // 如果审核通过，设置审核时间
        if ($status == 1) {
            $updateData['examine_date'] = date('Y-m-d H:i:s');
        }
        
        $r = $this->model->whereIn('id', $ids)->update($updateData);
        
        if (!$r) {
            $this->error('批量审核失败');
        }
        
        $this->success(['processed_count' => $r]);
    }

    /**
     * 拒绝提现
     */
    #[Route('POST','reject')]
    public function reject()
    {
        $id = input('id/d',0);
        $refuse = input('refuse','');
        
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('记录不存在');
        }
        
        $updateData = [
            'status' => 2, // 拒绝状态
            'refuse' => $refuse,
            'examine_date' => date('Y-m-d H:i:s')
        ];
        
        $r = $data->save($updateData);
        if (!$r) {
            $this->error('拒绝操作失败');
        }
        
        $this->success();
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
        
        $r = UserBankModel::batchUpdateStatus($ids, $status);
        if (!$r) {
            $this->error('更新状态失败');
        }
        
        $this->success(['updated_count' => $r]);
    }

    /**
     * 获取银行卡统计
     */
    #[Route('GET','statistics')]
    public function statistics()
    {
        $where = [];
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];
        
        $r = UserBankModel::getStatistics($where);
        $this->success($r);
    }

    /**
     * 获取待审核数量
     */
    #[Route('GET','pendingCount')]
    public function pendingCount()
    {
        $count = UserBankModel::getPendingCount();
        $this->success(['count' => $count]);
    }

    /**
     * 导出银行卡记录
     */
    #[Route('POST','export')]
    public function export()
    {
        $where = [];
        $where['userId'] = input('userID','');
        $where['name'] = input('name','');
        $where['type'] = input('type','');
        $where['status'] = input('status','');
        $where['params'] = [
            'beginTime' => input('beginTime',''),
            'endTime' => input('endTime','')
        ];
        
        // 获取所有数据（不分页）
        $data = UserBankModel::search($where);
        
        // 这里可以实现Excel导出逻辑
        // 暂时返回数据
        $this->success($data);
    }

    /**
     * 计算提现手续费
     */
    #[Route('POST','calculateFee')]
    public function calculateFee()
    {
        $money = input('money/f',0);
        $type = input('type/d',1);
        
        // 这里可以根据业务规则计算手续费
        // 示例：银行卡提现手续费2%，USDT提现手续费1%
        $feeRate = $type == 1 ? 0.02 : 0.01; // 银行卡2%，USDT1%
        $sCharge = $money * $feeRate;
        $actualAmount = $money - $sCharge;
        
        $this->success([
            'original_amount' => $money,
            'fee_rate' => $feeRate * 100,
            'fee_amount' => $sCharge,
            'actual_amount' => $actualAmount
        ]);
    }

    /**
     * 生成提现单号
     */
    #[Route('POST','generateOrderId')]
    public function generateOrderId()
    {
        do {
            $orderId = UserBankModel::generateOrderId();
        } while (UserBankModel::checkOrderIdExists($orderId));
        
        $this->success(['order_id' => $orderId]);
    }
}
