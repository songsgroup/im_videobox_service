<?php
declare (strict_types = 1);

namespace app\model\admin;

use think\annotation\model\relation\BelongsTo;

use think\Exception;

/**
 * MenuModel
 */
#[BelongsTo("parent", MenuModel::class, "parent_id",'menu_id')]
class MenuModel extends \app\BaseModel
{
    // 表名
    protected $name = 'sys_menu';
    protected $pk = 'menu_id';

    protected $mapping = [
// menu_id
// menu_name
// parent_id
// order_num
// path
// component
// query
// is_frame
// is_cache
// menu_type
// visible
// status
// perms
// icon
// create_by
// create_time
// update_by
// update_time
// remark
        ];
        
    // 字段默认值
    protected $default = [
            'menu_id'=>null,
            'menu_name'=>'',
            'parent_id'=>0,
            'order_num'=>0,
            'path'=>'#',
            'component'=>'',
            'query'=>'',
            'is_frame'=>1,
            'is_cache'=>0,
            'menu_type'=>'C',
            'visible'=>0,
            'status'=>0,
            'perms'=>'',
            'icon'=>'#',
            'create_by'=>'',
            // 'create_time'=>null,
            'update_by'=>'',
            // 'update_time'=>null,
            'remark'=>'',
        ];
    
    public static function all()
    {
        return self::where('status',0)->select()->toArray();
    }

    public static function getMenus()
    {
        return self::where('status',0)->where('menu_type','<>','F')->select()->toArray();
    }

    public static function getMenusTree($parentId=0,$hasBtn=false)
    {
        $menus = $hasBtn?self::all():self::getMenus();
        $r = array_values(array_filter($menus,fn($x)=>$x['parentId']==$parentId));
        array_multisort($r,SORT_ASC,SORT_NUMERIC,array_column($r,'orderNum'));
        $r = array_map(function ($x)use($parentId){
                $r = [
                    'id'=>$x['menuId'],
                    'label'=>$x['menuName'],
                ];
                if ($children = self::getMenusTree($x['menuId'])) {
                    $r['children'] = $children;
                }
                return $r;
            }, $r);
        return $r;
    }

    public static function enable($name)
    {
        if (!$name) {
            throw new Exception("菜单名称不能为空", 1);
        }
        $menu = self::where('path',$name)->find();
        $menu->status = 0;
        $menu->save();
        return true;
    }

    public static function disable($name)
    {
        if (!$name) {
            throw new Exception("菜单名称不能为空", 1);
        }
        $menu = self::where('path',$name)->find();
        $menu->status = 1;
        $menu->save();
        return true;
    }

    public static function remove($name)
    {
        if (!$name) {
            throw new Exception("菜单名称不能为空", 1);
        }
        $menu = self::where('path',$name)->find();
        if (!$menu) {
            return true;
        }
        $tree = self::getMenusTree($menu['menuId']);
        $iterator = new \RecursiveIteratorIterator(
            new \RecurisveArrayIterator($tree),
            \RecursiveIteratorIterator::SELF_FIRST);
        $menuIds = [];
        foreach ($iterator as $key=>$value) {
            if($key=='menuId')$menuIds[]=$value;
        }
        $r = self::destroy($menuIds);
        return true;
    }

    public static function add(array $data)
    {
        // 递归添加菜单
        // $menu = [
        //     'menuName'  => $data['menuName'],
        //     'path'      => $data['path']??'',
        //     'component' => $data['component']??'',
        //     'perms'     => $data['perms']??'',
        //     'icon'      => $data['icon']??'',
        //     'orderNum'  => $data['orderNum']??self::getNextOrderNum($data['parentId']??0),
        //     'parentId'  => $data['parentId']??0,
        // ];
        // if (!isset($data['sublist'])) {
        //     $menu['menuType'] = 'F';
        //     $menu = self::create($menu);
        //     return $menu;
        // }
        // foreach ($data['sublist'] as $key => $sub) {
        //     if (!isset($menu['menuId'])) {
        //         $menu['menuType'] = isset($sub['sublist'])?'M':'C';
        //         $menu = self::create($menu);
        //     }
        //     $sub['parentId'] = $menu['menuId'];
        //     $sub['orderNum'] = $key+1;
        //     self::add($sub);
        // }
        // return true;

        $q = [];
        self::buildGroup($data,$q);
        usort($q,fn($a,$b)=>
            $a['rank']>=$b['rank']&&
            ['M'=>0,'C'=>0,'F'=>2][$a['menuType']]>['M'=>0,'C'=>0,'F'=>2][$b['menuType']]);

        try {
            self::startTrans();
            foreach ($q as $k=>$menu) {
                unset($menu['key']);
                unset($menu['rank']);
                if ($menu['parent']) {
                    if ($parent = array_filter($q,fn($x)=>$x['key']==$menu['parent'])) {
                        $menu['parentId'] = current($parent)['menuId'];
                    }
                }
                unset($menu['parent']);
                $menu = self::create($menu);
                $q[$k]['menuId'] = $menu['menuId'];
            }
            self::commit();
        } catch (Exception $e) {
            self::rollback();
        }
        return true;
    }

    private static function buildGroup(array $data,array &$q)
    {
        $menu = [
            'menuName'  => $data['menuName'],
            'path'      => $data['path']??'',
            'component' => $data['component']??'',
            'perms'     => $data['perms']??'',
            'icon'      => $data['icon']??'',
            'orderNum'  => $data['orderNum']??self::getNextOrderNum($data['parentId']??0),
            'parentId'  => $data['parentId']??0,
            'parent'    => $data['parent']??0,
            'rank'      => $data['rank']??0,
            'key'       => count($q)+1,
        ];
        $sublist = $data['sublist']??[];
        unset($data['sublist']);
        if (!count($sublist)) {
            $menu['menuType'] = 'F';
            $q[] = $menu;
            return;
        }
        $menu['menuType'] = isset($sublist[0]['sublist'])?'M':'C';
        $q[] = $menu;
        foreach ($sublist as $key => $sub) {
            $sub['parent'] = $menu['key'];
            $sub['orderNum'] = $key+1;
            $sub['rank'] = $menu['rank']+1;
            self::buildGroup($sub,$q);
        }
        return true;
    }

    private static function getNextOrderNum($parentId=0){
        return self::where('parent_id',$parentId)->max('order_num')+1;
    }

}
