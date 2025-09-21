<?php

namespace addons\generator\library;

use app\Auth;
use think\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\helper\Str;
use think\facade\View;

use addons\generator\model\GenTableModel;
use addons\generator\model\GenTableColumnModel;
use app\model\admin\MenuModel;

/**
 * 代码生成
 */
class GeneratorService
{
    const DEFAULT_PARENT_MENU_ID = 3;

    public static string $rootPath = '';
    public static string $tplPath = '';
    
	// public function __construct(array $config){
	// 	$config['driver'] = $config['driver']??$this->driver;
	// 	$this->tablePrefix = config('database.connections.' . $config['driver'] . '.prefix');
	// 	$this->database = Config::get('database.connections' . '.' . $config['driver'] . '.database');
	// 	$this->rootPath = root_path();
	// 	$this->tplPath = addons_path('generator') . 'stub' . DS;
	// }

    public static function init()
    {
        self::$rootPath = root_path();
        self::$tplPath = addons_path('generator') . 'stub' . DS;
    }
	
    /**
     * 预览代码
     */
	public static function previewCode(int $tableId)
    {
	    // 查询表信息
	    $table = GenTableModel::find($tableId);
	    // 设置主子表信息
	    $table['subTableName'] 
            && $table['subTable'] = GenTableModel::where('table_name',$table['subTableName'])
                                                ->find();

        $context = self::prepareContext($table);
        
        $templates = self::getTemplateList($table['tplCategory'], $table['tplWebType']?:'');
        $r = [];
        foreach ($templates as $template){
            $tplPath = self::$tplPath.$template.'.stub';
            try {
                $code = View::fetch($tplPath,$context);
            } catch (\Exception $e) {
                $code = $e->getMessage()."\n".$e->getTraceAsString();
            }
            // $r[$template] = self::getFileName($template,$table);
            $r['vm/'.$template.'.vm'] = $code;
        }
        return $r;
	}

    /**
     * 生成代码
     *
     * @return bool
     */
    public static function generatorCode($tableName,$zip=null):bool
    {
        // 查询表信息
        $table = GenTableModel::where('table_name',$tableName)->find();
        // 设置主子表信息
        $table['subTableName']
            && $table['subTable'] = GenTableModel::where('table_name',$table['subTableName'])
                                                ->find();

        $context = self::prepareContext($table);

        $templates = self::getTemplateList($table['tplCategory'], $table['tplWebType']?:'');

        $genPath = $table['genPath']?($table['genPath']=='runtime'?root_path().'runtime/generator/':$table['genPath']):root_path();
        foreach ($templates as $template){
            $tplPath = self::$tplPath.$template.'.stub';
            try {
                $code = \think\facade\View::fetch($tplPath,$context);
            } catch (\Exception $e) {
                $code = $e->getMessage()."\n".$e->getTraceAsString();
            }
            if(!($filename = self::getFileName($template,$table))){
                continue;
            }
            if (str_ends_with($template,'.php')) {
                if ($table['packageName']=='addons.'.$table['moduleName']) {
                    $dir = 'addons/'.$table['moduleName'].'/';
                }else{
                    $dir = 'app/';
                }
                // continue;
            }elseif(str_ends_with($template,'sql')) {
                $dir = '';
            }else{
                $dir = 'ui/admin/';
                // continue;
            }
            if (!$table['genType']) {
                $path = $dir.$filename;
                $zip->addFromString($path,$code);
            }else{
                if (str_ends_with($template,'sql') && !$table['genPath']) {
                    // if (!MenuModel::where('component',substr($context['namespace']['vue'],1).'/index')->find()) {
                    //     $sql = array_filter(explode(";",$code),fn($x)=>trim($x));
                    //     $vars = [];
                    //     foreach ($sql as $s) {
                    //         if (preg_match('/SELECT\s+@(\S+)\s+:=\s+(.*)/',$s,$matchs)) {
                    //             $s = "SELECT {$matchs[2]};";
                    //             $vars[$matchs[1]] = current(Db::query($s)[0]);
                    //         }else{
                    //             $s = preg_replace_callback('/@(\w+)/',fn($m)=>$vars[$m[1]], $s);
                    //             Db::execute($s);
                    //         }
                    //     }
                    // }
                    continue;
                }
                $path = str_replace('/',DS,$genPath.$dir.$filename);
                if (!is_dir(dirname($path))) {
                    mkdir(dirname($path),0777,true);
                }
                file_put_contents($path,$code);
            }
        }
        return true;
    }
	
