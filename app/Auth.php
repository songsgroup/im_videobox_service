<?php

namespace app;

use think\facade\Db;
use think\Exception;
use think\facade\Validate;
use think\facade\Request;
use think\facade\Config;
use think\facade\Session;
use think\facade\Cache;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use helper\Rand;

use app\model\admin\UserModel;

class Auth
{
    protected static $instance = null;
    protected $_error = '';
    protected $_logined = false;
    protected $_user = null;
    protected $_token = '';
    //Token默认有效时长
    protected $keeptime = 2592000;
    protected $requestUri = '';
    protected $rules = [];
    //默认配置
    protected $config = [];
    protected $options = [];
    protected $allowFields = ['user_id', 'dept_id', 'user_name', 'nick_name', 'user_type', 'email', 'phonenumber', 'sex', 'avatar', 'status'];

    public function __construct($options = [])
    {
        if ($config = Config::get('user')) {
            $this->config = array_merge($this->config, $config);
        }
        $this->options = array_merge($this->config, $options);
    }

    /**
     *
     * @param array $options 参数
     * @return Auth
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 获取User模型
     * @return User
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * 兼容调用user模型的属性
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $name=='id' && $name = 'user_id';
        return $this->_user ? $this->_user->$name : null;
    }

    /**
     * 兼容调用user模型的属性
     */
    public function __isset($name)
    {
        $name=='id' && $name = 'user_id';
        return isset($this->_user) ? isset($this->_user->$name) : false;
    }

    public static function jwt_encode($payload,$keeptime=0){
        $payload['login_time'] = time();
        $payload['keeptime'] = $keeptime;
        $key = env('JWT_KEY','20230718-2020-4012-3456-111122223333');
        $jwt = JWT::encode($payload, $key, 'HS512');
        return $jwt;
    }

    public static function jwt_decode($jwt){
        if (!$jwt) return;
        $key = env('JWT_KEY','20230718-2020-4012-3456-111122223333');
        try {
            $decoded = (array)JWT::decode($jwt, new Key($key, 'HS512'));
        } catch (\Exception $e) {
            dump($e->getMessage());
            return;
        }
        return $decoded;
    }
    
    public function cache_encode($data){
        $uuid = $data['uuid']??\helper\Rand::uuid();
        $userKey = \constants\CacheConstants::LOGIN_TOKEN_KEY.$uuid;
        unset($data['uuid']);
        Cache::tag(\constants\CacheConstants::LOGIN_TOKEN_KEY)->set($userKey,$data,$this->keeptime);
        $token = self::jwt_encode([\constants\Constants::LOGIN_USER_KEY=>$uuid],$this->keeptime);
        return $token;
    }
    
    public function cache_decode($token){
        $data = self::jwt_decode($token);
        if (!$data) return;
        $uuid = $data[\constants\Constants::LOGIN_USER_KEY]??'';
        if (!$uuid) return;
        $userKey = \constants\CacheConstants::LOGIN_TOKEN_KEY.$uuid;
        $data = Cache::get($userKey);
        $user_id = trim($data['user_id']??0);
        if (!$user_id) return;
        $data['uuid']=$uuid;
        return $data;
    }
    
    public function refresh_token($data){
        // 
    }

    /**
     * 根据Token初始化
     *
     * @param string $token Token
     * @return boolean
     */
    public function init($token){
        if ($this->_logined) {
            return true;
        }
        if ($this->_error) {
            return;
        }

        $data = $this->cache_decode($token);
        if ($data) {
            $user = UserModel::find($data['user_id']);
            if (!$user) {
                $this->setError('Account not exist');
                return;
            }
            if ($user->status != 0) {
                $this->setError('Account is locked');
                return;
            }
            $this->_user = $user;
            $this->_logined = true;
            $this->_token = $token;

            //初始化成功的事件
            event("user_init_successed", $this->_user);

            return true;
        } else {
            $this->setError('You are not logged in');
            return;
        }
    }

    /**
     * 注册用户
     *
     * @return boolean
     */
    public function register($account, $password, $email = '', $phonenumber = '',$extend=[]){

        // 检测用户名、昵称、邮箱、手机号是否存在
        if (UserModel::where('user_name',$account)->find()) {
            $this->setError('注册用户名已存在');
            return false;
        }
        if ($email && UserModel::where('email',$email)->find()) {
            $this->setError('注册邮箱已存在');
            return false;
        }
        if ($phonenumber && UserModel::where('phonenumber',$phonenumber)->find()) {
            $this->setError('注册手机号已存在');
            return false;
        }

        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try {
            $user = new UserModel;
            $user->user_name   = $account;
            $user->password    = password_hash($password, PASSWORD_BCRYPT);
            $user->nick_name   = $account;
            $user->dept_id     = 0;
            $user->phonenumber = $phonenumber;
            $user->email       = $email;
            $user->sex         = 2;
            $user->status      = 0;
            $user->remark      = '';
            $user->save();

            UserPostModel::setUserPosts($user->user_id,[4]);//岗位 - 普通员工
            UserRoleModel::setUserRoles($user->user_id,[2]);//角色 - 普通角色

            Db::commit();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            Db::rollback();
            return false;
        }

        $this->direct($user->user_id);
        return true;
    }

