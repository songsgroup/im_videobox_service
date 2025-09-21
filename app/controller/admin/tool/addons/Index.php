<?php
declare (strict_types = 1);

namespace app\controller\admin\tool\addons;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;

use think\Exception;

/**
 * IndexController
 *
 * @author 心衍
 * @version 2024-05-03 17:18:07
 */
#[Group('admin/tool/addons')]
class Index extends \app\BaseController
{
    protected $noNeedLogin = ['*'];

    /**
     * 新建插件
     *
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('POST','$')]
    #[PreAuthorize('hasPermi','tool:addons:add')]
    public function new(){
        $info = [
            'name' => input('name/s',''),
            'title' => input('title/s',''),
            'intro' => input('intro/s',''),
            'author' => input('author/s',''),
            'website' => input('website/s',''),
            'version' => input('version/s',''),
            'state' => 0,
        ];
        $addon_path = addons_path($info['name']);
        if ( is_dir($addon_path) ) {
            $this->error('插件名称不可用');
        }
        mkdir($addon_path);
        $Name = ucfirst($info['name']);
        file_put_contents($addon_path."{$Name}.php",
            \think\facade\View::fetch(
                root_path('extend'.DS.'addonslibrary'.DS).'Addons.stub',
                array_merge($info,['Name'=>$Name])));
        set_addon_info($info['name'],$info);

        $this->success();
    }

    /**
     * 卸载插件
     *
     * @method (DELETE)
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('DELETE','<ids>')]
    #[PreAuthorize('hasPermi','tool:addons:remove')]
    public function remove($ids){
        $addon = get_addon_instance($ids);
        if (!$addon) {
            $this->error('插件不存在');
        }
        $info = $addon->getInfo();
        if ($info['state']) {
            $this->error('卸载插件前请先禁用插件');
        }
        try {
            if ($addon->uninstall()) {
                rmdirs(addons_path($ids));
                rmdirs(root_path(str_replace('/', DS, 'ui/admin/src/api/addons/'.$info['name'])));
                rmdirs(root_path(str_replace('/', DS, 'ui/admin/src/components/addons/'.$info['name'])));
                rmdirs(root_path(str_replace('/', DS, 'ui/admin/src/views/addons/'.$info['name'])));
                rmdirs(root_path(str_replace('/', DS, 'ui/admin/src/assets/addons/'.$info['name'])));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }

    /**
     * 修改插件信息
     *
     * @method (PUT)
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('PUT','')]
    #[PreAuthorize('hasPermi','tool:addons:edit')]
    public function edit(){
        $info = [
            'name' => input('name',''),
            'title' => input('title',''),
            'intro' => input('intro',''),
            'author' => input('author',''),
            'website' => input('website',''),
            'version' => input('version',''),
        ];
        try {
            set_addon_info($info['name'],$info);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }

    /**
     * 启用/禁用插件
     *
     * @method (PUT)
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('PUT','changeStatus')]
    #[PreAuthorize('hasPermi','tool:addons:edit')]
    public function changeStatus(){
        $name = input('name','');
        $state = input('state/d',0);

        $addon = get_addon_instance($name);
        if (!$addon) {
            $this->error("插件({$name})不存在");
        }
        try {
            if ($state?$addon->enable():$addon->disable()) {
                set_addon_info($name,['state'=>$state]);
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }

    /**
     * 读取插件配置
     *
     * @method (GET)
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('GET','config/:name')]
    #[PreAuthorize('hasPermi','tool:addons:config')]
    public function getConfig($name){
        $info = get_addon_info($name);
        $r = [
            'title' => $info['title'],
            'data' => get_addon_fullconfig($name),
        ];
        $this->success($r);
    }

    /**
     * 修改插件配置
     *
     * @method (PUT)
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('PUT','config/:name')]
    #[PreAuthorize('hasPermi','tool:addons:config')]
    public function setConfig($name){
        $config = $this->request->param();
        try {
            set_addon_config($name,$config);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }

    /**
     * 插件打包导出
     *
     * @method (GET)
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('GET','export')]
    #[PreAuthorize('hasPermi','tool:addons:edit')]
    public function export(){
        $name = input('name/s','');
        if (!$name) {
            $this->error('插件不存在');
        }

        $dir = addons_path($name);
        if (!is_dir($dir)) {
            $this->error('插件不存在');
        }

        try {
            $file = dir_archive($dir,'','addons_');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        abort(download($file,"addons_{$name}.zip"));
    }

    /**
     * 插件列表
     *
     * @method (GET)
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('GET','list')]
    #[PreAuthorize('hasPermi','tool:addons:query')]
    public function list(){
        $where = [];

        $where['name'] = input('name',null);
        $where['intro'] = input('intro',null);
        $where['author'] = input('author',null);

        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);

        $addonsList = get_addons_list();
        if ($where['name']) {
            $addonsList = array_filter($addonsList,fn($x)=>str_contains($x['name'],$where['name']));
        }
        if ($where['intro']) {
            $addonsList = array_filter($addonsList,fn($x)=>str_contains($x['intro'],$where['intro']));
        }
        if ($where['author']) {
            $addonsList = array_filter($addonsList,fn($x)=>str_contains($x['author'],$where['author']));
        }
        $total = count($addonsList);
        $addonsList = array_slice($addonsList, ($where['pageNum']-1)*$where['pageSize'], $where['pageSize']);
        $addonsList = array_map(function($addon){
                if (is_file(addons_path($addon['name']).'config.php')) {
                    $addon['config']=true;
                }
                return $addon;
            },array_values($addonsList));
        $r = [
            'total' => $total,
            'rows' => $addonsList,
        ];
        $this->success($r);
    }

    /**
     * 获取插件信息
     *
     * @method (GET)
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('GET',':name')]
    #[PreAuthorize('hasPermi','tool:addons:query')]
    public function getInfo($name){
        $r = [
            'data' => get_addon_info($name),
        ];
        $this->success($r);
    }

    /**
     * 插件离线安装
     *
     * @method (GET)
     * @author 心衍
     * @version 2024-05-03 17:18:07
     */
    #[Route('POST','import')]
    #[PreAuthorize('hasPermi','tool:addons:import')]
    public function import(){
        // 接收上传文件
        try {
            $file = request()->file('file');
            $mimeType = strtolower($file->getOriginalMime());
            $extension = strtolower($file->extension());
            if ($mimeType!=='application/zip') {
                $this->error('插件包格式有误');
            }
            $filesize = $file->getSize();
            // 保存到runtime目录
            $fullName = $file->getOriginalName();
            $savePath = runtime_path('addons');
            $file->move($savePath,$fullName);
        } catch (\think\exception\ErrorException $e) {
            $this->error($e->getMessage());
        }

        // 检查插件包格式(是否存在info.ini)
        $zip = new \ZipArchive;
        try {
            $res = $zip->open($savePath.$fullName);
            if (!$res) {
                throw new Exception('插件包格式有误');
            }
            $ini = $zip->getFromName('info.ini');
            if (!$ini) {
                throw new Exception('插件包格式有误');
            }
            $info = parse_ini_string($ini);
            $info_check_keys = ['name', 'title', 'intro', 'author', 'version', 'state'];
            foreach ($info_check_keys as $value) {
                if (!array_key_exists($value, $info)) {
                    throw new Exception('插件包格式有误');
                }
            }
            // 插件已存在 覆盖安装
            $addon_path = addons_path($info['name']);
            if (is_dir($addon_path)) {
                $addon = get_addon_info($info['name']);
                if ($addon['state']) {
                    throw new Exception('覆盖安装前请先禁用插件');
                }
            }
            $zip->extractTo($addon_path);
            $zip->close();
        } catch (Exception $e) {
            $zip->close();
            // unlink($savePath.$fullName);
            $this->error($e->getMessage());
        }

        try {
            // unlink($savePath.$fullName);
            // 禁用插件
            set_addon_info($info['name'],['state'=>0]);
            // 执行插件安装方法
            $addon = get_addon_instance($info['name']);
            if (!$addon) {
                $this->error("插件({$info['name']})不存在");
            }
            // 复制前端文件
            if (is_dir($addon->addonPath('ui/admin/api')))
                copydirs($addon->addonPath('ui/admin/api'),
                    root_path(str_replace('/', DS, 'ui/admin/src/api/addons/'.$info['name'])));
            if (is_dir($addon->addonPath('ui/admin/components')))
                copydirs($addon->addonPath('ui/admin/components'),
                    root_path(str_replace('/', DS, 'ui/admin/src/components/addons/'.$info['name'])));
            if (is_dir($addon->addonPath('ui/admin/views')))
                copydirs($addon->addonPath('ui/admin/views'),
                    root_path(str_replace('/', DS, 'ui/admin/src/views/addons/'.$info['name'])));
            if (is_dir($addon->addonPath('ui/admin/assets')))
                copydirs($addon->addonPath('ui/admin/assets'),
                    root_path(str_replace('/', DS, 'ui/admin/src/assets/addons/'.$info['name'])));
            // 执行插件安装回调
            $addon->install();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success(null,'插件安装成功');
    }
}
