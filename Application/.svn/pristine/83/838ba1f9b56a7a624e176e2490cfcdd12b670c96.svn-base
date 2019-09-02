<?php
ini_set('memory_limit', '356M');
define('APP_DIR', dirname(__FILE__) . '/../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';

use Wms\Dao\SpuDao;
use Wms\Dao\SkudictDao;
use Common\Service\ExcelService;

//帮助项目组导入期初库存数据脚本
$time = venus_script_begin("初始化期初库存数据");
$warCode = "WA100011";
$worCode = "WO31019130542533";
$files = "C:/Users/gfz_1/Desktop/huitang.xlsx";
$datas = ExcelService::GetInstance()->uploadByShell($files);
//array_pop($datas);//过滤最后一个类型说明表
$dicts = array(
    "A" => "spCode",//供货商编号
    "B" => "spName",//货品名称
    "C" => "spNorm",//货品规格
    "D" => "count",//采购数量
    "E" => "spBprice",//采购价格
    "F" => "supCode",//供货商编号

);

$skuList = array();
foreach ($datas as $sheetName => $list) {
    unset($list[0]);
    $skuList = array_merge($skuList, $list);
}
$dataArr = array();
$RECEIPT_STATUS_CREATE = "1";//入仓单创建状态
$RECEIPT_STATUS_INSPECTION = "2";//inspection入仓单验货状态
$RECEIPT_STATUS_FINISH = "3";//入仓单完成状态
$RECEIPT_STATUS_CANCEL = "4";//入仓单取消状态

$GOODSBATCH_STATUS_CREATE = "1";//货品批次创建状态
$GOODSBATCH_STATUS_INSPECTION = "2";//货品批次验货状态
$GOODSBATCH_STATUS_PUTAWAY = "3";//Putaway货品批次上架状态
$GOODSBATCH_STATUS_FINISH = "4";//货品批次使用完状态
$recModel = \Wms\Dao\ReceiptDao::getInstance($warCode);
$goodsModel = \Wms\Dao\GoodsDao::getInstance($warCode);
$goodsbatchModel = \Wms\Dao\GoodsbatchDao::getInstance($warCode);
$goodstoredModel = \Wms\Dao\GoodstoredDao::getInstance($warCode);
venus_db_starttrans();//启动事务
$result = true;

//创建入仓单
$recStatus = $RECEIPT_STATUS_FINISH;
$addRecData = array(
    "worcode" => $worCode,
    "mark" => "录入期初库存",
    "status" => $recStatus
);
$recCode = $recModel->insert($addRecData);
$emptySpu = array();
foreach ($skuList as $index => $skuItem) {
    $skuData = array();
    foreach ($dicts as $col => $key) {
        $skuData[$key] = isset($skuItem[$col]) ? $skuItem[$col] : "";
    }

    if (trim($skuData['spName']) == '' || trim($skuData['spBprice']) == '' || trim($skuData['supCode']) == '') {
        if (trim($skuData['spName']) == '' && trim($skuData['spBprice']) == '' && trim($skuData['supCode']) == '') {
            continue;
        } else {
            if (empty($skuData['spName'])) {
                venus_db_rollback();//回滚事务
                $message = "货品名称不能为空";
                echo $message;
                exit();
            }

            if (empty($skuData['spNorm'])) {
                venus_db_rollback();//回滚事务
                $message = "货品规格不能为空";
                echo $message;
                exit();
            }

            if (empty($skuData['count'])) {
                venus_db_rollback();//回滚事务
                $message = "采购数量不能为空";
                echo $message;
                exit();
            }

            if (empty($skuData['spBprice'])) {
                venus_db_rollback();//回滚事务
                $message = "货品采购价不能为空";
                echo $message;
                exit();
            }

            if (empty($skuData['supCode'])) {
                venus_db_rollback();//回滚事务
                $message = "spu货品供货商不能为空";
                echo $message;
                exit();
            }
        }
    } else {
//        $spName = trim(str_replace("'", "", $skuData['spName']));
//        $spuInfo=explode("(",$spName);
//        $spuName=$spuInfo[0];
//        if(!empty($spuInfo[1])){
//            $spuBrand=explode(")",$spuInfo[0])[0];
//            $spuData = \Wms\Dao\SkuDao::getInstance()->queryBySpuNameAndSkuNormAndSpuBrand($spuName, $skuData['spNorm'],$spuBrand);
//        }else{
//            $spuData = \Wms\Dao\SkuDao::getInstance()->queryBySpuNameAndSkuNormAndSpuBrand($spuName, $skuData['spNorm']);
//        }
        $spuData = \Wms\Dao\SpuDao::getInstance()->queryBySpuCode($skuData['spCode']);
        $skuCode = \Wms\Dao\SkuDao::getInstance()->querySkuCodeBySpuCodeToIwms($skuData['spCode']);

//        if (!empty($spuData)) {
            $addData['spucode'] = $spuData['spu_code'];
            $addData['count'] = $skuData['count'];
            $addData['bprice'] = $skuData['spBprice'];
            $addData['supcode'] = $skuData['supCode'];
            $addData['status'] = $GOODSBATCH_STATUS_PUTAWAY;
            $addData['reccode'] = $recCode;
//            $addData['skucode'] = trim($spuData['sku_code']);
            $addData['skucode'] = $skuCode;
            $addData['skucount'] = $skuData['count'];
            $gbcode = $goodsbatchModel->insert($addData);
            if (!$gbcode) {
                venus_db_rollback();
                $message = '添加入仓货品清单';
                echo $message;
                exit();

            }
            $issetGoods = $goodsModel->queryBySpuCode($spuData['spu_code']);
            if ($issetGoods) {
                $goodsCode = $issetGoods['goods_code'];
                $init = $issetGoods['goods_init'] + $skuData['count'];
                $count = $issetGoods['goods_count'] + $skuData['count'];
                $goodsRes = $goodsModel->updateCountAndInitByCode($goodsCode, $init, $count);
            } else {
                $goodsAddData = array(
                    'init' => $skuData['count'],
                    'count' => $skuData['count'],
                    'spucode' => $spuData['spu_code']
                );
                $goodsRes = $goodsModel->insert($goodsAddData);
            }
            $goodstoredAddData = array(
                'init' => $skuData['count'],
                'count' => $skuData['count'],
                'bprice' => $skuData['spBprice'],
                'gbcode' => $gbcode,
                'spucode' => $spuData['spu_code'],
                'supcode' => $skuData['supCode']
            );
            $goodstoredAddData['skucode'] = trim($skuCode);
            $goodstoredAddData['skucount'] = $skuData['count'];
            $goodstoredAddData['skuinit'] = $skuData['count'];
            $addGoodstoredRes = $goodstoredModel->insert($goodstoredAddData);
            if (!$goodsRes || !$addGoodstoredRes) {
                venus_db_rollback();
                $message = '存入库存';
                echo json_encode($message);
                exit();
            }
//        }
//        else {
//            if (!in_array($spuName, $emptySpu[$spuName])) {
//                $emptySpu[] = $spuName;
//                continue;
//            }
//
//        }
    }

    if (!$result) {
        venus_db_rollback();
        echo "失败1";
        exit();
    }
}
//if(!empty($emptySpu)){
//    venus_db_rollback();
//    $message="以下货品不存在：";
//    foreach ($emptySpu as $spuName=>$spuNormArr){
//        $message.=$spuName.":".join($spuNormArr).PHP_EOL;
//    }
//    echo json_encode($message);
//    exit();
//}
$uptRecFinish = $recModel->updateFinishTimeByCode($recCode);

if (!$uptRecFinish) {
    venus_db_rollback();
    $message = '完成入仓单失败';
    echo json_encode($message);
    exit();
}

if ($result) {
    venus_db_commit();
    return true;
} else {
    venus_db_rollback();
    echo "失败2";
    exit();
}







