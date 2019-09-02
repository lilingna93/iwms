<?php
/**
 * Created by PhpStorm.
 * User: lilingna
 * Date: 2018/12/27
 * Time: 10:39
 */
define('IS_MASTER', false);
define('APP_DIR', '/home/dev/venus-mini/');
//define('APP_DIR', '/home/iwms/app/');//正式站目录为/home/iwms/app/
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';
//在命令行中输入 chcp 65001 回车, 控制台会切换到新的代码页,新页面输出可为中文
$time = venus_script_begin("开始修改库存数据");

//读取excel里面修改spu比例的数据
$excelSpuData = readExcelSpuData();
//过滤空数据
$delNullExcelData = delNullExcelData($excelSpuData);
//按照修改的不同情况过滤(乘以，除以)
$mulAndDivData = manipulationData($delNullExcelData['skuData']);
$mulSpuData = $mulAndDivData['mul'];//乘法情况
$divSpuData = $mulAndDivData['div'];//除法情况
$errorSpuData = issetErrorDiv($divSpuData);

//修改库存(不提交，输出sql语句，统计修改数据)
if (!empty($errorSpuData)) {
    echo json_encode($errorSpuData);
    exit();
} else {
    venus_db_starttrans();
    $isSuccess = true;
    if (!empty($mulSpuData)) {
        foreach ($mulSpuData as $spCode => $mulSpuDatum) {
            $isSuccess = $isSuccess && updateGoodsBySpuCodeAndMul($spCode, $mulSpuDatum);
            $isSuccess = $isSuccess && updateGoodsbatchBySpuCodeAndMul($spCode, $mulSpuDatum);
            $isSuccess = $isSuccess && updateGoodstoredBySpuCodeAndMul($spCode, $mulSpuDatum);
            $issetIgs = queryIgoodsSpuListBySpuCode($spCode, 0, 10000);
            if (!empty($issetIgs)) {
                $isSuccess = $isSuccess && updateIgoodsBySpuCodeAndMul($spCode, $mulSpuDatum);
                $isSuccess = $isSuccess && updateIgoodsentBySpuCodeAndMul($spCode, $mulSpuDatum);
            }
        }
    }
    if (!empty($divSpuData)) {
        foreach ($divSpuData as $spCode => $divSpuDatum) {
            $isSuccess = $isSuccess && updateGoodsBySpuCodeAndDiv($spCode, $divSpuDatum);
            $isSuccess = $isSuccess && updateGoodsbatchBySpuCodeAndDiv($spCode, $divSpuDatum);
            $isSuccess = $isSuccess && updateGoodstoredBySpuCodeAndDiv($spCode, $divSpuDatum);
            $issetIgs = queryIgoodsSpuListBySpuCode($spCode, 0, 10000);
            if (!empty($issetIgs)) {
                $isSuccess = $isSuccess && updateIgoodsBySpuCodeAndDiv($spCode, $divSpuDatum);
                $isSuccess = $isSuccess && updateIgoodsentBySpuCodeAndDiv($spCode, $divSpuDatum);
            }
        }
    }
}
if ($isSuccess) {
    //输出信息
    echo "倍数为空:" . PHP_EOL;
    echo json_encode($delNullExcelData["emptySku"]) . PHP_EOL;
    echo "规格错误:" . PHP_EOL;
    echo json_encode($mulAndDivData['errorNorm']) . PHP_EOL;
    echo "暂无库存:" . PHP_EOL;
    echo json_encode($mulAndDivData['emptyGoods']) . PHP_EOL;
    $count = count($mulAndDivData['mul']) + count($mulAndDivData['div']);
    echo "共有" . count($delNullExcelData['skuData']) . "条数据发生改变" . PHP_EOL;
    echo "共有" . count($mulAndDivData['errorNorm']) . "条数据规格错误" . PHP_EOL;
    echo "共有" . count($mulAndDivData['emptyGoods']) . "条数据暂无库存" . PHP_EOL;
    echo "共有" . $count . "条数据修改库存" . PHP_EOL;
    echo "共有" . count($mulAndDivData['mul']) . "条数据修改库存乘以倍数" . PHP_EOL;
    echo "共有" . count($mulAndDivData['div']) . "条数据修改库存除以倍数" . PHP_EOL;
    venus_db_commit();
    exit();
} else {
    echo "修改失败";
    venus_db_rollback();
    exit();
}

