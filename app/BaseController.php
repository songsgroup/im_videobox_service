<?php
declare (strict_types = 1);

namespace app;

use think\annotation\route\Route;
use think\annotation\route\Group;
use think\App;
use think\exception\ValidateException;
use think\Validate;
use think\Request;
use think\helper\Str;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 默认响应输出类型,支持json/xml
     * @var string
     */
    protected $responseType = 'json';

    /**
     * 权限Auth
     * @var Auth
     */
    protected $auth = null;

    protected $model = null;
    
    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app){
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize(){
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');

        $this->auth = Auth::instance();
        $controllername = $this->request->controller();
        $actionname = $this->request->action();
        
        // token
        $token = $this->request->header('Authorization',
                    $this->request->request('token', 
                    \think\facade\Cookie::get('Admin-Token',
                    ''
                )));
        $token = array_reverse(explode(' ',$token))[0];
        
        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin)) {
            //初始化
            
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin()) {
                $this->error('请先登录',null,570);
            }
        } else {
            // 如果有传递token才验证是否登录状态
            if ($token) {
                $this->auth->init($token);
            }
        }
        
        // 读取鉴权注解
        $refClass = new \ReflectionClass($this::class);
        $refMethod = $refClass->getMethod($actionname);
        $attrs = $refMethod->getAttributes(\app\PreAuthorize::class);
        foreach ($attrs as $attr) {
            $attrins = $attr->newInstance();
            // 检测是否是否有对应权限
            if ( $attrins->type=='hasPermi' && !$this->auth->hasPermi($attrins->value) ) {
                $this->error('权限不足，无法访问系统资源',null,403);
            }
            // 检测是否是否有对应角色
            if ( $attrins->type=='hasRole' && !$this->auth->hasRole($attrins->value) ) {
                $this->error('权限不足，无法访问系统资源',null,403);
            }
        }
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 显示提示信息并重定向
     * @access protected
     * @param  string        $url     重定向地址
     * @param  string        $msg     提示字符串
     * @param  int           $delay   跳转延时
     * @return string
     */
    protected function redirect(string $url, string $msg = '', int $delay = 3000)
    {
        $msg or $delay = 100;
        $response = response(<<<HTML
            <h1 style="text-align: center">{$msg}</h1>
            <script>
                setTimeout(function(){ location.href = "{$url}" }, {$delay});
            </script>
        HTML);
        abort($response);
    }
    
    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed  $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型，支持json/xml/jsonp
     * @param array  $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result(string $msg,mixed $data = null,int $code = 0,string|null $type = null, array $header = [])
    {
        $result = [];
        $result['code'] = $code;
        $result['errCode'] = $code;
        $result['msg'] = $msg;
        $result['errMsg'] = $msg;
        $data && is_array($data) && $result=array_merge($result,$data);
        $data && is_string($data) && $result['data']=$data;
        $this->app->isDebug() && $result['uri'] = $this->request->controller().'->'.$this->request->action().'()';
        $type = $type ? $type : ($this->request->param(config('var_jsonp_handler')) ? 'jsonp' : $this->responseType);
        if (isset($header['statuscode'])) {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        } else {
            //未设置状态码,根据code值判断
            $code = 200;//$code >= 1000 || $code < 200 ? 200 : $code;
        }
        $response = response($result, $code, $header, $type);
        abort($response);
    }

    /**
     * 操作成功返回的数据
     * @param mixed  $data   要返回的数据
     * @param string $msg    提示信息
     * @param int    $code   错误码，默认为200
     * @param string $type   输出类型
     * @param array  $header 发送的 Header 信息
     */
    protected function success(mixed $data = null,string $msg = null,int $code = 200,string|null $type = null, array $header = [])
    {
        $this->result($msg??'操作成功', $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据
     * @param string $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为500
     * @param string $type   输出类型
     * @param array  $header 发送的 Header 信息
     */
    protected function error(string $msg = null,mixed $data = null,int $code = 500,string|null $type = null, array $header = [])
    {
        $this->result($msg??'操作失败', $data, $code, $type, $header);
    }

}
