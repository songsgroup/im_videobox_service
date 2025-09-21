<?php
namespace app\controller\admin;

use think\annotation\route\Route;
use think\annotation\route\Group;
use app\PreAuthorize;
use think\Exception;


/**
 * Common
 */
#[Group('admin/common')]
class Common extends \app\BaseController
{
    protected $noNeedLogin = [];

    /**
     * 通用下载请求
     *
     * @param fileName 文件名称
     * @param delete 是否删除
     */
    #[Route('GET','download')]
    public function fileDownload(string $fileName, bool $delete)
    {
        try {
            // TODO:
            if ($delete) {
                unlink($filePath);
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success(null,'');
    }

    /**
     * 通用上传请求（单个）
     */
    #[Route('POST','upload')]
    public function uploadFile()
    {
        try {
            // TODO:
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success(null,'');
    }

    /**
     * 通用上传请求（多个）
     */
    #[Route('POST','uploads')]
    public function uploadFiles()
    {
        try {
            // TODO:
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success(null,'');
    }

    /**
     * 本地资源通用下载
     */
    #[Route('GET','download/resource')]
    public function resourceDownload(string $resource){
        try {
            // TODO:
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success(null,'');
    }
}
