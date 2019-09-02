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

//将主仓所有spu、sku货品数据导入到辅仓（主仓货品数据）
$time = venus_script_begin("初始化iwms数据库的SPUSKU数据");

$files = "C:/Users/gfz_1/Desktop/spu/iwms_spu_sku.xlsx";
//echo file_exists($files)?"yes":"no";exit();
$datas = ExcelService::GetInstance()->uploadByShell($files);
//array_pop($datas);//过滤最后一个类型说明表
$dicts = array(
    "A" => "sku_code",//sku品类编号
    "B" => "spu_code",//sku品类编号
    "E" => "spu_subtype",//spu二级分类编号
    "G" => "spu_storetype",//spu仓储方式
    "I" => "spu_name",//spu货品名称
    "J" => "spu_brand",//spu品牌
    "K" => "spu_from",//spu货品产地
    "L" => "spu_mark",//sku备注
    "M" => "spu_img",//spu图片
    "N" => "spu_cunit",//可计算最小单位
    "O" => "spu_norm",//spu规格
    "P" => "spu_unit",//spu计量单位
    "Q" => "spu_bprice",//spu采购价
    "R" => "spu_sprice",//spu销售价
    "U" => "profit_price",//spu销售价
    "V" => "sku_norm",//sku规格
    "W" => "sku_unit",//sku单位
    "X" => "spu_count",//单位sku含spu数量
    "Y" => "sku_mark"//sku备注
);

$skuList = array();

foreach ($datas as $sheetName => $list) {
    unset($list[0]);
    $skuList = array_merge($skuList, $list);
}
$dataArr = array();
venus_db_starttrans();//启动事务
$result = true;
$spuCount = SpuDao::getInstance()->queryCountByCondition();
if ($spuCount > 0) {
    $i = $spuCount;
} else {
    $i = 0;
}

foreach ($skuList as $index => $skuItem) {

    $skuData = array();
    foreach ($dicts as $col => $key) {
        $skuData[$key] = isset($skuItem[$col]) ? $skuItem[$col] : "";
    }

    //验证二级分类是否符合规定长度
    if (!empty($skuData["sku_code"])) {
        if (!empty($skuData['spu_subtype']) && strlen($skuData['spu_subtype']) <= 5) {
            $skuData['spu_type'] = substr($skuData['spu_subtype'], 0, 3);//一级分类编号

        } else if (!empty($skuData['spu_subtype']) && strlen($skuData['spu_subtype']) > 5) {
            venus_throw_exception(5004, $skuData['spu_name']);
        }
    }

    if (trim($skuData['spu_name']) == '' || trim($skuData['spu_subtype']) == '' || trim($skuData['spu_storetype']) == '') {
        if (trim($skuData['spu_name']) == '' && trim($skuData['spu_subtype']) == '' && trim($skuData['spu_storetype']) == '') {
            continue;
        } else {
            //品类名称不能为空
            if (empty($skuData['spu_name'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "货品名称不能为空");
                return false;
            }

            // 二级类目不能为空
            if (empty($skuData['spu_subtype'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "货品二级分类不能为空");
                return false;
            }

            // sku货品规格不能为空
            if (empty($skuData['sku_norm'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "sku货品规格不能为空");
                return false;
            }

            // sku货品规格不能为空
            if (empty($skuData['spu_unit'])) {
                venus_db_rollback();//回滚事务
                venus_throw_exception(1, "spu货品单位不能为空");
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
            "spu_count" => $skuData['spu_count'],
            "spu_name" => $skuData['spu_name'],
            "spu_norm" => $skuData['spu_norm'],
            "spu_unit" => $skuData['spu_unit'],
            "sku_norm" => $skuData['sku_norm'],
            "sku_unit" => $skuData['sku_unit'],
            "sku_mark" => $skuData['sku_mark']
        );

        $jsonCon = json_encode($condition);
        if (in_array($jsonCon, $dataArr)) {//检测excel表里是否有重复的数据
            $redata = json_decode($jsonCon, true);
            $name = $redata['spu_name'];
            venus_throw_exception(5001, $name);
        } else {
            $dataArr[] = $jsonCon;
            //检测iwms_spu、iwms_sku数据表是否已存在该品类
            $skuDictresult = SkudictDao::getInstance()->queryByCode($skuData['spu_code']);
            if(empty($skuDictresult)){
                $skuDictData = array(
                    "spucode" => $skuData['spu_code'],
                    "skucode" => $skuData['sku_code'],
                    "spuname" => $skuData["spu_name"],
                    "spuabname" => "",
                    "sputype" => $skuData['spu_type'],
                    "spusubtype" => $skuData["spu_subtype"],
                    "spustoretype" => $skuData["spu_storetype"],
                    "spubrand" => $skuData["spu_brand"],
                    "spufrom" => $skuData["spu_from"],
                    "spunorm" => $skuData["spu_norm"],
                    "spucunit" => 1,
                    "spuunit" => $skuData["spu_unit"],
                    "spumark" => $skuData["spu_mark"]
                );
                $result = $result && SkudictDao::GetInstance("WA000001")->insert($skuDictData);
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







