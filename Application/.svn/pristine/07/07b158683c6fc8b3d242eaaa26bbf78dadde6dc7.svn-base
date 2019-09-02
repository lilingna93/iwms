<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2018/8/13
 * Time: 11:21
 */

include_once "start_generate_report.php";
include_once "common_report_function.php";

if (array_key_exists($REPORT_TYPE_INVOICE, $repDataByType)) {
    $invoiceType = $REPORT_TYPE_INVOICE;
} else {
    $invoiceType = $REPORT_TYPE_APPLY;
}
$reportData = $repDataByType[$invoiceType];

use Wms\Dao\InvoiceDao;
use Wms\Dao\IgoodsDao;
use Wms\Dao\ReportDao;
use Common\Service\ExcelService;
use Wms\Dao\IgoodsentDao;
use Wms\Dao\WorkerDao;

$reportDataList = array();
foreach ($reportData as $reportDatum) {
    $file = get_file_data($reportDatum);
    if (array_key_exists('spType', $reportDatum)) {
        $file['spType'] = $reportDatum['spType'];
    }
    if (array_key_exists('spSubtype', $reportDatum)) {
        $file['spSubtype'] = $reportDatum['spSubtype'];
    }

    $clause = array();
    $clause['sctime'] = $reportDatum['stime'];
    $clause['ectime'] = $reportDatum['etime'];
    $clause['status'] = $INVOICE_STATUS_FINISH;
    $invoiceModel = InvoiceDao::getInstance($reportDatum['warCode']);
    $list = $invoiceModel->queryListByCondition($clause, 0, 100000);
    if (!empty($list)) {
        foreach ($list as $v) {
            $warName = WorkerDao::getInstance()->queryByCode($v['wor_code'])['war_name'];
            if (!empty($v['room'])) {
                $receiver = $warName . "(" . $v['room'] . ")";
            } else {
                $receiver = $warName;
            }
            $invCodeList[$reportDatum['warCode']][$receiver][$v['inv_receiver']][] = $v['inv_code'];

        }

        if (!empty($invCodeList)) {
            $reportDataList[] = array(
                'list' => $invCodeList,
                'file' => $file
            );

        }
    } else {
        $uptReportFnameArr[] = report_upt_data_null($file, $REPORT_STATUS_DATANULL);
    }
    unset($clause);
    unset($list);
    unset($invCodeList);
    unset($file);
}
unset($reportData);

foreach ($reportDataList as $reportData) {
    $warName = $reportData['file']['warName'];
    $sumMoney = 0;
    $invIgoodsDataListArr = array();
    foreach ($reportData['list'] as $key => $items) {
        foreach ($items as $receiver => $goodsArr) {
            foreach ($goodsArr as $user => $goods) {
                $igoodsModel = IgoodsDao::getInstance($key);
                $igoodsentModel = IgoodsentDao::getInstance($key);


                $clauseInvGoods = array(
                    'invcodes' => $goods,
                );
                if (array_key_exists('spType', $reportData['file'])) {
                    $clauseInvGoods['sputype'] = $reportData['file']['spType'];
                    $spType = venus_spu_type_name($reportData['file']['spType']);
                } else {
                    $spType = "全部";
                }
                if (array_key_exists('spSubtype', $reportData['file'])) {
                    $clauseInvGoods['spusubtype'] = $reportData['file']['spSubtype'];
                    $spSubtype = venus_spu_catalog_name($reportData['file']['spSubtype']);
                } else {
                    $spSubtype = "全部";
                }
                $invGoodsentDataList = $igoodsentModel->queryListByCondition($clauseInvGoods, 0, 1000000);
                $typeName = $spType . "-" . $spSubtype;
                foreach ($invGoodsentDataList as $invGoodsentData) {

                    $spuName = $invGoodsentData['spu_name'];
                    $spuUnit = $invGoodsentData['spu_unit'];
                    $money = bcmul($invGoodsentData['igs_count'], $invGoodsentData['igs_bprice'], 6);
                    $invIgoodsDataListArr[$warName][$receiver][$user][$typeName][$spuName][$invGoodsentData['igs_bprice']]['count'] += $invGoodsentData['igs_count'];
                    $invIgoodsDataListArr[$warName][$receiver][$user][$typeName][$spuName][$invGoodsentData['igs_bprice']]['unit'] = $spuUnit;
                    $invIgoodsDataListArr[$warName][$receiver][$user][$typeName][$spuName][$invGoodsentData['igs_bprice']]['money'] += $money;
                    unset($money);
                }
                unset($invIgoodsDataList);
            }
        }
    }
    $data = array();

    $letters = array(
        "A", "B", "C", "D", "E"
    );

    foreach ($invIgoodsDataListArr as $warNameKey => $igsDataArr) {
        foreach ($igsDataArr as $receiver => $igsData) {
            foreach ($igsData as $user => $typeGoods) {
                $line = array();
                foreach ($typeGoods as $typeName => $goodDataArr) {
                    foreach ($goodDataArr as $goodKey => $goodData) {
                        foreach ($goodData as $bprice => $goodDatum) {
                            $goodsToLine = array($goodKey, $goodDatum['count'], $goodDatum['unit'], $bprice, $goodDatum['money']);
                            $line[] = $goodsToLine;
                        }
                    }
                }

                $countLine = count($line);
                $line = array_chunk($line, 9);
                foreach ($line as $lineKey => $lineItem) {
                    $money = 0;
                    if ($lineKey > 0) {
                        $page = $lineKey + 1;
                        $sheetName = $receiver . "第" . $page . "页";
                    } else {
                        $sheetName = $receiver;
                    }

                    $data[$sheetName] = array(
                        "C1" => $reportData['file']['name'],
                        "B2" => $typeName,
                        "C2" => date("Y年m月d日", time()),
                        "F2" => $receiver,
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
                    $data[$sheetName]["F14"] = $user;
//                    echo json_encode($sheetName);exit();
                }
            }
        }
    }

    if (empty($line)) {
        $uptReportFnameArr[] = report_upt_data_null($reportData['file'], $REPORT_STATUS_DATANULL);

    } else {
        $fileName = ExcelService::getInstance()->exportExcelByTemplate($data, "020");
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