    /**
     * 设置模板变量信息
     *
     * @return 模板列表
     */
    public static function prepareContext(GenTableModel $table):array
    {
        $moduleName   = $table['moduleName'];
        $businessName = $table['businessName'];
        $packageName  = $table['packageName'];
        $tplCategory  = $table['tplCategory'];
        $functionName = $table['functionName'];
        
        $route = array_slice(explode('_',$table['tableName']),2,-1);

        $tableOptions = json_decode($table['options']??'{}',true);
        
        $r = [];
        $r['tplCategory']  = $table['tplCategory'];//模板类型 crud tree sub
        $r['tableName']    = $table['tableName'];//表明
        $r['functionName'] = $functionName ?: '【请填写功能名称】';//功能名称
        $r['ClassName']    = $table['className'];//类名stuldy
        $r['className']    = lcfirst($table['className']);//类名camel
        $r['moduleName']   = $moduleName;//模块名 sys
        $r['BusinessName'] = ucfirst($businessName);//业务名 表后缀 stuldy
        $r["businessName"] = $businessName;//业务名 表后缀 camel
        $r['basePackage']  = explode('.',$packageName)[0];
        if($r['basePackage']!=='addons')array_unshift($route,$moduleName);
        $r['packageName'] = array_map(fn($x)=>str_replace('.','\\', $x),explode('..',$packageName));
        $r['packageName'][1] = $r['packageName'][1]??'';
        $r['namespace']=[
            'model'     =>rtrim("{$r['packageName'][0]}\\model\\{$r['packageName'][1]}",'\\'),
            'sub-model' =>rtrim("{$r['packageName'][0]}\\model\\{$r['packageName'][1]}",'\\'),
            'service'   =>rtrim("{$r['packageName'][0]}\\service\\{$r['packageName'][1]}",'\\'),
            'controller'=>rtrim(rtrim("{$r['packageName'][0]}\\controller\\".($r['basePackage']=='addons'?'admin\\':'')."{$r['packageName'][1]}",'\\').'\\'.implode('\\',$route),'\\'),
            'validate'  =>rtrim("{$r['packageName'][0]}\\validate\\{$r['packageName'][1]}",'\\'),
            'api.js'    =>rtrim("/src/api/".($r['basePackage']=='addons'?"addons/{$moduleName}/":'').implode('/',$route),'/'),
            'vue'       =>rtrim(($r['basePackage']=='addons'?"/addons/{$moduleName}/":'/').implode('/',$route),'/')."/{$businessName}",
            'routegroup'=>ltrim(ltrim($r['packageName'][1].'/','/').implode('/',$route).'/'.array_reverse(explode('_',$table['tableName']))[0],'/'),
        ];
        $r['classNames']=[
            'model'     =>$table['className'].'Model',
            'sub-model' =>$table['className'].'Model',
            'service'   =>$table['className'].'Service',
            'controller'=>ucfirst(current(array_reverse(explode('_',$table['tableName'])))).(Config::get('route.controller_suffix')?'Controller':''),
            'validate'  =>$table['className'].'Validate',
        ];
        $r['author'] = $table['functionAuthor'];//功能作者
        $r['datetime'] = date('Y-m-d H:i:s');//时间
        $r['pkColumn'] = $table['columns']->filter(fn($x)=>$x['isPk'])->toArray()[0];
        $r['importList'] = [];//getImportList($table);//导入包名
        $r['permissionPrefix'] = self::getPermissionPrefix($moduleName, $businessName);//权限前缀 用于拼接后缀list delete等
        $r['columns'] = $table['columns']->order('sort')->toArray();//字段列表
        $r['table'] = $table->toArray();
        $r['dicts'] = self::getDicts($table);
        $r['relations'] = $tableOptions['relations']??[];
        $r['parentMenuId'] = ($tableOptions[GenConstants::PARENT_MENU_ID]??0)?:self::DEFAULT_PARENT_MENU_ID;//父菜单ID
        if ($tplCategory == GenConstants::TPL_TREE){
            $treeCode       = Str::camel($tableOptions[GenConstants::TREE_CODE]??'');
            $treeParentCode = Str::camel($tableOptions[GenConstants::TREE_PARENT_CODE]??'');
            $treeName       = Str::camel($tableOptions[GenConstants::TREE_NAME]??'');
    
            $r['treeCode'] = $treeCode;
            $r['treeParentCode'] = $treeParentCode;
            $r['treeName'] = $treeName;
            $r['expandColumn'] = self::getExpandColumn($table);
            if (isset($tableOptions[GenConstants::TREE_PARENT_CODE])){
                $r['tree_parent_code'] = $tableOptions[GenConstants::TREE_PARENT_CODE];
            }
            if (isset($tableOptions[GenConstants::TREE_NAME])){
                $r['tree_name'] = $tableOptions[GenConstants::TREE_NAME];
            }
        }
        if ($tplCategory==GenConstants::TPL_SUB){
        //     $r['subTable'] = $table['subTable'];
        //     $r['subTableName'] = $table['subTableName'];
        //     $r['subTableFkName'] = $table['subTableFkName'];
        //     $r['subTableFkClassName'] = Str::studly($subTableFkName);
        //     $r['subTableFkclassName'] = Str::camel($subTableFkName);
            $r['subClassName'] = $table['subTable']['className'];
        //     $r['subclassName'] = lcfirst($table['subTable']['className']);
        //     $r['subImportList'] = [];//getImportList($table['subTable']);
        }
        return $r;
    }
	
