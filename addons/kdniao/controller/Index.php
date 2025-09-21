<?php
declare (strict_types = 1);

namespace addons\kdniao\controller;

use think\annotation\route\Route;
use think\annotation\route\Group;
use addons\kdniao\library\Kdniao;

class Index extends \app\BaseController
{

    #[Route('GET','getList')]
    public function index()
    {
        $this->success(['data'=>Kdniao::$data]);
    }

    #[Route('*','$')]
    public function query()
    {
        $code = $this->request->post('code');
        $company = $this->request->post('company');
        $kdniao = new Kdniao();
        $wuliu = $kdniao->getOrderTracesByJson($company, $code);

        if ($wuliu == -1) {
            $this->error('未设置接口配置！请在插件管理中配置！');
        }

        $wuliu = json_decode($wuliu, true);

        $r = isset($wuliu['Traces']) && count($wuliu['Traces']) ? array_reverse($wuliu['Traces']) : [['AcceptStation' => '暂无物流信息', 'AcceptTime' => date('Y-m-d H:i:s', time())]];
        $this->success(['data'=>$r]);
    }

}