//读取excel里面修改spu比例的数据
function readExcelSpuData()
{
    vendor("PHPExcel.PHPExcel");
    vendor("PHPExcel.Reader.Excel2007");
    $objReader = new \PHPExcel_Reader_Excel2007();
    $files = "/home/lilingna/spudata.xlsx";
//    $files = "/home/rd/spudata.xlsx";
    $PHPExcel = $objReader->load($files);
    $currentSheet = $PHPExcel->getSheet(4);
    //获取总列数
    $allColumn = $currentSheet->getHighestColumn();
    //获取总行数
    $allRow = $currentSheet->getHighestRow();

    $dataList = array();
    //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
    for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
        $itemData = array();
        //从哪列开始，A表示第一列
        for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++) {
            //数据坐标
            $address = $currentColumn . $currentRow;
            //读取到的数据，保存到数组$arr中
            $itemData[$currentColumn] = $currentSheet->getCell($address)->getValue();
            if ($itemData[$currentColumn] instanceof \PHPExcel_RichText)     //富文本转换字符串
                $itemData[$currentColumn] = $itemData[$currentColumn]->__toString();
        }
        array_push($dataList, $itemData);
    }
    unset($dataList[0]);
    unset($dataList[1]);
    return $dataList;
}

//过滤空数据
function delNullExcelData($skuList)
{
    $skuData = array();
    $emptySku = array();
    foreach ($skuList as $index => $skuItem) {
        if ($skuItem['A'] == null || $skuItem['F'] == null) {
            if ($skuItem['A'] != null && $skuItem['F'] == null) {
                $emptySku[] = $skuItem['A'];
            }
            continue;
        } else {
            $skuData[] = $skuItem;
        }
    }
    return array("skuData" => $skuData, "emptySku" => $emptySku);
}

//处理数据
function manipulationData($skuList)
{
    //获取所有库存从主仓带过来的的spu数据
    $spuGoodsData = queryDistinctSpuByCondition(0, 10000);
    $spuGoodsData = array_column($spuGoodsData, "spu_code");
    $dicts = array(
        "A" => "spCode",//编号
        "F" => "mul",//倍数(正数：乘以；负数：除以)

    );
    $mulSpuData = array();
    $divSpuData = array();
    $errorNorm = array();
    $emptyGoods = array();
    foreach ($skuList as $index => $skuItem) {
        if ($skuItem['F'] != 0) {
            if ($skuItem['F'] == 1 || $skuItem['F'] == -1) {
                $errorNorm[] = $skuItem['A'];
                continue;
            } else {
                if (in_array($skuItem['A'], $spuGoodsData)) {
                    $skuData = array();
                    foreach ($dicts as $col => $key) {
                        $skuData[$key] = isset($skuItem[$col]) ? $skuItem[$col] : "";
                    }
                    if ($skuItem['F'] > 0) {
                        $mulSpuData[$skuData['spCode']] = $skuData['mul'];
                    }
                    if ($skuItem['F'] < 0) {
                        $divSpuData[$skuData['spCode']] = 0 - $skuData['mul'];
                    }
                } else {
                    $emptyGoods[] = $skuItem['A'];
                    continue;
                }
            }
        } else {
            continue;
        }
    }
    return array("mul" => $mulSpuData, "div" => $divSpuData, "errorNorm" => $errorNorm, "emptyGoods" => $emptyGoods);
}

