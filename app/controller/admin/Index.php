<?php
namespace app\controller\admin;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use think\facade\Cache;
use helper\Rand;
use constants\CacheConstants;
use app\service\admin\ConfigService;


/**
 * Index
 */
#[Group('admin')]
class Index extends \app\BaseController
{
    protected $noNeedLogin = ['index','getCode','login','logout'];

    /**
     * 首页
     *
     */
    #[Route('*','/')]
    public function index()
    {
        $this->success(null,sprintf('请通过前端地址访问。'));
    }
    
    /**
     * 生成验证码
     */
    #[Route('GET','captchaImage')]
    public function getCode()
    {
        $captchaEnabled =false; // ConfigService::selectCaptchaEnabled();
        $r = [
            'captchaEnabled' => $captchaEnabled,
        ];
        if (!$captchaEnabled) {
            $this->success($r);
        }
        $captchaType = 'char';
        if ($captchaType=='math') {
            $capText = '1+1@2';
            list($capStr,$code) = explode('@',$capText);
        }elseif ($captchaType=='char') {
            $capStr = $code = sprintf('%04X', mt_rand(0, 65535));
        }
        $image = $capText;//base64(text2img($capText))
        $r['img'] = $image;
        $r['uuid'] = Rand::uuid();
        Cache::set(CacheConstants::CAPTCHA_CODE_KEY.$r['uuid'],$code,300);
        $this->success($r);
    }

    /**
     * 登录方法
     */
    #[Route('POST','login')]
    public function login(){
        $username = input('username','');
        $password = input('password','');
        $code = input('code','');
        $uuid = input('uuid','');

        $captchaEnabled = ConfigService::selectCaptchaEnabled();
        if ($captchaEnabled) {
            if (Cache::get(CacheConstants::CAPTCHA_CODE_KEY.$uuid,'')!==$code) {
                $this->error('验证码错误');
            }
        }

        if (!$this->auth->login($username, $password)) {
            $this->error($this->auth->getError());
        }
        
        $token = $this->auth->getToken();
        $this->success(['token'=>$token]);
    }

    #[Route('*','logout')]
    public function logout(){
        try{ 
            $r = $this->auth->logout();
        }catch(\Exception $e){
            $this->error($this->auth->getError());
        }

        $this->success();
    }

    #[Route('GET','getInfo')]
    public function getInfo(){
        $user = $this->auth->getUser();
        $user?->dept?->children;
        $roles = array_column($user->roles?->toArray()?:[],'roleKey');
        $r = [
            'permissions'=>$user->getPerms(),
            'roles'=>$roles,
            'user'=>$user,
        ];
        $this->success($r);
    }

    #[Route('GET','getRouters')]
    public function getRouters(){
        $allMenu = $this->auth->getUser()->getRouters();

        $r = $allMenu;
        $this->success(['data'=>$r]);
    }
    
    #[Route('POST','register')]
    public function register(){
        if (ConfigService::selectConfigByKey('sys.account.registerUser')!=='true') {
            $this->error('当前系统没有开启注册功能！');
        }

        $username = input('username','');
        $password = input('password','');
        $code = input('code','');
        $uuid = input('uuid','');

        $captchaEnabled = ConfigService::selectCaptchaEnabled();
        if ($captchaEnabled) {
            if (Cache::get(CacheConstants::CAPTCHA_CODE_KEY.$uuid,'')!==$code) {
                $this->error('验证码错误');
            }
        }

        if (!$username) {
            $this->error('用户名不能为空');
        }
        if (!$password) {
            $this->error('用户密码不能为空');
        }
        if (strlen($username) < 2 || strlen($username) > 20) {
            $this->error('账户长度必须在2到20个字符之间');
        }
        if (strlen($password) < 5 || strlen($password) > 20) {
            $this->error('密码长度必须在2到20个字符之间');
        }
        if (!$this->auth->register($username, $password)) {
            $this->error($this->auth->getError());
        }

        $this->success();
    }
}
