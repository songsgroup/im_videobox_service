<?php

namespace app\middleware;

use app\model\admin\OperLogModel;
use app\model\admin\LogininforModel;

use think\facade\Cache;

/**
 * 接口日志记录
 */
class OperLog
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        $time = date('Y-m-d H:i:s',$request->server('REQUEST_TIME'));
        $ipaddr = $request->ip();
        $login_location = Cache::get('ip_location_'.$ipaddr,'-');

        $auth = \app\Auth::instance();
        $params = $request->request();
        $uri = $params['s']??'';
        if ( $auth->isLogin() && str_starts_with($uri,'/admin') ) {
            unset($params['s']);
            $result = $response->getData();
            OperLogModel::insert([
                'title'         => '',
                'business_type' => '0',//（0其它 1新增 2修改 3删除）
                'method'        => implode('.',array_reverse(explode('.',$request->server('SERVER_NAME')))).'.'.$request->controller().'.'.$request->action().'()',
                'request_method'=> $request->method(),
                'operator_type' => '1',//操作类别（0其它 1后台用户 2手机端用户）
                'oper_name'     => $auth->user_name,
                'dept_name'     => $auth->dept?->dept_name,
                'oper_url'      => $uri,
                'oper_ip'       => $ipaddr,
                'oper_location' => $login_location,
                'oper_param'    => json_encode($params),
                'json_result'   => strlen(json_encode($result))<65535?json_encode($result):'Data too long',
                'status'        => $response->getCode()==200?'0':'1',
                'error_msg'     => $response->getCode()==200 && is_array($result)?$result['code']=='200'?null:($result['msg']??$result['message']??''):$response->getCode(),
                'oper_time'     => $time,
                'cost_time'     => microtime(1)*1000-$request->server('REQUEST_TIME_FLOAT')*1000,
            ]);
        }

        
        if (in_array($request->request('s',''),['/admin/login','/admin/register'])) {
            $browser = \helper\UA::browser();
            $os = \helper\UA::os();
            list($login_location) = event('iplocation',$ipaddr)?:['-'];
            Cache::set('ip_location_'.$ipaddr,$login_location);

            if($response->getCode()!=500){
                $info = LogininforModel::create([
                    'user_name'      => input('username',''),
                    'ipaddr'         => $ipaddr,
                    'login_location' => $login_location,
                    'browser'        => $browser,
                    'os'             => $os,
                    'status'         => $response->getData()['code']=='200'?'0':'1',
                    'msg'            => $response->getData()['msg'],
                    'login_time'     => $time,
                ]);
                
                if($auth->isLogin()){
                    $data = $auth->cache_decode($auth->getToken());
                    $data['info_id'] = $info['info_id'];
                    $auth->cache_encode($data);
                }
            }
        }
        
        return $response;
    }
}