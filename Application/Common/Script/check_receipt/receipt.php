<?php
/**
 * 导出小程序订单入仓单数据
 */

define('IS_MASTER', false);
ini_set('memory_limit', '256M');
define('APP_DIR', dirname(__FILE__) . '/../../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';
vendor("PHPExcel");
echo venus_current_datetime() . PHP_EOL;
//$receiptcodeData = M("receipt")->where(array("rec_type" => 2, "rec_ecode" => ''))->select();
//$receiptcodeArr = array(
//    "WA100002" => array(
//        "RE40520142646129",
//    ),

//    "WA100005"=>array(
//        "RE40326134844957",
//        "RE40326134802953"
//    ),
//    "WA100010"=>array(
//        "RE40327080338972",
//    ),
//    "WA100003"=>array(
//        "RE40327085633227",
//        "RE40327093150968"
//    ),
//);
$orderArr = array(
    "WA100006" => array("O40517161416939")
);
foreach ($orderArr as $warCode => $ocodes) {
    $excelReader = PHPExcel_IOFactory::createReader('Excel2007');
    $excelFile = new PHPExcel();
    $excelFile->setActiveSheetIndex(0);
    $excelSheet = $excelFile->getActiveSheet();
    $line = 0;

    exportOrdergoodsArr($warCode, $excelSheet,$ocodes);

    $line++;
    $excelSheet->setCellValue("A{$line}", "------------------------------------------------------------------------------------------------------------");
    $excelSheet->setCellValue("D{$line}", "------------------------------------------------------------------------------------------------------------");
    echo "SUM:{$line}" . PHP_EOL;
    $excelWriter = PHPExcel_IOFactory::createWriter($excelFile, 'Excel2007');
    $excelWriter->save("receiptdata{$warCode}.online.xlsx");
    echo "receiptdata{$warCode}.online.xlsx";
}

exit();
foreach ($receiptcodeArr as $warCode => $codes) {
    $excelReader = PHPExcel_IOFactory::createReader('Excel2007');
    $excelFile = new PHPExcel();
    $excelFile->setActiveSheetIndex(0);
    $excelSheet = $excelFile->getActiveSheet();
//    echo count($codes) . PHP_EOL;

//modifyGoodsCount($codes);exit;

//$errorcount = 0;
    $line = 0;

    foreach ($codes as $code) {
        $gbcodeArr = array();
        exportGoodbatchData($warCode, $code, $excelSheet);
        exportGoodstoredData($warCode, $excelSheet);
        $line++;
        $excelSheet->setCellValue("A{$line}", "------------------------------------------------------------------------------------------------------------");
        $excelSheet->setCellValue("D{$line}", "------------------------------------------------------------------------------------------------------------");
    }
    $line++;
//    exportOrdergoodsArr($warCode, $excelSheet);
//    $line++;
    $excelSheet->setCellValue("A{$line}", "------------------------------------------------------------------------------------------------------------");
    $excelSheet->setCellValue("D{$line}", "------------------------------------------------------------------------------------------------------------");
    echo "SUM:{$line}" . PHP_EOL;
    $excelWriter = PHPExcel_IOFactory::createWriter($excelFile, 'Excel2007');
    $excelWriter->save("./receiptdata{$warCode}.online.xlsx");
}
exit;


function exportGoodbatchData($warCode, $code, $excelSheet)
{
    global $line;
    global $gbcodeArr;
    $line++;
    $excelSheet->setCellValue("A{$line}", "GoodsBatch");
    $line++;
    $list = M("goodsbatch")->where(array("rec_code" => $code, "war_code" => $warCode))->order("gb_code desc")->fetchSql(false)->select();
    foreach ($list as $data) {
        $excelSheet->setCellValue("A{$line}", $data["gb_code"]);
        $excelSheet->setCellValue("B{$line}", $data["spu_code"]);
        $excelSheet->setCellValue("C{$line}", $data["sku_count"]);
        $excelSheet->setCellValue("D{$line}", $data["gb_count"]);
        $excelSheet->setCellValue("E{$line}", $data["sup_code"]);
        $excelSheet->setCellValue("F{$line}", $data["war_code"]);
        $excelSheet->setCellValue("G{$line}", $data["gb_ctime"]);
        $excelSheet->setCellValue("H{$line}", $data["rec_code"]);
        $excelSheet->setCellValue("I{$line}", M("receipt")->where(array("rec_code" => $data["rec_code"]))->getField("rec_mark"));
        $line++;
        $gbcodeArr[] = $data["gb_code"];
    }
}

function exportGoodstoredData($warCode, $excelSheet)
{
    global $line;
    global $gbcodeArr;
    $line++;
    $excelSheet->setCellValue("A{$line}", "GoodsStored");
    $line++;
    $list = M("goodstored")->where(array("gb_code" => array("in", $gbcodeArr), "war_code" => $warCode))->fetchSql(false)->select();
    foreach ($list as $data) {
        $excelSheet->setCellValue("A{$line}", $data["gs_code"]);
        $excelSheet->setCellValue("B{$line}", $data["spu_code"]);
        $excelSheet->setCellValue("C{$line}", $data["sku_init"]);
        $excelSheet->setCellValue("D{$line}", $data["gs_init"]);
        $excelSheet->setCellValue("E{$line}", $data["sku_count"]);
        $excelSheet->setCellValue("F{$line}", $data["gs_count"]);
        $excelSheet->setCellValue("G{$line}", $data["gb_code"]);
        $excelSheet->setCellValue("H{$line}", $data["war_code"]);
        $line++;
    }


}

function exportOrdergoodsArr($warCode, $excelSheet,$orderArr)
{
    global $line;
    $line++;
    $excelSheet->setCellValue("A{$line}", "OrderGoods");
    $line++;

    if (!empty($orderArr)) {
        $list = M("zwdb_wms.ordergoods")
            ->where(array("order_code" => array("in", $orderArr), "war_code" => $warCode))
            ->select();

        foreach ($list as $data) {
            $ocode = $data["order_code"];
            $order = M("zwdb_wms.order")->where(array("order_code" => $ocode))->find();

            $excelSheet->setCellValue("A{$line}", $data["goods_code"]);
            $excelSheet->setCellValue("B{$line}", $data["spu_code"]);
            $excelSheet->setCellValue("C{$line}", $data["sku_init"]);
            $excelSheet->setCellValue("D{$line}", $data["goods_count"]);
            $excelSheet->setCellValue("E{$line}", $data["sku_count"]);
            $excelSheet->setCellValue("F{$line}", $data["w_sku_count"]);
            $excelSheet->setCellValue("G{$line}", $data["supplier_code"]);
            $excelSheet->setCellValue("H{$line}", $data["order_code"]);
            $excelSheet->setCellValue("I{$line}", $data["ot_code"]);
            $excelSheet->setCellValue("J{$line}", $data["war_code"] . "-" . $order["order_status"]);
            $line++;
        }
    }

}

function getOrderData($warCode)
{
    $orderCodeArr = M("zwdb_wms.order")->where(array("order_status" => 2, "order_code" => array("GT", "O40300115219491"), "war_code" => $warCode))->field("order_code")->limit(10000)->select();
    $orderCodeArr = array_column($orderCodeArr, "order_code");
    $codeArr = array();
    echo count($orderCodeArr) . PHP_EOL;
    $recDatacodeArr = array();
    foreach ($orderCodeArr as $orderCode) {
//        echo json_encode($orderCode).PHP_EOL;
        $recData = M("receipt")->where(array("rec_ecode" => $orderCode))->find();
        $recDatacodeArr[$orderCode] = $recData;
        if (empty($recData) || $recData == null) {
            $codeArr[] = $orderCode;
        }
    }
    echo count($recDatacodeArr) . PHP_EOL;
    return $codeArr;
}

//function getRecData()
//{
//    $recData = M("receipt")->where(array("or", array(array("rec_ecode" => ''))))->find();
//}

function getOrderDataNotWar()
{
    $orderCodeArr = M("zwdb_wms.order")->where(array("order_status" => 2, "order_code" => array("GT", "O40301000000000")))->field("order_code")->limit(100000)->select();
    $orderCodeArr = array_column($orderCodeArr, "order_code");
    $codeArr = array();
    echo count($orderCodeArr) . PHP_EOL;
    $recDatacodeArr = array();
    foreach ($orderCodeArr as $orderCode) {
//        echo json_encode($orderCode).PHP_EOL;
        $recData = M("receipt")->where(array("rec_ecode" => $orderCode))->find();

        if (empty($recData) || $recData == null) {
            $codeArr[] = $orderCode;
        } else {
            $recDatacodeArr[$orderCode] = $recData;
        }
    }
    echo count($recDatacodeArr) . PHP_EOL;
    return array("codearr" => $codeArr, "rec" => $recDatacodeArr);
}
