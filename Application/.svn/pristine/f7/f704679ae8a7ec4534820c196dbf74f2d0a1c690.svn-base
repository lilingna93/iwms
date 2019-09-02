<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2018/8/10
 * Time: 14:14
 */
include_once "start_generate_report.php";
include_once "common_report_function.php";

if (array_key_exists($REPORT_TYPE_RECEIPT, $repDataByType)) {
    $receiptType = $REPORT_TYPE_RECEIPT;
} else {
    $receiptType = $REPORT_TYPE_PURCHUSE;
}
$reportData = $repDataByType[$receiptType];

use Wms\Dao\ReceiptDao;
use Wms\Dao\GoodsbatchDao;
use Wms\Dao\SupplierDao;
use Wms\Dao\SpuDao;
use Wms\Dao\ReportDao;
use Common\Service\ExcelService;

$reportDataList = array();
foreach ($reportData as $reportDatum) {
    $file = get_file_data($reportDatum);
    $clause = array();
    if (array_key_exists('supCode', $reportDatum)) {
        $file['supCode'] = $reportDatum['supCode'];
    }

    $clause['sftime'] = $reportDatum['stime'];
    $clause['eftime'] = $reportDatum['etime'];
    $clause['status'] = $RECEIPT_STATUS_FINISH;
//    echo json_encode($clause);
//    exit();
    $receiptModel = ReceiptDao::getInstance($reportDatum['warCode']);
    $list = $receiptModel->queryListByCondition($clause, 0, 100000);
//    echo json_encode($list);
//    exit();
    if (!empty($list)) {
        foreach ($list as $v) {
            $recCodeList[$reportDatum['warCode']][] = $v['rec_code'];
        }
        if (!empty($recCodeList)) {
            $reportDataList[] = array(
                'list' => $recCodeList,
                'file' => $file
            );
        }
        unset($recCodeList);
    } else {
        $uptReportFnameArr[] = report_upt_data_null($file, $REPORT_STATUS_DATANULL);
    }
    unset($clause);
    unset($recCodeList);
    unset($file);
}
unset($reportData);

foreach ($reportDataList as $reportData) {
    $recGoodsbatchDataListArr = array();
    $warName = $reportData['file']['warName'];

    $supEmpty = array();
    $supEmptyToSpu = array();
    foreach ($reportData['list'] as $key => $reportDatum) {

        $goodsbatchModel = GoodsbatchDao::getInstance($key);
        $spuModel = SpuDao::getInstance($key);
        $recGoodsbatchDataList = array();

        $clauseRecGoods = array(
            "reccodes" => $reportDatum,
        );
        if (array_key_exists('supCode', $reportData['file'])) {
            $supplierCode = $reportData['file']['supCode'];
            $clauseRecGoods['supcode'] = $reportData['file']['supCode'];
            $supplierName = SupplierDao::getInstance()->queryAllByCode($supplierCode)['sup_name'];
        } else {
            $supplierName = "全部供应商";
        }

        $recGoodsbatchDataList = array();
//        echo json_encode($clauseRecGoods);
//        exit();
        $recGoodsbatchDataList = $goodsbatchModel->queryListByCondition($clauseRecGoods, 0, 100000);
//        echo json_encode($recGoodsbatchDataList);
//        exit();
        foreach ($recGoodsbatchDataList as $recGoodsbatchData) {
            $money = bcmul($recGoodsbatchData['gb_count'], $recGoodsbatchData['gb_bprice'], 6);
            $spuName = $recGoodsbatchData['spu_name'];
            $spuUnit = $recGoodsbatchData['spu_unit'];
            $recGoodsbatchDataListArr[$warName][$supplierName][$spuName][$recGoodsbatchData['gb_bprice']]['count'] += $recGoodsbatchData['gb_count'];
            $recGoodsbatchDataListArr[$warName][$supplierName][$spuName][$recGoodsbatchData['gb_bprice']]['unit'] = $spuUnit;
            $recGoodsbatchDataListArr[$warName][$supplierName][$spuName][$recGoodsbatchData['gb_bprice']]['money'] += $money;
            unset($money);
        }
        unset($recGoodsbatchDataList);
    }
//    exit();
//    echo json_encode($recGoodsbatchDataListArr);
//    exit();
    $data = array();

    $letters = array(
        "A", "B", "C", "D", "E"
    );

    $line = array();
    foreach ($recGoodsbatchDataListArr as $warNameKey => $goods) {
//        echo json_encode($goods);
//        exit();
        foreach ($goods as $goodDataArr) {
            foreach ($goodDataArr as $goodKey => $goodData) {
                foreach ($goodData as $bprice => $goodDatum) {
                    $goodsToLine = array($goodKey, $goodDatum['count'], $goodDatum['unit'], $bprice, $goodDatum['money']);
                    $line[] = $goodsToLine;
                }
            }
        }
    }

    $line = array_chunk($line, 9);
    foreach ($line as $lineKey => $lineItem) {
        $money = 0;
        if ($lineKey > 0) {
            $page = $lineKey + 1;
            $sheetName = $supplierName . "第" . $page . "页";
        } else {
            $sheetName = $supplierName;
        }

        $data[$sheetName] = array(
            "C1" => explode("(", $reportData['file']['name'])[0],
            "B2" => $supplierName,
            "C2" => date("Y年m月d日", time()),
            "F2" => $warName,
        );
        $countLineItemNum = count($lineItem) + 4;
        for ($lineNum = 4; $lineNum < $countLineItemNum; $lineNum++) {
            for ($rows = 0; $rows < count($letters); $rows++) {
                $num = $letters[$rows] . $lineNum;
                $data[$sheetName][$num] = $lineItem[$lineNum - 4][$rows];
                if ($rows == count($letters) - 1) {
                    $money += $lineItem[$lineNum - 4][$rows];
                }
            }
        }
        $data[$sheetName]["E13"] = $money;
        $data[$sheetName]["B13"] = venus_money_amount_in_words($money);
        unset($lineItem);
    }
    if (empty($line)) {
        $uptReportFnameArr[] = report_upt_data_null($reportData['file'], $REPORT_STATUS_DATANULL);

    } else {
        $fileName = ExcelService::getInstance()->exportExcelByTemplate($data, "010");
        $uptReportFnameArr[] = report_upt_data($reportData['file'], $fileName, $REPORT_STATUS_FINISH);
    }
    unset($line);
    unset($fileName);
    unset($data);
    unset($warName);

}
unset($reportDataList);
unset($recGoodsbatchDataListArr);
unset($receiptType);