	/**
     * 获取模板信息
     * @param tplCategory 生成的模板
     * @param tplWebType 前端类型
     * @return 模板列表
     */
	public static function getTemplateList(string $tplCategory=GenConstants::TPL_CRUD,string $tplWebType='')
    {
        $useWebType = 'vue';
        if ($tplWebType == 'element-plus'){
            $useWebType = 'vue/v3';
        }
        $templates = [];
        $templates[] = 'php/model.php';
        // $templates[] = 'php/service.php';
        $templates[] = 'php/controller.php';
        // $templates[] = 'php/validate.php';
        // $templates[] = 'php/lang.php';
        $templates[] = 'sql/sql';
        $templates[] = 'js/api.js';
        if ($tplCategory == GenConstants::TPL_CRUD){
            $templates[] = $useWebType . '/index.vue';
        }else if ($tplCategory == GenConstants::TPL_TREE){
            $templates[] = $useWebType . '/index-tree.vue';
        }else if ($tplCategory == GenConstants::TPL_SUB){
            $templates[] = $useWebType . '/index.vue';
            // $templates[] = 'php/sub-domain.php';
        }
	    return $templates;
	}

    /**
     * 获取文件名
     */
    public static function getFileName(string $template, GenTableModel $table):string
    {
        // 文件名称
        $fileName = '';
        // 包路径
        $packageName = $table['packageName'];
        // 模块名
        $moduleName = $table['moduleName'];
        // 大写类名
        $className = $table['className'];
        // 业务名称
        $businessName = $table['businessName'];

        if ($packageName=='addons.'.$moduleName) {
            // deeppath
            $deepPath = array_slice(explode('_',$table['tableName']),2);
            $deepPath[count($deepPath)-1] = ucfirst($deepPath[count($deepPath)-1]);
            $deepPath = implode('/',$deepPath);

            if (str_ends_with($template,'model.php'))
            {
                $fileName = sprintf('model/%sModel.php', $className);
            }
            else if (str_ends_with($template,'controller.php'))
            {
                $fileName = sprintf('controller/admin/%s.php', $deepPath);
            }
            else if (str_ends_with($template,'service.php'))
            {
                $fileName = sprintf('service/%sService.php', $className);
            }
            else if (str_ends_with($template,'validate.php'))
            {
                $fileName = sprintf('validate/%sValidate.php', $deepPath);
            }
            else if (str_ends_with($template,'lang.php'))
            {
                $fileName = sprintf('lang/%s.php', $deepPath);
            }
            else if (str_ends_with($template,'sql'))
            {
                $fileName = $className . 'Menu.sql';
            }
            else if (str_ends_with($template,'api.js'))
            {
                $fileName = sprintf('src/api/addons/%s/%s.js', $moduleName, strtolower($deepPath));
            }
            else if (str_ends_with($template,'index.vue'))
            {
                $fileName = sprintf('src/views/addons/%s/%s/index.vue', $moduleName, strtolower($deepPath));
            }
            else if (str_ends_with($template,'index-tree.vue'))
            {
                $fileName = sprintf('src/views/addons/%s/%s/index.vue', $moduleName, strtolower($deepPath));
            }

            return $fileName;
        }

        // deeppath
        $deepPath = array_slice(explode('_',$table['tableName']),1);
        $deepPath[count($deepPath)-1] = ucfirst($deepPath[count($deepPath)-1]);
        $deepPath = implode('/',$deepPath);

        if (str_ends_with($template,'model.php'))
        {
            $fileName = sprintf('model/admin/%s/%sModel.php',$moduleName, $className);
        }
        else if (str_ends_with($template,'controller.php'))
        {
            $fileName = sprintf('controller/admin/%s/%s.php', $moduleName,$deepPath);
        }
        else if (str_ends_with($template,'service.php'))
        {
            $fileName = sprintf('service/admin/%s/%sService.php',$moduleName, $className);
        }
        else if (str_ends_with($template,'validate.php'))
        {
            $fileName = sprintf('validate/admin/%s/%sValidate.php', $moduleName,$deepPath);
        }
        else if (str_ends_with($template,'lang.php'))
        {
            $fileName = sprintf('lang/admin/%s/%s.php', $moduleName,$deepPath);
        }
        else if (str_ends_with($template,'sql'))
        {
            $fileName = $className . 'Menu.sql';
        }
        else if (str_ends_with($template,'api.js'))
        {
            $fileName = sprintf('src/api/%s/%s.js', $moduleName, strtolower($deepPath));
        }
        else if (str_ends_with($template,'index.vue'))
        {
            $fileName = sprintf('src/views/%s/%s/index.vue', $moduleName, strtolower($deepPath));
        }
        else if (str_ends_with($template,'index-tree.vue'))
        {
            $fileName = sprintf('src/views/%s/%s/index.vue', $moduleName, strtolower($deepPath));
        }
        return $fileName;
    }

