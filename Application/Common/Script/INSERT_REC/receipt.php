<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2019/5/20
 * Time: 16:05
 */
define('IS_MASTER', false);
ini_set('memory_limit', '256M');
define('APP_DIR', dirname(__FILE__) . '/../../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';

$recCodeArr=M("receipt")->where(array("rec_ctime"=>array(
    array('EGT','2019-05-20 14:00:00'),
    array('ELT','2019-05-21 14:30:00'),
    'AND'
)))->select();
$oCodeArr=array_column($recCodeArr,"rec_ecode");
//$oCodeArr = array(
//    "O40517182214939", "O40517161605807",
//    "O40519114115963",
//);
$addGbList = array();
foreach ($oCodeArr as $oCode) {
    $ogList = getOrderGoodsList($oCode);
    $recCode = getIwmsRecCode($oCode);
    $recGbList = getIwmsRecGbList($recCode);

    $ordergoodsList = array();
    foreach ($ogList as $ogData) {
        $warCode = $ogData['war_code'];
        $skuCode = $ogData["sku_code"];
        $skuCount = $ogData["goods_count"];
        $count = $ogData["goods_count"];
        $bPrice = bcadd($ogData["spu_sprice"], $ogData["pro_percent"], 2);
        $spuCode = $ogData["spu_code"];
        $supCode = $ogData["supplier_code"];
        if ($supCode == "SU00000000000003") {
            $supCode = "SU40409165031663";
        }
        if ($supCode == "SU00000000000002") {
            $supCode = "SU00000000000001";
        }

        $ordergoodsList[$recCode][$skuCode] = array(
            "warCode" => $warCode,
            "skuCode" => $skuCode,
            "skuCount" => $skuCount,
            "count" => $count,
            "bPrice" => $bPrice,
            "spuCode" => $spuCode,
            "supCode" => $supCode,
        );
        $addGbList[$recCode][$skuCode] = array(
            "warCode" => $warCode,
            "skuCode" => $skuCode,
            "skuCount" => $skuCount,
            "count" => $count,
            "bPrice" => $bPrice,
            "spuCode" => $spuCode,
            "supCode" => $supCode,
        );
    }
    $goodsbatchList = array();
    foreach ($recGbList as $recGbData) {
        $warCode = $recGbData['war_code'];
        $skuCode = $recGbData["sku_code"];
        $count = $recGbData["gb_count"];
        $bPrice = $recGbData["gb_bprice"];
        $spuCode = $recGbData["spu_code"];
        $supCode = $recGbData["sup_code"];
        $goodsbatchData = array(
            "warCode" => $warCode,
            "skuCode" => $skuCode,
            "skuCount" => $count,
            "count" => $count,
            "bPrice" => $bPrice,
            "spuCode" => $spuCode,
            "supCode" => $supCode,
        );

        $goodsbatchList[$recCode][$skuCode] = $goodsbatchData;
        if ($goodsbatchList[$recCode][$skuCode]['count'] == $addGbList[$recCode][$skuCode]['count']) {
            unset($addGbList[$recCode][$skuCode]);
            if(empty($addGbList[$recCode])){
                unset($addGbList[$recCode]);
            }
        }
    }


}
//echo "empty";
//exit();
echo json_encode($addGbList);
exit();
foreach ($addGbList as $recCode => $ordergoodsDataList) {
    foreach ($ordergoodsDataList as $ordergoodsData) {
        $warCode = $ordergoodsData['warCode'];
        $skuCode = $ordergoodsData["skuCode"];
        $skuCount = $ordergoodsData["skuCount"];
        $count = $ordergoodsData["count"];
        $bPrice = $ordergoodsData["bPrice"];
        $spuCode = $ordergoodsData["spuCode"];
        $supCode = $ordergoodsData["supCode"];
        $gbCode = getCode("GB");
        $addGbData = array(
            "status" => 3,//已上架
            "code" => $gbCode,
            "count" => $count,
            "bprice" => $bPrice,
            "spucode" => $spuCode,
            "supcode" => $supCode,
            "skucode" => $skuCode,
            "skucount" => $skuCount,
            "reccode" => $recCode,
        );
        $sql[] = insertGb($addGbData, $warCode);
        $gsCode = getCode("GW");
        $addGsData = array(
            "init" => $count,
            "code" => $gsCode,
            "count" => $count,
            "bprice" => $bPrice,
            "gbcode" => $gbCode,
            "spucode" => $spuCode,
            "skucode" => $skuCode,
            "skucount" => $skuCount,
            "supcode" => $supCode,
        );
        $sql[] = insertGs($addGsData, $warCode);
        $issetGoods = queryGoods($spuCode, $warCode);
        if (!empty($issetGoods)) {
            $goodsCode = $issetGoods['goods_code'];
            if ($count != 0) {
                $sql[] = updateGoods($goodsCode, $count);
            }

        } else {
            $goodsCode = getCode("G");
            $addGoodsData = array(
                "init" => $count,
                "code" => $goodsCode,
                "count" => $count,
                "spucode" => $spuCode,
            );
            $sql[] = insertGoods($addGoodsData, $warCode);
        }
    }
}


file_put_contents("201905211331iwms.sql", implode(";\n", $sql));
exit();

function insertGb($item, $warCode)
{
    $data = array(
        "gb_code" => $item["code"],
        "gb_ctime" => venus_current_datetime(),
        "gb_status" => $item["status"],
        "gb_count" => $item["count"],  //spu的数量，该货品的实际数量，比如多少瓶
        "gb_bprice" => $item["bprice"], //spu的采购价格
        "spu_code" => $item["spucode"],//spu编码
        "sku_code" => $item["skucode"],//sku编码，该商品采购时的规格信息
        "sku_count" => $item["skucount"],//sku的数量，该商品采购时的采购数量，比如多少箱
        "rec_code" => $item["reccode"],//所属入仓单编码
        "sup_code" => $item["supcode"],//货品供货商编号
        "war_code" => $warCode,
    );
    return M("Goodsbatch")->fetchSql(true)->add($data);
}


function insertGs($item, $warCode)
{

    $data = array(
        "gs_code" => $item["code"],
        "gs_init" => $item["init"],     //初次写入的货品数量，即spu的数量
        "gs_count" => $item["count"],   //当前货品数量，即spu的实际数量
        "gb_bprice" => $item["bprice"], //货品的采购价格，即spu的采购价格

        "gb_code" => $item["gbcode"],   //所属入仓货品批次表激励编号
        "pos_code" => $item["poscode"], //仓库货位编号

        "spu_code" => $item["spucode"], //spu编号

        "sku_code" => $item["skucode"], //sku编号，货品采购和上架时的规格数据信息
        "sku_init" => $item["skucount"],//sku采购数量，即按货品采购时规格的采购数量
        "sku_count" => $item["skucount"],//sku的实际数量

        "sup_code" => $item["supcode"],//货品供货商编号
        "war_code" => $warCode,//所属仓库
    );
    return M("Goodstored")->fetchSql(true)->add($data);
}


function insertGoods($item, $warCode)
{
    $data = array(
        "goods_code" => $item["code"],
        "goods_init" => $item["init"],
        "goods_count" => $item["count"],
        "spu_code" => $item["spucode"],
        "war_code" => $warCode,
    );
    return M("Goods")->fetchSql(true)->add($data);
}

function updateGoods($goodsCode, $count)
{
    $time = venus_current_datetime();
    return "UPDATE `wms_goods` SET `goods_init` = `goods_init` + $count,`goods_count` = `goods_count` + $count,`timestamp` = '{$time}' WHERE `goods_code` = '{$goodsCode}'";
}

function queryGoods($spuCode, $warCode)
{
    $condition = array("goods . war_code" => $warCode, "goods . spu_code" => $spuCode);
    return M("Goods")->alias('goods')->field('*')
        ->join("LEFT JOIN wms_spu spu ON spu . spu_code = goods . spu_code")
        ->where($condition)->fetchSql(false)->find();
}

function getCode($str)
{
    return venus_unique_code($str);
}

function getOrderGoodsList($oCode)
{
    return M("zwdb_wms.ordergoods")->where(array("order_code" => $oCode))->select();
}

function getIwmsRecCode($oCode)
{
    return M("receipt")->where(array("rec_ecode" => $oCode))->getField("rec_code");
}

function getIwmsRecGbList($recCode)
{
    return M("goodsbatch")->where(array("rec_code" => $recCode))->select();
}