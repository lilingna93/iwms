<?php
/**
 * 导出库存数据
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

$skucodeArr = array(
    "WA100017" => array(
        "PD31112082824296",
        "SP000095",
    ),
);
foreach ($skucodeArr as $warCode => $skucodes) {
    $excelReader = PHPExcel_IOFactory::createReader('Excel2007');
    $excelFile = new PHPExcel();
    $excelFile->setActiveSheetIndex(0);
    $excelSheet = $excelFile->getActiveSheet();
    echo count($skucodes) . PHP_EOL;

//modifyGoodsCount($skucodes);exit;

//$errorcount = 0;
    $line = 0;
    $skucount = 0;
    foreach ($skucodes as $skucode) {
//        $list = M("goods")->where(array("spu_code" => $skucode, "war_code" => $warCode))->fetchSql(false)->select();
//        if (empty($list)) continue;
//        $skucount++;
        exportGoodbatchData($warCode, $skucode, $excelSheet);
        exportGoodstoredData($warCode, $skucode, $excelSheet);
        exportGoodsData($warCode, $skucode, $excelSheet);
        exportIgoodsData($warCode, $skucode, $excelSheet);
        exportIgoodsentData($warCode, $skucode, $excelSheet);
//     exportOrderGoodsData($warCode,$skucode,$excelSheet);
        $line++;
        $excelSheet->setCellValue("A{$line}", "------------------------------------------------------------------------------------------------------------");
        $excelSheet->setCellValue("D{$line}", "------------------------------------------------------------------------------------------------------------");
    }
//    echo "SUM:{$skucount}" . PHP_EOL;
    $excelWriter = PHPExcel_IOFactory::createWriter($excelFile, 'Excel2007');
    $excelWriter->save("goodsskudata{$warCode}.online.xlsx");
}
exit;


function exportGoodbatchData($warCode, $skucode, $excelSheet)
{
    global $line;
    $line++;
    $excelSheet->setCellValue("A{$line}", "GoodsBatch");
    $line++;
    $list = M("goodsbatch")->where(array("spu_code" => $skucode, "war_code" => $warCode))->fetchSql(false)->select();
    foreach ($list as $data) {
        $excelSheet->setCellValue("A{$line}", $data["gb_code"]);
        $excelSheet->setCellValue("B{$line}", $data["sku_count"]);
        $excelSheet->setCellValue("C{$line}", $data["gb_count"]);
        $excelSheet->setCellValue("D{$line}", $data["sup_code"]);
        $excelSheet->setCellValue("E{$line}", $data["war_code"]);
        $excelSheet->setCellValue("F{$line}", $data["gb_ctime"]);
        $excelSheet->setCellValue("G{$line}", $data["rec_code"]);
        $excelSheet->setCellValue("H{$line}", M("receipt")->where(array("rec_code" => $data["rec_code"]))->getField("rec_mark"));
        $line++;
    }
}

function exportGoodstoredData($warCode, $skucode, $excelSheet)
{
    global $line;
    $line++;
    $excelSheet->setCellValue("A{$line}", "GoodsStored");
    $line++;
    $list = M("goodstored")->where(array("spu_code" => $skucode, "war_code" => $warCode))->fetchSql(false)->select();
    foreach ($list as $data) {
        $excelSheet->setCellValue("A{$line}", $data["gs_code"]);
        $excelSheet->setCellValue("B{$line}", $data["sku_init"]);
        $excelSheet->setCellValue("C{$line}", $data["gs_init"]);
        $excelSheet->setCellValue("D{$line}", $data["sku_count"]);
        $excelSheet->setCellValue("E{$line}", $data["gs_count"]);
        $excelSheet->setCellValue("F{$line}", $data["gb_code"]);
        $excelSheet->setCellValue("G{$line}", $data["war_code"]);
        $line++;
    }


}

function exportGoodsData($warCode, $skucode, $excelSheet)
{

    global $line;
    $line++;
    $excelSheet->setCellValue("A{$line}", "Goods");
    $line++;
    $list = M("goods")->where(array("spu_code" => $skucode, "war_code" => $warCode))->fetchSql(false)->select();
    if (empty($list)) {
        $excelSheet->setCellValue("G{$line}", $skucode);
    }
    foreach ($list as $data) {
        $excelSheet->setCellValue("A{$line}", $data["goods_code"]);
        $excelSheet->setCellValue("B{$line}", $data["sku_init"]);
        $excelSheet->setCellValue("C{$line}", $data["goods_init"]);
        $excelSheet->setCellValue("D{$line}", $data["sku_count"]);
        $excelSheet->setCellValue("E{$line}", $data["goods_count"]);
        $excelSheet->setCellValue("F{$line}", $data["war_code"]);
        $excelSheet->setCellValue("G{$line}", $skucode);

        $skudata = M("sku")->where(array("spu_code" => $skucode))->fetchSql(false)->select();
        $spucode = $skudata[0]["spu_code"];
        $spudata = M("spu")->where(array("spu_code" => $spucode))->fetchSql(false)->select();
        $excelSheet->setCellValue("F{$line}", $spudata[0]["spu_name"]);

        $goodscode = $data["goods_code"];
        $goodscount = $data["goods_init"];

        $igoods = M("igoods")->where(array("goods_code" => $goodscode))->select();
        $igoodscount = 0;
        foreach ($igoods as $item) {
            $igoodscount += $item["igo_count"];
        }
        if ($igoodscount > 0) {
            //echo "{$goodscount}?={$igoodscount} ".$data["goods_init"]."=". ($goodscount>=$igoodscount?"":"数量错误").PHP_EOL;
            $excelSheet->setCellValue("L{$line}", ($goodscount >= $igoodscount ? "" : "数量错误"));
            $excelSheet->setCellValue("M{$line}", ($goodscount >= $igoodscount ? "" : "{$skucode}"));
        }
        $line++;
    }
    //exit;
}


function exportIgoodsData($warCode, $skucode, $excelSheet)
{
    global $errorcount;
    global $line;
    $line++;
    $excelSheet->setCellValue("A{$line}", "Igoods");
    $line++;
    $list = M("igoods")->where(array("spu_code" => $skucode, "war_code" => $warCode))->select();//,"igo_code"=>array("GT","GO31229000000000")

    foreach ($list as $data) {
        $excelSheet->setCellValue("A{$line}", $data["igo_code"]);
        $excelSheet->setCellValue("B{$line}", $data["sku_count"]);
        $excelSheet->setCellValue("C{$line}", $data["igo_count"]);
        $excelSheet->setCellValue("D{$line}", $data["goods_code"]);
        $excelSheet->setCellValue("E{$line}", $data["war_code"]);
        $excelSheet->setCellValue("F{$line}", M("invoice")->where(array("inv_code" => $data['inv_code']))->getField("inv_ecode"));

        $igocode = $data["igo_code"];
        $igoodscount = $data["igo_count"];
        $igoods = M("igoodsent")->where(array("igo_code" => $igocode))->select();
        $igoodsentcount = 0;
        foreach ($igoods as $item) {
            $igoodsentcount += $item["igs_count"];
        }
        $excelSheet->setCellValue("J{$line}", ($igoodscount == $igoodsentcount ? "" : "错误"));
        $excelSheet->setCellValue("K{$line}", ($igoodscount == $igoodsentcount ? "" : "{$skucode}"));
        $excelSheet->setCellValue("L{$line}", ($igoodscount == $igoodsentcount ? "" : "{$igocode}"));

        // $invcode = $data["inv_code"];
        // $invdata = M("invoice")->where(array("invoice.inv_code"=>$invcode))->find();
        // $ocode = $invdata["inv_ecode"];
        // $excelSheet->setCellValue("G{$line}", $ocode);
        // global $goodsSkuDict;
        // $goodsSkuDict["{$skucode}.{$ocode}"] = 1;
        $line++;
    }

}

function exportIgoodsentData($warCode, $skucode, $excelSheet)
{
    global $line;
    $line++;
    $excelSheet->setCellValue("A{$line}", "Igoodsent");
    $line++;
    $list = M("igoodsent")->where(array("spu_code" => $skucode, "war_code" => $warCode))->select();//,"igo_code"=>array("GT","GO31229000000000")
    $count = 0;
    foreach ($list as $data) {
        $excelSheet->setCellValue("A{$line}", $data["igs_code"]);
        $excelSheet->setCellValue("B{$line}", $data["sku_count"]);
        $excelSheet->setCellValue("C{$line}", $data["igs_count"]);
        $excelSheet->setCellValue("D{$line}", $data["gs_code"]);
        $excelSheet->setCellValue("E{$line}", $data["igo_code"]);
        $excelSheet->setCellValue("F{$line}", $data["war_code"]);
        $count += $data["igo_count"];
        $line++;
    }
    if (empty($list)) {
        $excelSheet->setCellValue("A{$line}", "错误数据");
    }
}

function exportOrderGoodsData($warCode, $skucode, $excelSheet)
{
    global $line;
    $line++;
    $excelSheet->setCellValue("A{$line}", "OrderGoods");
    $line++;
    $list = M("ordergoods")->where(array("spu_code" => $skucode, "war_code" => $warCode))->select();
    foreach ($list as $data) {
        $ocode = $data["order_code"];
        $order = M("order")->where(array("order_code" => $ocode))->find();
        if (!empty($order)) {
            if ($order["order_status"] == 3 || ($order["order_status"] == 1 && $order["w_order_status"] == 1)) {
                continue;
            }
        } else {
            continue;
        }

        $excelSheet->setCellValue("A{$line}", $data["goods_code"]);
        $excelSheet->setCellValue("B{$line}", $data["sku_init"]);
        $excelSheet->setCellValue("C{$line}", $data["goods_count"]);
        $excelSheet->setCellValue("D{$line}", $data["sku_count"]);
        $excelSheet->setCellValue("E{$line}", $data["w_sku_count"]);
        $excelSheet->setCellValue("F{$line}", $data["supplier_code"]);
        $excelSheet->setCellValue("G{$line}", $data["order_code"]);
        $excelSheet->setCellValue("H{$line}", $data["ot_code"]);
        $excelSheet->setCellValue("I{$line}", $data["war_code"] . "-" . $order["order_status"]);
        $excelSheet->setCellValue("N{$line}", empty($data["ot_code"]) ? "异常分单" : "");
        $excelSheet->setCellValue("P{$line}", ($data["sku_count"] != $data["w_sku_count"]) ? "异常入库" : "");
        $excelSheet->setCellValue("Q{$line}", (bcdiv($data["goods_count"], $data["spu_count"], 2) != $data["w_sku_count"]) ? "异常数据" : "");

        global $goodsSkuDict;
        if (!isset($goodsSkuDict["{$skucode}.{$ocode}"])) {
            $excelSheet->setCellValue("O{$line}", "ecode错误");
        }


        $line++;
    }
}