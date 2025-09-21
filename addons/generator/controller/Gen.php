<?php
namespace addons\generator\controller;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use think\facade\Cache;
use addons\generator\model\GenTableModel;
use addons\generator\model\GenTableColumnModel;
use addons\generator\library\GeneratorService;
use addons\generator\library\GenConstants;
use addons\generator\library\GenUtils;
use helper\SqlUtil;


/**
 * Gen
 */
class Gen extends \app\BaseController
{
    protected $noNeedLogin = [];

    protected function initialize(){
        parent::initialize();
        $this->model = new GenTableModel;
    }

    /**
     * 查询代码生成列表
     */
    #[PreAuthorize('hasPermi','tool:gen:list')]
    public function list(){
        $where = [];
        $where['params'] = [
            'beginTime'=>input('params.beginTime',''),
            'endTime'=>input('params.endTime',''),
        ];
        $where['tableName'] = input('tableName','');
        $where['tableComment'] = input('tableComment','');
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);

        $r = $this->model->search($where);
        $this->success($r);
    }

    #[PreAuthorize('hasPermi','tool:gen:query')]
    public function getInfo($id){
        $table = $this->model->find($id);
        $table['columns'] = $table->columns->all();
        $options = json_decode($table['options']?:'',true);
        $table['treeCode'] = $options[GenConstants::TREE_CODE]??'';
        $table['treeParentCode'] = $options[GenConstants::TREE_PARENT_CODE]??'';
        $table['treeName'] = $options[GenConstants::TREE_NAME]??'';
        $table['parentMenuId'] = $options[GenConstants::PARENT_MENU_ID]??'';
        $table['parentMenuName'] = $options[GenConstants::PARENT_MENU_NAME]??'';
        $tables = $this->model->select()->all();
        $tables = array_map(function($table){
                $table['columns'] = $table->columns->all();
                return $table;
            },$tables);
        $list = GenTableColumnModel::where('table_id',$id)->select();
        $this->success(['data'=>[
                'info'=>$table,
                'rows'=>$list->toArray(),
                'tables'=>$tables,
                'relations'=>$options['relations']??[],
            ]]);
    }

    #[PreAuthorize('hasPermi','tool:gen:list')]
    public function dblist(){
        $where = [];
        $where['tableName'] = input('tableName','');
        $where['tableComment'] = input('tableComment','');
        $where['pageNum'] = input('pageNum/d',1);
        $where['pageSize'] = input('pageSize/d',10);
        $r = $this->model->searchDb($where);
        $this->success($r);
    }

    #[PreAuthorize('hasPermi','tool:gen:list')]
    public function column($tableId){
        $table = $this->model->find($tableId);
        $table->columns();
        $r = ['total'=>count($table['columns']),'rows'=>$table['columns']];
        $this->success($r);
    }

    #[PreAuthorize('hasRole','admin')]
    public function createTable(){
        $sql = input('sql/s');

        try {
            SqlUtil::filterKeyword($sql);
            $sqlStatements = explode(';',$sql);
            $tableNames = [];
            foreach ($sqlStatements as $sqlStatement){
                if(str_starts_with(strtoupper($sqlStatement),'CREATE TABLE')){
                    if(\think\facade\Db::execute($sqlStatement)){
                        if (preg_match('/CREATE TABLE `([^`]+)`/i', $sql, $matches)) {
                            $tableName = $matches[1];
                            $tableNames[] = $tableName;
                        }
                    }
                }
            }
            $r = GenTableModel::searchDbByName($tableNames);
            $tableList = $r['rows'];
            $this->importGenTableModel($tableList,$this->auth->user_name);
            $this->success();
        } catch (\Exception $e) {
            $this->error('创建表结构异常:'.$e);
        }

        $this->success();
    }

    #[PreAuthorize('hasPermi','tool:gen:import')]
    public function importTable(){
        $tableNames = explode(',',input('tables/s',''));
        $r = GenTableModel::searchDbByName($tableNames);
        $tableList = $r['rows'];
        try{
            $this->importGenTableModel($tableList,$this->auth->user_name);
        }catch (\Exception $e){
            throw $e;
        }
        $this->success();
    }

    private function importGenTableModel(array $tableList,string $operName){
        foreach ($tableList as $table){
            $tableName = $table['tableName'];

            GenUtils::initTable($table,$operName);

            $table = GenTableModel::create($table->toArray());
            if ($table['tableId']){
                // 保存列信息
                $r = GenTableColumnModel::selectDbColumnsByName($tableName);
                $genTableColumns = $r['rows'];
                foreach ($genTableColumns as $column){
                    GenUtils::initColumnField($column, $table);
                    GenTableColumnModel::create($column->toArray());
                }
            }
        }
    }

    #[PreAuthorize('hasPermi','tool:gen:edit')]
    public function edit(){
        $id = input(\think\helper\Str::camel($this->model->getPk()),'');
        $data = $this->model->find($id);
        if (!$data) {
            $this->error('资源不存在');
        }
        $table = $this->request->param();
        $table['options'] = json_encode($table['params']);
        $r = $data->save($table);
        if (!$r) {
            $this->error('操作失败');
        }
        $columns = input('columns/a',[]);
        foreach ($columns as $column){
            GenTableColumnModel::update($column);
        }
        $this->success();
    }

    #[PreAuthorize('hasPermi','tool:gen:remove')]
    public function remove($ids){
        $ids = explode(',',$ids);
        GenTableModel::destroy([$this->model->getPk()=>$ids]);
        GenTableColumnModel::destroy([$this->model->getPk()=>$ids]);
        $this->success();
    }

    /**
     * 预览代码
     */
    #[PreAuthorize('hasPermi','tool:gen:preview')]
    public function preview($tableId){
        $r = GeneratorService::previewCode($tableId);
        $this->success(['data'=>$r]);
    }

    #[PreAuthorize('hasPermi','tool:gen:code')]
    public function download($tableName){
        ob_start();
        $zipStream = fopen('php://output', 'w');
        $zip = new \ZipArchive();
        $res = $zip->open($zipStream, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        GeneratorService::generatorCode($tableName,$zip);
        $zip->close();
        $zipStream = ob_get_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="example.zip"');
        header('Content-Length: ' . strlen($zipData));
        die($zipStream);
    }

    #[PreAuthorize('hasPermi','tool:gen:code')]
    public function genCode($tableName){
        GeneratorService::generatorCode($tableName);
        $this->success();
    }

    #[PreAuthorize('hasPermi','tool:gen:edit')]
    public function synchDb(string $tableName=''){
        $table = $this->model->where('table_name',$tableName)->find();
        if(!$table){
            $this->error('数据不存在');
        }
        $table->columns();
        $tableColumns = $table['columns'];
        $tableColumnNames = $tableColumns->column('columnName');
        $tableColumnMap = array_combine($tableColumnNames,$tableColumns->all());

        $r = GenTableColumnModel::selectDbColumnsByName($tableName);
        $dbColumns = $r['rows'];
        if (!$dbColumns){
            $this->error('同步数据失败，原表结构不存在');
        }
        $dbColumnNames = array_column($dbColumns,'columnName');

        foreach ($dbColumns as $column){
            GenUtils::initColumnField($column, $table);
            if (in_array($column['columnName'],$tableColumnNames)){
                $prevColumn = $tableColumnMap[$column['columnName']];
                $column['columnId'] = $prevColumn['columnId'];
                if ($column['isList']){
                    // 如果是列表，继续保留查询方式/字典类型选项
                    $column['dictType'] = $prevColumn['dictType'];
                    $column['queryType'] = $prevColumn['queryType'];
                }
                if ($prevColumn['isRequired'] && !$column['isPk']
                        && ($column['isInsert'] || $column['isEdit'])
                        && ($column['isUsableColumn'] || !$column['isSuperColumn'])
                    ){
                    // 如果是(新增/修改&非主键/非忽略及父属性)，继续保留必填/显示类型选项
                    $column['isRequired'] = $prevColumn['isRequired'];
                    $column['htmlType'] = $prevColumn['htmlType'];
                }
                $prevColumn->save($column);
            }else{
                $column->save();
            }
        }

        $delColumns = array_values(array_filter($tableColumns->toArray(),fn($column)=>!in_array($column['columnName'],$dbColumnNames)));
        if ($delColumns){
            GenTableColumnModel::destroy(array_column($delColumns,'columnId'));
        }
        $this->success(['data'=>array_column($delColumns,'columnId'),'genc'=>$tableColumns,'dbc'=>$dbColumnNames]);
    }

    #[PreAuthorize('hasPermi','tool:gen:code')]
    public function batchGenCode(){
        $tableNames = input('tables/s','');
        $tableNames or die;
        $tableNames = explode(',',$tableNames);

        $file = tempnam(sys_get_temp_dir(), 'gen_');
        $zip = new \ZipArchive;
        $zip->open($file,\ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($tableNames as $tableName) {
            GeneratorService::generatorCode($tableName,$zip);
        }
        $zip->close();
        abort(download($file,"ruoyi-tp.zip"));
    }
}