    /**
     * 根据列类型获取字典组
     * 
     * @param genTable 业务表对象
     * @return 返回字典组
     */
    public static function getDicts(GenTableModel $table):String
    {
        $dicts = [];
        self::addDicts($dicts, $table['columns']->all());
        if ($table['subTable']){
            self::addDicts($dicts, $table['subTable']['columns']->all());
        }
        $dicts = array_unique($dicts);
        return implode(',',$dicts);
    }

    /**
     * 添加字典列表
     * 
     * @param dicts 字典列表
     * @param columns 列集合
     */
    public static function addDicts(array &$dicts, array $columns)
    {
        foreach ($columns as $column){
            if (!$column['isSuperColumn'] && $column['dictType'] 
                && in_array($column['htmlType'],[
                                GenConstants::HTML_SELECT,
                                GenConstants::HTML_RADIO,
                                GenConstants::HTML_CHECKBOX]))
            {
                $dicts[] = "'" . $column['dictType'] . "'";
            }
        }
    }
	
    /**
     * 获取权限前缀
     *
     * @param moduleName 模块名称
     * @param businessName 业务名称
     * @return 返回权限前缀
     */
    public static function getPermissionPrefix(string $moduleName, string $businessName):string
    {
        return sprintf('%s:%s',$moduleName,$businessName);
    }

    /**
     * 获取需要在哪一列上面显示展开按钮
     *
     * @param genTable 业务表对象
     * @return 展开按钮列序号
     */
    public static function getExpandColumn(GenTableModel $table):int
    {
        $options = $table['options'];
        $params = json_decode($options,true);
        $treeName = $params[GenConstants::TREE_NAME];
        $num = 0;
        foreach ($table['columns']->all() as $column){
            if ($column['isList']){
                $num++;
                $columnName = $column['columnName'];
                if ($columnName == $treeName){
                    break;
                }
            }
        }
        return $num;
    }
}
GeneratorService::init();