    /**
     * 用户登录
     *
     * @param string $account  账号,用户名、邮箱、手机号
     * @param string $password 密码
     * @param string $captcha 短信验证码
     * @return boolean
     */
    public function login($account, $password='',$captcha=''){
        $field = Validate::regex($account, '/^1\d{10}$/') ? 'phonenumber' : 'user_name';
        $user = UserModel::where($field,$account)->find();
        if (!$user) {
            $this->setError('账号不存在或者密码错误!');
            return false;
        }

        if ($user->status != 0) {
            $this->setError('账号已被冻结');
            return false;
        }

        if ($password) {
            if (!password_verify($password, $user->password)) {
                $this->setError('账号不存在或者密码错误!');
                return false;
            }
        }
        if ($captcha) {
            // list($rst,$msg) = \app\model\Sendsms::check($account,$captcha);
            // if (!$rst) {
            //     $this->setError($msg);
            //     return false;
            // }
        }

        //直接登录会员
        return $this->direct($user->user_id);
    }

    /**
     * 退出
     *
     * @return boolean
     */
    public function logout(){
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }
        //设置登录标识
        $this->_logined = false;
        
        // 从缓存删除登录状态
        $data = self::jwt_decode($this->_token);
        $uuid = $data[\constants\Constants::LOGIN_USER_KEY];
        $userKey = \constants\CacheConstants::LOGIN_TOKEN_KEY.$uuid;
        Cache::delete($userKey);
        
        //退出成功的事件
        event("user_logout_successed", $this->_user);
        return true;
    }

    /**
     * 修改密码
     * @param string $newpassword       新密码
     * @param string $oldpassword       旧密码
     * @param bool   $ignoreoldpassword 忽略旧密码
     * @return boolean
     */
    public function changepwd($newpassword, $oldpassword = '', $ignoreoldpassword = false){
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }
        //判断旧密码是否正确
        if (password_verify($oldpassword, $this->_user->password) || $ignoreoldpassword) {
            Db::startTrans();
            try {
                $newpassword = password_hash($newpassword, PASSWORD_BCRYPT);
                $this->_user->save(['password' => $newpassword]);

                // Token::delete($this->_token);
                //修改密码成功的事件
                event("user_changepwd_successed", $this->_user);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->setError($e->getMessage());
                return false;
            }
            return true;
        } else {
            $this->setError('Password is incorrect');
            return false;
        }
    }

    /**
     * 直接登录账号
     * @param int $user_id
     * @return boolean
     */
    public function direct($user_id){
        $user = UserModel::find($user_id);
        if ($user) {
            Db::startTrans();
            try {
                $ip = request()->ip();
                $time = time();

                $user->save();

                $this->_user = $user;
                
                $this->_token = $this->cache_encode([
                        'user_id'=>$user->user_id,
                    ]);

                $this->_logined = true;
                
                //登录成功的事件
                event("user_login_successed", $this->_user);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->setError($e->getMessage());
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测是否是否有对应权限
     * @param string $perms   权限标识
     * @return bool
     */
    public function hasPermi($perms = ''):bool {
        if (!$this->_logined) {
            return false;
        }
        $allperms = $this->_user->getPerms();
        if(in_array($perms,$allperms)) return true;
        $perms = explode(':',$perms);
        foreach ($allperms as $item){
            $item = explode(':',$item);
            if(array_filter(array_keys($item),fn($i)=>$item[$i]!=='*'&&$item[$i]!==$perms[$i]))
                break;
            return true;
        }
        return false;
    }

    /**
     * 检测是否是否有对应角色
     * @param string $role   角色标识
     * @return bool
     */
    public function hasRole($role = ''):bool {
        if (!$this->_logined) {
            return false;
        }
        $roles = $this->_user->roles()->column('role_key');
        return in_array($role,$roles);
    }

    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined) {
            return true;
        }
        return false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 获取当前请求的URI
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * 设置当前请求的URI
     * @param string $uri
     */
    public function setRequestUri($uri)
    {
        $this->requestUri = $uri;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields()
    {
        return $this->allowFields;
    }

    /**
     * 设置允许输出的字段
     * @param array $fields
     */
    public function setAllowFields($fields)
    {
        $this->allowFields = $fields;
    }

    /**
     * 删除一个指定会员
     * @param int $user_id 会员ID
     * @return boolean
     */
    public function delete($user_id)
    {
        $user = UserModel::find($user_id);
        if (!$user) {
            return false;
        }
        Db::startTrans();
        try {
            // 删除会员
            UserModel::destroy($user_id);

            event("user_delete_successed", $user);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->setError($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public function match($arr = [])
    {
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr) {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower(Request::action()), $arr) || in_array('*', $arr)) {
            return true;
        }

        // 没找到匹配
        return false;
    }

    /**
     * 设置会话有效时间
     * @param int $keeptime 默认为永久
     */
    public function keeptime($keeptime = 0)
    {
        $this->keeptime = $keeptime;
    }

    /**
     * 设置错误信息
     *
     * @param string $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ?: '';
    }
}