function queryDistinctSpuByCondition($page = 0, $count = 100)
{
    return M("Goods")->alias('goods')->field('distinct(goods.spu_code)')
        ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code")
        ->where(array("spu.sup_code" => 'SU00000000000001'))
        ->order('goods.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
}

function queryGoodsSpuListByCondition($spucode, $page = 0, $count = 100)
{
    return M("Goods")->alias('goods')->field('*,goods.spu_code,gb.sku_count gb_sku_count,gs.sku_count gs_sku_count')
        ->join("LEFT JOIN wms_goodsbatch gb ON gb.spu_code=goods.spu_code And goods.spu_code='" . $spucode . "'")
        ->join("LEFT JOIN wms_goodstored gs ON gs.gb_code=gb.gb_code And goods.spu_code='" . $spucode . "'")
        ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code And goods.spu_code='" . $spucode . "'")
        ->where(array("spu.sup_code" => 'SU00000000000001'))
        ->order('goods.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
}

function queryIgoodsSpuListBySpuCode($spucode, $page = 0, $count = 100)
{
    return M("Igoods")->alias('igo')->field('*')
        ->join("LEFT JOIN wms_igoodsent igs ON igs.igo_code=igo.igo_code")
        ->where(array("igo.spu_code" => $spucode))
        ->order('igo.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
}

function issetErrorDiv($divSpuData)
{
    $errorSpuData = array();
//过滤除法，判断是否存在除不尽情况
    foreach ($divSpuData as $spCode => $divSpuDatum) {
        $spuList = queryGoodsSpuListByCondition($spCode, 0, 10000);
        foreach ($spuList as $spu) {
            $goodsinit = bcdiv($spu['goods_init'], $divSpuDatum);
            $goodscount = bcdiv($spu['goods_count'], $divSpuDatum);
            $gbcount = bcdiv($spu['gb_count'], $divSpuDatum);
            $gbskucount = bcdiv($spu['gb_sku_count'], $divSpuDatum);
            $gsskucount = bcdiv($spu['gs_sku_count'], $divSpuDatum);
            $gsinit = bcdiv($spu['gs_init'], $divSpuDatum);
            $gscount = bcdiv($spu['gs_count'], $divSpuDatum);
            if (intval($goodsinit) != $goodsinit || intval($goodscount) != $goodscount || intval($gbcount) != $gbcount ||
                intval($gbskucount) != $gbskucount || intval($gsskucount) != $gsskucount || intval($gsinit) != $gsinit ||
                intval($gscount) != $gscount) {
                $errorSpuData[] = array($spCode => $spu['spu_name']);
            } else {
                continue;
            }
        }
    }
    return $errorSpuData;
}

function updateGoodsBySpuCodeAndMul($spuCode, $mul)
{
    $sql = "UPDATE `wms_goods` SET 
`goods_init`=`goods_init`*$mul,
`goods_count`=`goods_count`*$mul
 WHERE `spu_code`='" . $spuCode . "'";
    return M("Goods")->execute($sql);
//    return $sql;
}

function updateGoodsBySpuCodeAndDiv($spuCode, $div)
{
    $sql = "UPDATE `wms_goods` SET 
`goods_init`=`goods_init`/$div, 
`goods_count`=`goods_count`/$div
  WHERE `spu_code`='" . $spuCode . "'";
    return M("Goods")->execute($sql);
//    return $sql;
}

function updateGoodsbatchBySpuCodeAndMul($spuCode, $mul)
{
    $sql = "UPDATE `wms_goodsbatch` SET 
`gb_count`=`gb_count`*$mul,
`sku_count`=`sku_count`*$mul,
`gb_bprice`=`gb_bprice`/$mul
 WHERE `spu_code`='" . $spuCode . "'";
    return M("Goodsbatch")->execute($sql);
//    return $sql;
}

function updateGoodsbatchBySpuCodeAndDiv($spuCode, $div)
{
    $sql = "UPDATE `wms_goodsbatch` SET 
`gb_count`=`gb_count`/$div, 
`sku_count`=`sku_count`/$div, 
`gb_bprice`=`gb_bprice`*$div
  WHERE `spu_code`='" . $spuCode . "'";
    return M("Goodsbatch")->execute($sql);
//    return $sql;
}

function updateGoodstoredBySpuCodeAndMul($spuCode, $mul)
{
    $sql = "UPDATE `wms_goodstored` SET 
`gs_init`=`gs_init`*$mul,
`gs_count`=`gs_count`*$mul,
`sku_count`=`sku_count`*$mul,
`gb_bprice`=`gb_bprice`/$mul
 WHERE `spu_code`='" . $spuCode . "'";
    return M("Goodstored")->execute($sql);
//    return $sql;
}

function updateGoodstoredBySpuCodeAndDiv($spuCode, $div)
{
    $sql = "UPDATE `wms_goodstored` SET 
`gs_init`=`gs_init`/$div, 
`gs_count`=`gs_count`/$div, 
`sku_count`=`sku_count`/$div, 
`gb_bprice`=`gb_bprice`*$div
  WHERE `spu_code`='" . $spuCode . "'";
    return M("Goodstored")->execute($sql);
//    return $sql;
}

function updateIgoodsBySpuCodeAndMul($spuCode, $mul)
{
    $sql = "UPDATE `wms_igoods` SET 
`igo_count`=`igo_count`*$mul,
`sku_count`=`sku_count`*$mul
 WHERE `spu_code`='" . $spuCode . "'";
    return M("Igoods")->execute($sql);
//    return $sql;
}

function updateIgoodsBySpuCodeAndDiv($spuCode, $div)
{
    $sql = "UPDATE `wms_igoods` SET 
`igo_count`=`igo_count`/$div, 
`sku_count`=`sku_count`/$div
  WHERE `spu_code`='" . $spuCode . "'";
//    return M("Igoods")->execute($sql);
    return $sql;
}

function updateIgoodsentBySpuCodeAndMul($spuCode, $mul)
{
    $sql = "UPDATE `wms_igoodsent` SET 
`igs_count`=`igs_count`*$mul,
`sku_count`=`sku_count`*$mul,
`igs_bprice`=`igs_bprice`/$mul
 WHERE `spu_code`='" . $spuCode . "'";
    return M("Igoodsent")->execute($sql);
//    return $sql;
}

function updateIgoodsentBySpuCodeAndDiv($spuCode, $div)
{
    $sql = "UPDATE `wms_igoodsent` SET 
`igs_count`=`igs_count`/$div, 
`sku_count`=`sku_count`/$div, 
`igs_bprice`=`igs_bprice`*$div
  WHERE `spu_code`='" . $spuCode . "'";
    return M("Igoodsent")->execute($sql);
//    return $sql;
}