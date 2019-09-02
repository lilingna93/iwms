<?php
ini_set('memory_limit','356M');
define('APP_DIR', dirname(__FILE__) . '/../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';

use Wms\Dao\SpuDao;
use Wms\Dao\SkudictDao;
use Common\Service\ExcelService;

//帮助锦辉批量导入货品数据脚本（非主仓数据）
$time = venus_script_begin("初始化iwms数据库的SPUSKU数据");

$files = "C:/Users/gfz_1/Desktop/iwms_spu/iwms_spu_sku_import.xlsx";
$datas = ExcelService::GetInstance()->uploadByShell($files);
array_pop($datas);//过滤最后一个类型说明表
$dicts = array(
    "A" => "spu_code",//spu品类编号
    "B" => "spu_subtype",//spu二级分类编号
    "F" => "spu_storetype",//spu货品仓储方式
    "H" => "spu_name",//spu货品名称
    "I" => "spu_brand",//spu品牌
    "J" => "spu_from",//spu货品产地
    "K" => "sup_code",//供货商
    "L" => "spu_norm",//spu规格
    "M" => "spu_unit",//spu计量单位
    "N" => "spu_cunit",//可计算最小单位
    "O" => "spu_mark",//spu备注
);

$skuList = array();
foreach ($datas as $sheetName => $list) {
    unset($list[0]);
    $skuList = array_merge($skuList, $list);
}
$dataArr = array();
venus_db_starttrans();//启动事务
$result = true;

foreach ($skuList as $index => $skuItem) {
    $skuData = array();
    foreach ($dicts as $col => $key) {
        $skuData[$key] = isset($skuItem[$col]) ? $skuItem[$col] : "";
    }

    //验证二级分类是否符合规定长度
    if (!empty($skuData['spu_subtype']) && strlen($skuData['spu_subtype']) <= 5) {
        $skuData['spu_type'] = substr($skuData['spu_subtype'], 0, 3);//一级分类编号

    }else if(!empty($skuData['spu_subtype']) && strlen($skuData['spu_subtype']) > 5){
        venus_throw_exception(5004, $skuData['spu_name']);
    }

    if (trim($skuData['spu_name']) == '' || trim($skuData['spu_subtype']) == '' || trim($skuData['spu_storetype']) == '') {
        if (trim($skuData['spu_name']) == '' && trim($skuData['spu_subtype']) == '' && trim($skuData['spu_storetype']) == '') {
            continue;
        } else {
            if (empty($skuData['spu_name'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "货品名称不能为空");
                return false;
            }

            if (empty($skuData['spu_subtype'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "货品二级分类不能为空");
                return false;
            }

            if (empty($skuData['spu_brand'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "spu货品品牌不能为空");
                return false;
            }

            if (empty($skuData['sup_code'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "spu货品供货商不能为空");
                return false;
            }

            if (empty($skuData['spu_norm'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "spu货品规格不能为空");
                return false;
            }

            if (empty($skuData['spu_unit'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "spu货品单位不能为空");
                return false;
            }

            // spu货品规格不能为空
            if (empty($skuData['spu_cunit'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "spu货品最小规格单位不能为空");
                return false;
            }

            // 仓储方式（常温，冷冻，冷藏）不能为空
            if (empty($skuData['spu_storetype'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "货品仓储方式不能为空");
                return false;
            }
        }
    } else {
        $condition = array(
            "spu_subtype" => $skuData['spu_subtype'],
            "spu_brand" => $skuData['spu_brand'],
            "spu_storetype" => $skuData['spu_storetype'],
            "spu_mark" => $skuData['spu_mark'],
            "spu_name" => $skuData['spu_name'],
            "spu_norm" => $skuData['spu_norm'],
            "spu_unit" => $skuData['spu_unit'],
            "spu_cunit" => $skuData['spu_cunit'],
            "sup_code" => $skuData['sup_code']
        );

        $jsonCon = json_encode($condition);
        if (in_array($jsonCon, $dataArr)) {//检测excel表里是否有重复的数据
            $redata = json_decode($jsonCon, true);
            $name = $redata['spu_name'];
            venus_throw_exception(5001, $name);
        } else {
            $dataArr[] = $jsonCon;
            //检测wms_spu、wms_sku数据表是否已存在该品类
            if (empty($skuData["spu_code"])) {
                $getField = 'spu_name';
                $totalCount = SpuDao::getInstance()->queryOneByCondition($condition, $getField);
                if (!empty($totalCount)) {
                    venus_throw_exception(5002, $totalCount);
                }
            }

            $spuDatas = array(
                "spucode" => "",
                "skucode" => "",
                "sputype" => $skuData['spu_type'],
                "spusubtype" => $skuData["spu_subtype"],
                "spubrand" => $skuData["spu_brand"],
                "spuabname" => "",
                "spustoretype" => $skuData["spu_storetype"],
                "spuname" => $skuData["spu_name"],
                "spufrom" => $skuData["spu_from"],
                "spunorm" => $skuData["spu_norm"],
                "spuunit" => $skuData["spu_unit"],
                "spumark" => $skuData["spu_mark"],
                "spucunit" => $skuData["spu_cunit"],
                "supcode" => $skuData["sup_code"],
            );

            if (empty($skuData["spu_code"])) {
                $result = $result && SkudictDao::GetInstance("WA000001")->insert($spuDatas);
            }
            if (!$result) {
                venus_db_rollback();
                return false;
            }
        }
    }
}

if ($result) {
    venus_db_commit();
    return true;
} else {
    venus_db_rollback();
    return false;
}







