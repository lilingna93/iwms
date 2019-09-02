<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2018/8/8
 * Time: 16:46
 */

namespace Wms\Service;


use Common\Service\ExcelService;
use Common\Service\PassportService;
use Wms\Dao\GoodsbatchDao;
use Wms\Dao\GoodsDao;
use Wms\Dao\GoodstoredDao;
use Wms\Dao\IgoodsDao;
use Wms\Dao\IgoodsentDao;
use Wms\Dao\InvoiceDao;
use Wms\Dao\ReceiptDao;
use Wms\Dao\ReportDao;
use Wms\Dao\SpuDao;
use Wms\Dao\SupplierDao;
use Wms\Dao\WarehouseDao;
use Wms\Dao\WorkerDao;

class ReportService
{
    static private $REPORT_TYPE_RECEIPT = "2";//入仓单
    static private $REPORT_TYPE_INVOICE = "4";//出仓单
    static private $REPORT_TYPE_RECEIPT_COLLECT = "6";//入库汇总
    static private $REPORT_TYPE_INVOICE_COLLECT = "8";//出库汇总
    static private $REPORT_TYPE_GOODSTROED_COLLECT = "10";//库存汇总
    static private $REPORT_TYPE_GOODSTROED_ACCOUNT = "12";//台账登记表
    static private $REPORT_TYPE_APPLY = "14";//申领单
    static private $REPORT_TYPE_PURCHUSE = "16";//采购单

    static private $REPORT_STATUS_CREATE = "1";//报表状态已创建
    static private $REPORT_STATUS_UNDERWAY = "2";//报表状态处理中
    static private $REPORT_STATUS_FINISH = "3";//报表状态已生成
    static private $REPORT_STATUS_DATANULL = "4";//报表状态无数据
    static private $REPORT_STATUS_INVUNUAUAL = "5";//报表状态异常

    public $warCode;
    public $worcode;

    public function __construct()
    {
        $workerData = PassportService::getInstance()->loginUser();
        if (empty($workerData)) {
            venus_throw_exception(110);
        }

        $this->warCode = $workerData["war_code"];
        $this->worcode = $workerData["wor_code"];
//        $this->worcode = "WO30817165215125";
//        $this->warCode = "WA000001";
    }

    /**
     * @param $param
     * @return array|bool
     * 创建报表
     */
    public function report_create($param)
    {
        if (!isset($param)) {
            $param = $_POST;
        }
        $userWarCode = $this->warCode;
        $worCode = $this->worcode;
        $warCode = $param['data']['warCode'];
        $type = $param['data']['type'];
        $stime = $param['data']['stime'];
        $etime = $param['data']['etime'];
        $otherMsg = $param['data']['otherMsg'];

        if (empty($type)) {
            $message = "报表类型为空";
            venus_throw_exception(1, $message);
            return false;
        }
        if (empty($warCode)) {
            $message = "客户单位为空";
            venus_throw_exception(1, $message);
            return false;
        }
        if (empty($stime) || empty($etime)) {
            $message = "日期为空";
            venus_throw_exception(1, $message);
            return false;
        }

        if (empty($otherMsg['repName'])) {
            $message = "报表名称为空";
            venus_throw_exception(1, $message);
            return false;
        }

        if ($type == self::$REPORT_TYPE_GOODSTROED_ACCOUNT) {
            if (empty($otherMsg['spCode'])) {
                $message = "请选择货品";
                venus_throw_exception(1, $message);
                return false;
            }
        }

        $repData = array(
            "stime" => $stime,
            "etime" => $etime,
            "type" => $type,
            "warCode" => $warCode,
        );

        if (!empty($otherMsg['spCode'])) {
            $spuModel = SpuDao::getInstance($warCode);
            $spName = $spuModel->queryByCode($otherMsg['spCode'])['spu_name'];
            $repNames = $otherMsg['repName'] . "(" . $spName . ")";
            $repData["spCode"] = $otherMsg['spCode'];
        } else {
            $repNames = $otherMsg['repName'];
        }

        if ($type == self::$REPORT_TYPE_RECEIPT) {
            if (!empty($param['data']['supCode'])) {
                $repData["supCode"] = $param['data']['supCode'];
            }
        }

        if ($type == self::$REPORT_TYPE_INVOICE) {
            if (!empty($param['data']['spType'])) {
                $repData["spType"] = $param['data']['spType'];
                $spuTypeName = venus_spu_type_name($repData["spType"]);
            } else {
                $spuTypeName = "全部";
            }
            if (!empty($param['data']['spSubtype'])) {
                $repData["spSubtype"] = $param['data']['spSubtype'];
                $spuSubTypeName = venus_spu_catalog_name($repData["spSubtype"]);
            } else {
                $spuSubTypeName = "全部";
            }
            if (isset($spuSubTypeName) && isset($spuTypeName) && !empty($spuSubTypeName) && !empty($spuTypeName)) {
                $spuRepName = "出仓单(" . $spuTypeName . "/" . $spuSubTypeName . ")";
            } else {
                $spuRepName = "出仓单(全部/全部)";
            }
        }

        $repNameArr = explode("-", $repNames);

        if (array_key_exists(3, $repNameArr)) {
            $repName = $repNameArr[0] . "年" . $repNameArr[1] . "月" . $repNameArr[2] . "日" . $repNameArr[3];
            if (isset($spuRepName) && !empty($spuRepName)) {
                if ($repNameArr[3] != $spuRepName) {
                    $message = '报表名称不正确';
                    venus_throw_exception(2, $message . $repNameArr[3] . "," . "$spuRepName");
                    return false;
                }
            }
        } else {
            $repName = $repNameArr[0] . "年" . $repNameArr[1] . "月" . $repNameArr[2];
        }

        $reportModel = ReportDao::getInstance($userWarCode);
        $issetReport = $reportModel->queryByName($repName);
        if ($issetReport) {
            venus_throw_exception(4001);
            return false;
        }
        $reportAddData = array(
            "name" => $repName,
            "fname" => "NULL",
            "data" => json_encode($repData),
            "type" => $type,
            "worcode" => $worCode,
        );
        $addRep = $reportModel->insert($reportAddData);

        if (!$addRep) {
            $message = '创建报表失败';
            venus_throw_exception(2, $message);
            return false;
        } else {
            $success = true;
            $data = array();
            $message = '';
            return array($success, $data, $message);
        }
    }

    /**
     * @return array
     * 报表列表
     */
    public function report_search()
    {
        $warCode = $this->warCode;

        $type = $_POST['data']['type'];
        $pageCurrent = $_POST['data']['pageCurrent'];//当前页数
        $clause = array();
        if (!empty($type)) {
            $clause['type'] = $type;
        }
        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }

        $reportModel = ReportDao::getInstance($warCode);
        $workerModel = WorkerDao::getInstance($warCode);

        $totalCount = $reportModel->queryCountByCondition($clause);
        $pageLimit = pageLimit($totalCount, $pageCurrent);
        $reportDataList = $reportModel->queryListByCondition($clause, $pageLimit['page'], $pageLimit['pSize']);
        $data = array();
        $data = array(
            "pageCurrent" => $pageCurrent,
            "pageSize" => $pageLimit['pageSize'],
            "totalCount" => $totalCount,
        );

        foreach ($reportDataList as $value) {

            $data['list'][] = array(
                "code" => $value['rep_code'],
                "repName" => $value['rep_name'],
                "repFname" => $value['rep_fname'],
                "repCtime" => $value['rep_ctime'],
                "repStatus" => $value['rep_status'],
                "repStatMsg" => venus_report_status_desc($value['rep_status']),
                "worName" => $workerModel->queryByCode($value['wor_code'])['wor_name'],
            );
        }
        $success = true;
        $message = '';
        return array($success, $data, $message);
    }

    public function report_delete()
    {
        $warCode = $this->warCode;

        $repCode = $_POST['data']['repCode'];
        if (empty($repCode)) {
            $message = "报表编号为空，请选择报表";
            venus_throw_exception(1, $message);
            return false;
        }

        $reportModel = ReportDao::getInstance($warCode);
        $delReport = $reportModel->deleteByCode($repCode);
        if (!$delReport) {
            $message = '报表删除失败';
            venus_throw_exception(2, $message);
            return false;
        } else {
            $success = true;
            $data = array();
            $message = '';
            return array($success, $data, $message);
        }
    }

    /**
     * @param $param
     * @return array|bool
     * 创建并下载报表
     */
    public function report_create_export($param)
    {

        if (!isset($param)) {
            $param = $_POST;
        }

        $userWarCode = $this->warCode;
        $worCode = $this->worcode;
        $warCode = $param['data']['warCode'];
        $type = $param['data']['type'];
        $stime = $param['data']['stime'];
        $etime = $param['data']['etime'];
        $otherMsg = $param['data']['otherMsg'];

        if (empty($type)) {
            $message = "报表类型为空";
            venus_throw_exception(1, $message);
            return false;
        }
        if (empty($warCode)) {
            $message = "客户单位为空";
            venus_throw_exception(1, $message);
            return false;
        }
        if (empty($stime) || empty($etime)) {
            $message = "日期为空";
            venus_throw_exception(1, $message);
            return false;
        }

        if ($type == self::$REPORT_TYPE_GOODSTROED_ACCOUNT) {
            if (empty($otherMsg['spCode'])) {
                $message = "请选择货品";
                venus_throw_exception(1, $message);
                return false;
            }
        }

        if ($type == self::$REPORT_TYPE_RECEIPT) {
            if (!empty($param['data']['supCode'])) {
                $supCode = $param['data']['supCode'];
            }
        }

        if ($type == self::$REPORT_TYPE_INVOICE) {
            if (!empty($param['data']['spType'])) {
                $repData["spType"] = $param['data']['spType'];
                $spuTypeName = venus_spu_type_name($repData["spType"]);
            } else {
                $spuTypeName = "全部";
            }
            if (!empty($param['data']['spSubtype'])) {
                $repData["spSubtype"] = $param['data']['spSubtype'];
                $spuSubTypeName = venus_spu_catalog_name($repData["spSubtype"]);
            } else {
                $spuSubTypeName = "全部";
            }
            if (isset($spuSubTypeName) && isset($spuTypeName) && !empty($spuSubTypeName) && !empty($spuTypeName)) {
                $spuRepName = "出仓单(" . $spuTypeName . "/" . $spuSubTypeName . ")";
            } else {
                $spuRepName = "出仓单(全部/全部)";
            }
        }

        if (!empty($otherMsg['spCode'])) {
            $spuModel = SpuDao::getInstance($warCode);
            $spName = $spuModel->queryByCode($otherMsg['spCode'])['spu_name'];
            $repNames = $otherMsg['repName'] . "(" . $spName . ")";
        } else {
            $repNames = $otherMsg['repName'];
        }

        $repNameArr = explode("-", $repNames);

        if (array_key_exists(3, $repNameArr)) {
            $repName = $repNameArr[0] . "年" . $repNameArr[1] . "月" . $repNameArr[2] . "日" . $repNameArr[3];
            if (isset($spuRepName) && !empty($spuRepName)) {
                if ($repNameArr[3] != $spuRepName) {
                    $message = '报表名称不正确' . $repNameArr[3] . "," . "$spuRepName";
                    return array(false, array(), $message);
                }
            }
        } else {
            $repName = $repNameArr[0] . "年" . $repNameArr[1] . "月" . $repNameArr[2];
        }

        if ($type == self::$REPORT_TYPE_RECEIPT) {
            return $this->create_receipt_report($supCode, $repName, $stime, $etime, $warCode);
        } elseif ($type == self::$REPORT_TYPE_INVOICE) {
            return $this->create_invoice_report($repData["spType"], $repData["spSubtype"], $repName, $stime, $etime, $warCode);
        } elseif ($type == self::$REPORT_TYPE_RECEIPT_COLLECT) {
            return $this->create_receipt_collect_report($repName, $stime, $etime, $warCode);
        } elseif ($type == self::$REPORT_TYPE_INVOICE_COLLECT) {
            return $this->create_invoice_collect_report($repName, $stime, $etime, $warCode);
        } elseif ($type == self::$REPORT_TYPE_GOODSTROED_COLLECT) {
            return $this->create_goodstored_collect_report($repName, $etime, $warCode);
        } elseif ($type == self::$REPORT_TYPE_GOODSTROED_ACCOUNT) {
            $spuCode = $otherMsg['spCode'];
            return $this->create_goodstored_account_report($repName, $spuCode, $stime, $etime, $warCode);
        } else {
            if ($type == self::$REPORT_TYPE_APPLY || $type == self::$REPORT_TYPE_PURCHUSE) {
                return array(false, array(), "请选择入仓单或出仓单");
            } else {
                return array(false, array(), "请选择创建类型");
            }
        }
    }

    /**
     * @param $supCode供应商编号
     * @param $repName报表名称
     * @param $stime开始时间
     * @param $etime结束时间
     * @param $warCode仓库编号
     * 创建入仓单报表
     */
    private function create_receipt_report($supCode, $repName, $stime, $etime, $warCode)
    {
        $receiptModel = ReceiptDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);
        $supplierModel = SupplierDao::getInstance($warCode);
        $warehouseModel = WarehouseDao::getInstance($warCode);
        $spuModel = SpuDao::getInstance($warCode);
        $clauseReceipt = array(
            "sctime" => $stime,
            "ectime" => $etime,
        );

        $recCodeCount = $receiptModel->queryCountByCondition($clauseReceipt);

        $recDataList = $receiptModel->queryListByCondition($clauseReceipt, 0, $recCodeCount);
        $roomData = array();
        if (empty($recDataList)) return array(false, array(), "无相关数据");
        foreach ($recDataList as $recData) {
            if ($recData['rec_ctime'] >= $stime && $recData['rec_ctime'] < $etime) {
                $recCodeArr[] = $recData['rec_code'];
            }
            if (!empty($recData['rec_room'])) $roomData[$recData['rec_code']] = $recData['rec_room'];
        }


        $clauseGoodsbatch = array(
            "reccodes" => $recCodeArr,
        );
        if (!empty($supCode)) $clauseGoodsbatch['supcode'] = $supCode;
        $goodsbatchDataList = $goodsbatchModel->queryListByCondition($clauseGoodsbatch, 0, 100000);
        if (empty($goodsbatchDataList)) return array(false, array(), "无相关数据");
        if (!empty($supCode)) {
            $supplierName = SupplierDao::getInstance()->queryAllByCode($supCode)['sup_name'];
        }
        $excelData = array();
        foreach ($goodsbatchDataList as $goodsbatchData) {
            if(empty($supCode)) $supplierName = SupplierDao::getInstance()->queryAllByCode($goodsbatchData['sup_code'])['sup_name'];
            $money = bcmul($goodsbatchData['gb_count'], $goodsbatchData['gb_bprice'], 6);
            $spuCode = $goodsbatchData['spu_code'];
            $spuName = trim($goodsbatchData['spu_name']);
            if (empty($goodsbatchData['spu_name'])) return array(false, $goodsbatchData, "{$goodsbatchData['spu_code']}无货品名称");
            $spuUnit = $goodsbatchData['spu_unit'];
            if (!empty($goodsbatchData['rec_code']) && !empty($roomData[$goodsbatchData['rec_code']])) {
                $warName = $warehouseModel->queryClientByCode($warCode)['war_name'] . $roomData[$goodsbatchData['rec_code']];
            } else {
                $warName = $warehouseModel->queryClientByCode($warCode)['war_name'];
            }

            $excelData[$warName][$supplierName][$spuName][$spuCode][$goodsbatchData['gb_bprice']]['count'] =
                floatval(
                    bcadd(
                        $excelData[$warName][$supplierName][$spuName][$spuCode][$goodsbatchData['gb_bprice']]['count'],
                        $goodsbatchData['gb_count'], 6
                    )
                );
            $excelData[$warName][$supplierName][$spuName][$spuCode][$goodsbatchData['gb_bprice']]['unit'] = $spuUnit;
            $excelData[$warName][$supplierName][$spuName][$spuCode][$goodsbatchData['gb_bprice']]['money'] =
                floatval(
                    bcadd(
                        $excelData[$warName][$supplierName][$spuName][$spuCode][$goodsbatchData['gb_bprice']]['money'],
                        $money, 6
                    )
                );
            unset($money);
            $clauseInv = array(
                'gbcode' => $goodsbatchData['gb_code'],
                "invtype" => 6
            );
            $igsDataCount = $igoodsentModel->queryCountByGbCode($clauseInv);
            $igsData = $igoodsentModel->queryListByGbCode($clauseInv, 0, $igsDataCount);

            if (!empty($igsData)) {
                foreach ($igsData as $igsDatum) {
                    if ($igsDatum['inv_type'] != 6) continue;
                    $igsMoney = floatval(bcmul($igsDatum['igs_count'], $igsDatum['igs_bprice'], 6));
                    if (!empty($igsDatum['rec_code']) && !empty($roomData[$igsDatum['rec_code']])) {
                        $warName = $warehouseModel->queryClientByCode($warCode)['war_name'] . $roomData[$igsDatum['rec_code']];
                    } else {
                        $warName = $warehouseModel->queryClientByCode($warCode)['war_name'];
                    }

                    $excelData[$warName][$supplierName][$spuName][$spuCode][$igsDatum['gb_bprice']]['count'] =
                        floatval(
                            bcsub(
                                $excelData[$warName][$supplierName][$spuName][$spuCode][$igsDatum['gb_bprice']]['count'],
                                $igsDatum['igs_count'], 6
                            )
                        );
                    $excelData[$warName][$supplierName][$spuName][$spuCode][$igsDatum['gb_bprice']]['money'] =
                        floatval(
                            bcsub(
                                $excelData[$warName][$supplierName][$spuName][$spuCode][$igsDatum['gb_bprice']]['money'],
                                $igsMoney, 6
                            )
                        );
                }

            }
        }


        if (empty($excelData)) return array(false, array(), "无相关数据");
        return $this->export_receipt($excelData, $repName);

    }

    /**
     * @param $spType
     * @param $spSubtype
     * @param $repName
     * @param $stime
     * @param $etime
     * @param $warCode
     * @return array
     * 创建出仓单报表
     */
    private function create_invoice_report($spType, $spSubtype, $repName, $stime, $etime, $warCode)
    {
        $clause = array();
        $clause['sctime'] = $stime;
        $clause['ectime'] = $etime;
        $invoiceModel = InvoiceDao::getInstance($warCode);
        $invList = $invoiceModel->queryListByCondition($clause, 0, 100000);
        $invDataArr = array();
        if (!empty($invList)) {
            foreach ($invList as $invData) {
                if ($invData['inv_type'] == 6) continue;
                $warName = WorkerDao::getInstance()->queryByCode($invData['wor_code'])['war_name'];
                if (!empty($invData['room'])) {
                    $receiver = $warName . "|" . $invData['inv_receiver'] . "(" . $invData['room'] . ")";
                } else {
                    $receiver = $warName . "|" . $invData['inv_receiver'];
                }
                $invDataArr[$invData['war_code']][$receiver][$invData['inv_receiver']][] = $invData['inv_code'];
            }
        } else {
            return array(false, array(), "无相关数据");
        }

        $excelData = array();
        foreach ($invDataArr as $warCode => $invData) {
            foreach ($invData as $receiver => $goodsArr) {
                foreach ($goodsArr as $user => $goods) {
                    $igoodsentModel = IgoodsentDao::getInstance($warCode);
                    $clauseInvGoods = array(
                        'invcodes' => $goods,
                    );
                    if (!empty($spType)) {
                        $clauseInvGoods['sputype'] = $spType;
                        $spTypeMsg = venus_spu_type_name($spType);
                    } else {
                        $spTypeMsg = "全部";
                    }
                    if (!empty($spSubtype)) {
                        $clauseInvGoods['spusubtype'] = $spSubtype;
                        $spSubtypeMsg = venus_spu_catalog_name($spSubtype);
                    } else {
                        $spSubtypeMsg = "全部";
                    }
                    $invGoodsentDataList = $igoodsentModel->queryListByCondition($clauseInvGoods, 0, 1000000);
                    $typeName = $spTypeMsg . "-" . $spSubtypeMsg;
                    foreach ($invGoodsentDataList as $invGoodsentData) {
                        $spuCode = $invGoodsentData['spu_code'];
                        $spuName = $invGoodsentData['spu_name'];
                        $spuUnit = $invGoodsentData['spu_unit'];
                        $money = bcmul($invGoodsentData['igs_count'], $invGoodsentData['igs_bprice'], 6);
                        $excelData[$receiver][$user][$typeName][$spuName][$spuCode][$invGoodsentData['igs_bprice']]['count'] =
                            floatval(
                                bcadd(
                                    $excelData[$receiver][$user][$typeName][$spuName][$spuCode][$invGoodsentData['igs_bprice']]['count'],
                                    $invGoodsentData['igs_count'], 6
                                )
                            );
                        $excelData[$receiver][$user][$typeName][$spuName][$spuCode][$invGoodsentData['igs_bprice']]['unit'] = $spuUnit;
                        $excelData[$receiver][$user][$typeName][$spuName][$spuCode][$invGoodsentData['igs_bprice']]['money'] =
                            floatval(
                                bcadd(
                                    $excelData[$receiver][$user][$typeName][$spuName][$spuCode][$invGoodsentData['igs_bprice']]['money'],
                                    $money, 6
                                )
                            );
                        unset($money);
                    }
                    unset($invIgoodsDataList);
                }
            }
        }

        if (empty($excelData)) return array(false, array(), "无相关数据");

        return $this->export_invoice($excelData, $repName);
    }

    /**
     * @param $repName报表名称
     * @param $stime开始时间
     * @param $etime结束时间
     * @param $warCode仓库编号
     * @return array
     * 创建并下载入库汇总
     */
    private function create_receipt_collect_report($repName, $stime, $etime, $warCode)
    {

        $receiptModel = ReceiptDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
        $supplierModel = SupplierDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);

        $clause = array();
        $clause['sctime'] = $stime;
        $clause['ectime'] = $etime;
        $recCodeList = $receiptModel->queryListByCondition($clause, 0, 100000);
        $recCodeArr = array_column($recCodeList, "rec_code");
        $recGoodsbatchDataListArr = array();
        $warName = WarehouseDao::getInstance()->queryClientByCode($warCode)['war_name'];
        $dateArr = array();

        $year = date("Y", strtotime($stime));
        $sMonth = date("m", strtotime($stime));
        $eMonth = date("m", strtotime($etime));
        $sDate = date("d", strtotime($stime));
        $eDate = date("d", strtotime($etime));
        if ($sMonth == $eMonth) {
            for ($i = $sDate; $i < $eDate; $i++) {
                if (strlen($i) == 1) {
                    $dateArr[] = $sMonth . '-0' . $i;
                } else {
                    $dateArr[] = $sMonth . '-' . $i;
                }
            }
        } else {
            $sDays = get_days_by_year_and_month($year, $sMonth);
            for ($i = $sDate; $i <= $sDays; $i++) {
                if (strlen($i) == 1) {
                    $dateArr[] = $sMonth . '-0' . $i;
                } else {
                    $dateArr[] = $sMonth . '-' . $i;
                }
            }
            for ($i = 1; $i < $eDate; $i++) {
                if (strlen($i) == 1) {
                    $dateArr[] = $eMonth . '-0' . $i;
                } else {
                    $dateArr[] = $eMonth . '-' . $i;
                }
            }
        }

        $supplierNameArr = array(
            "科贸（鲜鱼水菜）",
            "科贸供货商",
            "科贸（休闲食品）",
            "总公司（调拨）",
            "自采",
        );


        if (empty($recCodeArr)) return array(false, array(), "无相关数据");
        $clauseRecGoods = array(
            "reccodes" => $recCodeArr
        );

        $recGoodsbatchDataList = $goodsbatchModel->queryListByCondition($clauseRecGoods, 0, 100000);
        $supMoney = array();
        foreach ($recGoodsbatchDataList as $recGoodsbatchData) {
            if ($recGoodsbatchData['gb_count'] == 0) continue;
            $supplierName = $supplierModel->queryAllByCode($recGoodsbatchData['sup_code'])['sup_name'];

            if (!empty($supplierName)) {
                if (!in_array($supplierName, $supplierNameArr)) {
                    $supplierNameArr[] = $supplierName;
                }
                $recCtime = $receiptModel->queryByCode($recGoodsbatchData['rec_code'])['rec_ctime'];
                $date = date("m-d", strtotime($recCtime));
                $money = floatval(bcmul($recGoodsbatchData['gb_count'], $recGoodsbatchData['gb_bprice'], 6));
                if (!isset($recGoodsbatchDataListArr[$warName][$supplierName][$date])) $recGoodsbatchDataListArr[$warName][$supplierName][$date] = 0;
                if (!isset($recGoodsbatchDataListArr[$warName][$date])) $recGoodsbatchDataListArr[$warName][$date] = 0;
                $recGoodsbatchDataListArr[$warName][$supplierName][$date] =
                    floatval(
                        bcadd(
                            $recGoodsbatchDataListArr[$warName][$supplierName][$date],
                            $money, 6
                        )
                    );
                $recGoodsbatchDataListArr[$warName][$date] =
                    floatval(
                        bcadd(
                            $recGoodsbatchDataListArr[$warName][$date], $money,
                            6
                        )
                    );

                $supMoney[$supplierName] = floatval(
                    bcadd(
                        $supMoney[$supplierName],
                        $money, 6
                    )
                );
            }
        }

        $clauseInv = array(
            'recsctime' => $stime,
            'recectime' => $etime,
            "invtype" => 6
        );
        $igsDataCount = $igoodsentModel->queryCountByGbCode($clauseInv);
        $igsData = $igoodsentModel->queryListByGbCode($clauseInv, 0, $igsDataCount);

        if (!empty($igsData)) {
            foreach ($igsData as $igsDatum) {
                if ($igsDatum['inv_type'] != 6) continue;
                $supplierName = $supplierModel->queryAllByCode($igsDatum['sup_code'])['sup_name'];
                $recCtime = $igsDatum['rec_ctime'];
                $date = date("m-d", strtotime($recCtime));
                $money = floatval(bcmul($igsDatum['igs_count'], $igsDatum['igs_bprice'], 6));

                $recGoodsbatchDataListArr[$warName][$supplierName][$date] =
                    floatval(
                        bcsub(
                            $recGoodsbatchDataListArr[$warName][$supplierName][$date],
                            $money, 6
                        )
                    );
                $recGoodsbatchDataListArr[$warName][$date] =
                    floatval(
                        bcsub(
                            $recGoodsbatchDataListArr[$warName][$date], $money,
                            6
                        )
                    );

                $supMoney[$supplierName] = floatval(
                    bcsub(
                        $supMoney[$supplierName],
                        $money, 6
                    )
                );
            }

        }


        $data = array();
        $data[$repName] = array(
            "C1" => $repName,
        );
        $letters = array();
        for ($letter = 0; $letter < count($supplierNameArr) + 2; $letter++) {
            $letters[] = chr(65 + $letter);
        }

        $line = array();
        $dateArray = array();
        $dateKey = 0;
        foreach ($dateArr as $date) {
            $dateArray[$dateKey] = $date;
            $line[$dateKey + 1][] = $date;
            $dateKey++;
        }

        foreach ($recGoodsbatchDataListArr as $warNameKey => $goodArr) {

            foreach ($goodArr as $goodsKey => $goods) {
                if (is_array($goods)) {
                    foreach ($goods as $goodKey => $good) {
                        $gk = array_keys($dateArray, $goodKey)[0] + 1;
                        $line[$gk][$goodsKey] = $good;
                    }
                } else {
                    $gk = array_keys($dateArray, $goodsKey)[0] + 1;
                    $line[$gk]['money'] = $goodArr[$goodsKey];
                }
            }


        }

        $countLineNum = count($line) + 3;
        for ($rows = 1; $rows < count($letters); $rows++) {
            $num = 2;
            $row = $rows - 1;
            $totalLine = $countLineNum;
            if ($rows == 1) {
                $data[$repName][$letters[$row] . $totalLine] = "合计";
            }
            if ($rows != count($letters) - 1) {
                $data[$repName][$letters[$rows] . $num] = $supplierNameArr[$row];
                $data[$repName][$letters[$rows] . $totalLine] = $supMoney[$supplierNameArr[$row]];
            } else {
                $data[$repName][$letters[$rows] . $num] = "合计";
                $data[$repName][$letters[$rows] . $totalLine] = array_sum($supMoney);
            }

        }

        for ($lineNum = 3; $lineNum < $countLineNum; $lineNum++) {
            for ($rows = 0; $rows < count($letters); $rows++) {
                $num = $letters[$rows] . $lineNum;

                if ($rows == count($letters) - 1) {
                    $data[$repName][$num] = $line[$lineNum - 2]['money'];
                } else {
                    if ($rows != 0) {
                        $data[$repName][$num] = $line[$lineNum - 2][$data[$repName][$letters[$rows] . 2]];
                    } else {
                        $data[$repName][$num] = $line[$lineNum - 2][$rows];
                    }


                }
            }

        }


        if (empty($data)) return array(false, array(), "无相关数据");
        $fileName = ExcelService::getInstance()->exportExcelByTemplate($data, "011");
        return array(true, array("repName" => $repName, "fileName" => $fileName), "");
    }

    /**
     * @param $repName
     * @param $stime
     * @param $etime
     * @param $warCode
     * @return array
     * 创建出库汇总并下载
     */
    private function create_invoice_collect_report($repName, $stime, $etime, $warCode)
    {


        $invoiceModel = InvoiceDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);

        $clause = array();
        $clause['sctime'] = $stime;
        $clause['ectime'] = $etime;
        $clause['invtype'] = array("neq", 6);
        $list = $invoiceModel->queryListByCondition($clause, 0, 100000);
        $invCodeList = array_column($list, "inv_code");

        $invGoodsentDataListArr = array();
        $line = array();
        $warName = WarehouseDao::getInstance()->queryClientByCode($warCode)['war_name'];
        $dateArr = array();
        $year = date("Y", strtotime($stime));
        $sMonth = date("m", strtotime($stime));
        $eMonth = date("m", strtotime($etime));
        $sDate = date("d", strtotime($stime));
        $eDate = date("d", strtotime($etime));
        if ($sMonth == $eMonth) {
            for ($i = $sDate; $i < $eDate; $i++) {
                if (strlen($i) == 1) {
                    $dateArr[] = $sMonth . '-0' . $i;
                } else {
                    $dateArr[] = $sMonth . '-' . $i;
                }
            }
        } else {
            $sDays = get_days_by_year_and_month($year, $sMonth);
            for ($i = $sDate; $i <= $sDays; $i++) {
                if (strlen($i) == 1) {
                    $dateArr[] = $sMonth . '-0' . $i;
                } else {
                    $dateArr[] = $sMonth . '-' . $i;
                }
            }
            for ($i = 1; $i < $eDate; $i++) {
                if (strlen($i) == 1) {
                    $dateArr[] = $eMonth . '-0' . $i;
                } else {
                    $dateArr[] = $eMonth . '-' . $i;
                }
            }
        }
        $spuTypeMoney = array();

        if (empty($invCodeList)) return array(false, array(), "无相关数据");
        $spuTypeNameArr = array(
            "1" => "蔬菜",
            "2" => "水果",
            "3" => "奶制品",
            "4" => "豆制品",
            "5" => "猪肉",
            "6" => "牛羊肉",
            "7" => "禽类",
            "8" => "水产(海鲜)",
            "9" => "蛋类",
            "10" => "米",
            "11" => "面",
            "12" => "杂粮",
            "13" => "油",
            "14" => "调料",
            "15" => "干货",
            "16" => "酒水饮料",
            "17" => "物料",
            "18" => "小食品",
        );
        $clauseInvGoods = array("in", $invCodeList);
        $invGoodsentDataList = $igoodsentModel->queryListByInvCode($clauseInvGoods, 0, 100000);

        $emptyRepType = array();
        foreach ($invGoodsentDataList as $invGoodsentData) {
            $invData = $invoiceModel->queryByCode($invGoodsentData['inv_code']);
            $spuTypeName = $spuTypeNameArr[$invGoodsentData['spu_reptype']];
            if (!empty($spuTypeName) && $invData['inv_type'] != 6) {
                $invCtime = $invData['inv_ctime'];
                $date = date("m-d", strtotime($invCtime));
                $money = floatval(bcmul($invGoodsentData['igs_count'], $invGoodsentData['igs_bprice'], 6));

                $invGoodsentDataListArr[$warName][$spuTypeName][$date] = bcadd($invGoodsentDataListArr[$warName][$spuTypeName][$date], $money, 2);

                $invGoodsentDataListArr[$warName][$date] = bcadd($invGoodsentDataListArr[$warName][$date], $money, 2);


                $spuTypeMoney[$spuTypeName] = bcadd($spuTypeMoney[$spuTypeName], $money, 2);

                unset($money);
            } else {
                if (empty($spuTypeName) && !array_key_exists($invGoodsentData['spu_code'], $emptyRepType)) {
                    $emptyRepType[$invGoodsentData['spu_code']] = $invGoodsentData['spu_name'];
                }
            }
        }
        if (!empty($emptyRepType)) {
            $msg = "有货品未分配报表分类:" . "<br>";
            foreach ($emptyRepType as $spuCode => $spuName) {
                $msg .= "编号" . $spuCode . "货品" . $spuName . "<br>";
            }
            return array(false, array(), $msg);
        }

        $data = array();
        $data[$repName] = array(
            "C1" => $repName,
        );
        $letters = array();
        for ($letter = 0; $letter < count($spuTypeNameArr) + 2; $letter++) {
            $letters[] = chr(65 + $letter);
        }
        $line = array();
        $dateArray = array();
        $dateKey = 0;
        foreach ($dateArr as $date) {
            $dateArray[$dateKey] = $date;
            $line[$dateKey + 1][] = $date;
            $dateKey++;
        }

        foreach ($invGoodsentDataListArr as $warNameKey => $goodArr) {

            foreach ($goodArr as $goodsKey => $goods) {
                if (is_array($goods)) {
                    foreach ($goods as $goodKey => $good) {
                        $gk = array_keys($dateArray, $goodKey)[0] + 1;
                        $line[$gk][$goodsKey] = $good;

                    }
                } else {
                    $gk = array_keys($dateArray, $goodsKey)[0] + 1;
                    $line[$gk]['money'] = $goodArr[$goodsKey];
                }
            }


        }

        $countLineNum = count($line) + 3;
        for ($rows = 1; $rows < count($letters); $rows++) {
            $num = 2;
            $row = $rows - 1;
            $totalLine = $countLineNum;

            if ($rows == 1) {
                $data[$repName][$letters[$row] . $totalLine] = "合计";
            }
            if ($rows == count($letters) - 1) {
                $data[$repName][$letters[$rows] . $num] = "合计";
                $data[$repName][$letters[$rows] . $totalLine] = array_sum($spuTypeMoney);

            } else {
                $data[$repName][$letters[$rows] . $num] = $spuTypeNameArr[$rows];
                $data[$repName][$letters[$rows] . $totalLine] = $spuTypeMoney[$spuTypeNameArr[$rows]];

            }
        }

        for ($lineNum = 3; $lineNum < $countLineNum; $lineNum++) {
            for ($rows = 0; $rows < count($letters); $rows++) {
                $num = $letters[$rows] . $lineNum;
                if ($rows == count($letters) - 1) {
                    $data[$repName][$num] = $line[$lineNum - 2]['money'];
                } else {
                    if ($rows != 0) {
                        $data[$repName][$num] = $line[$lineNum - 2][$data[$repName][$letters[$rows] . 2]];
                    } else {
                        $data[$repName][$num] = $line[$lineNum - 2][$rows];
                    }
                }
            }

        }
        if (empty($data)) return array(false, array(), "无相关数据");
        $fileName = ExcelService::getInstance()->exportExcelByTemplate($data, "021");
        return array(true, array("repName" => $repName, "fileName" => $fileName), "");
    }

    /**
     * @param $repName
     * @param $stime
     * @param $etime
     * @param $warCode
     * @return array
     * 创建库存汇总并下载
     */
    private function create_goodstored_collect_report($repName, $etime, $warCode)
    {

        $receiptModel = ReceiptDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
        $invoiceModel = InvoiceDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);


        $prevMonthDataList = array();
        $goodsDataList = array();
        $clause = array(
            "ectime" => $etime
        );

        $recCodeList = $receiptModel->queryListByCondition($clause, 0, 100000);
        $recCodeArr = array_column($recCodeList, "rec_code");
        $clauseRecGoods = array(
            "reccodes" => $recCodeArr
        );
        $prevMonthGbData = $goodsbatchModel->queryPrevMonth($clauseRecGoods, 0, 100000);

        if (!empty($prevMonthGbData)) {
            $prevMonthDataList['gb'][] = $prevMonthGbData;
        }
        $invCodeList = $invoiceModel->queryListByCondition($clause, 0, 100000);
        $invCodeArr = array_column($invCodeList, "inv_code");
        $clauseInvGoods = array("invcodes" => $invCodeArr);
        $igsData = $igoodsentModel->queryPrevMonth($clauseInvGoods, 0, 10000000);
        if (!empty($igsData)) {
            $prevMonthDataList['igs'][] = $igsData;
        }
        if (empty($prevMonthDataList)) return array(false, array(), "无相关数据");
        $gbCount = array();
        $gbPrice = array();
        $igsCount = array();
        $igsPrice = array();
        $count = array();
        $price = array();
        $bprice = array();
        $spuCodeArr = array();
        if (!empty($prevMonthDataList['gb'])) {
            foreach ($prevMonthDataList['gb'] as $prevMonthData) {
                foreach ($prevMonthData as $prevMonthDatum) {
                    $spuCode = $prevMonthDatum['spu_code'];
                    $gbCount[$spuCode] = floatval(bcadd($gbCount[$spuCode], $prevMonthDatum['gb_count'], 6));
                    $gbPrice[$spuCode] = floatval(bcadd($gbPrice[$spuCode], bcmul($prevMonthDatum['gb_count'], $prevMonthDatum['gb_bprice'], 6), 6));
                    if (!array_key_exists($spuCode, $spuCodeArr)) {
                        $spuCodeArr[$spuCode] = array(
                            "spu_type" => $prevMonthDatum['spu_code'],
                            "spu_name" => $prevMonthDatum['spu_name'],
                            "spu_unit" => $prevMonthDatum['spu_unit'],
                        );
                    }
                }
            }

        }

        if (!empty($prevMonthDataList['igs'])) {
            foreach ($prevMonthDataList['igs'] as $prevMonthData) {
                foreach ($prevMonthData as $prevMonthDatum) {
                    $spuCode = $prevMonthDatum['spu_code'];
                    $igsCount[$spuCode] = floatval(bcadd($igsCount[$spuCode], $prevMonthDatum['igs_count'], 6));
                    $igsPrice[$spuCode] = floatval(bcadd($igsPrice[$spuCode], bcmul($prevMonthDatum['igs_count'], $prevMonthDatum['igs_bprice'], 6), 6));
                    if (!array_key_exists($spuCode, $spuCodeArr)) {
                        $spuCodeArr[$spuCode] = array(
                            "spu_type" => $prevMonthDatum['spu_type'],
                            "spu_name" => $prevMonthDatum['spu_name'],
                            "spu_unit" => $prevMonthDatum['spu_unit'],
                        );
                    }
                }
            }

        }

        foreach ($spuCodeArr as $spuCode => $spuData) {
            $count[$spuCode] = floatval(bcsub($gbCount[$spuCode], $igsCount[$spuCode], 2));
            $price[$spuCode] = floatval(bcsub($gbPrice[$spuCode], $igsPrice[$spuCode], 2));
            $bprice[$spuCode] = floatval(bcdiv($price[$spuCode], $count[$spuCode], 2));
        }

        foreach ($prevMonthDataList['gb'] as $prevMonthData) {
            foreach ($prevMonthData as $prevMonthDatum) {
                if (!array_key_exists($prevMonthDatum['spu_code'], $goodsDataList[$warCode][$prevMonthDatum['spu_type']])) {
                    $goodsDataList[$warCode][$prevMonthDatum['spu_type']][$prevMonthDatum['spu_code']] = array($prevMonthDatum['spu_name'], $prevMonthDatum['spu_unit'], $count[$prevMonthDatum['spu_code']], $bprice[$prevMonthDatum['spu_code']], $price[$prevMonthDatum['spu_code']]);
                }

            }
        }
        $letters = array();
        for ($letter = 0; $letter < 13; $letter++) {
            $letters[] = chr(65 + $letter);
        }

        if (empty($goodsDataList)) return array(false, array(), "无相关数据");
        $excelGoodsData = array();
        foreach ($goodsDataList as $goodsData) {
            foreach ($goodsData as $goodsType => $goodsTypeDataList) {
                foreach ($goodsTypeDataList as $goodsTypeData) {
                    $sheetName = $repName . "-全部";
                    $excelGoodsData[$sheetName][] = $goodsTypeData;
                    $spuSheetName = $repName . "-" . venus_spu_type_name($goodsType);
                    $excelGoodsData[$spuSheetName][] = $goodsTypeData;
                }
            }
        }
        unset($goodsDataList);
        $goodsDataList = array();
        if (empty($excelGoodsData)) return array(false, array(), "无相关数据");
        $data = array();
        foreach ($excelGoodsData as $sheetName => $goodsDataList) {
            $data[$sheetName] = array(
                "A1" => $sheetName,
            );
            $line = array();
            $goodsKey = 1;
            $countGoodsData = count($goodsDataList);
            $totalLine = bcdiv($countGoodsData, 2, 0);
            if (bcmod($countGoodsData, 2) != 0) {
                $totalLine = $totalLine + 1;
            }
            foreach ($goodsDataList as $goodsData) {
                array_unshift($goodsData, $goodsKey);
                if ($goodsKey > $totalLine && array_key_exists($goodsKey - $totalLine, $line)) {
                    $lineData = array_merge($line[$goodsKey - $totalLine], $goodsData);
                    unset($line[$goodsKey - $totalLine]);
                    unset($line[$goodsData]);
                    $line[$goodsKey - $totalLine] = $lineData;
                } else {
                    $line[$goodsKey] = $goodsData;
                }
                $goodsKey++;
            }
            foreach ($line as $lineValue) {
                foreach ($lineValue as $lineValueRow => $lineValueCell) {
                    $cellStr = $lineValue[0] + 2;
                    $num = $letters[$lineValueRow] . $cellStr;
                    $data[$sheetName][$num] = $lineValueCell;
                }
            }
            $goodsCountOne = 0;
            $goodsCountTwo = 0;

            foreach ($data[$sheetName] as $key => $datum) {
                if (substr($key, 0, 1) == "F") {
                    $goodsCountOne = bcadd($goodsCountOne, $datum, 2);
                }
                if (substr($key, 0, 1) == "L") {
                    $goodsCountTwo = bcadd($goodsCountTwo, $datum, 2);
                }
            }
            $cellGoodsCount = $totalLine + 3;
            $totalCellOne = "A" . $cellGoodsCount;
            $totalCellTwo = "G" . $cellGoodsCount;
            $data[$sheetName][$totalCellOne] = "合计";
            $data[$sheetName][$totalCellTwo] = "合计";
            $numGoodsOne = $letters["5"] . $cellGoodsCount;
            $numGoodsTwo = $letters["11"] . $cellGoodsCount;
            $data[$sheetName][$numGoodsOne] = $goodsCountOne;
            $data[$sheetName][$numGoodsTwo] = $goodsCountTwo;
            unset($sheetNameStr);
            unset($sheetName);
        }
//        echo json_encode($data);
//        exit();
        if (empty($data)) return array(false, array(), "无相关数据");
        $fileName = ExcelService::getInstance()->exportExcelByTemplate($data, "030");
        return array(true, array("repName" => $repName, "fileName" => $fileName), "");
    }

    /**
     * @param $repName
     * @param $spuCode
     * @param $stime
     * @param $etime
     * @param $warCode
     * @return array
     * 创建台账并下载
     */
    private function create_goodstored_account_report($repName, $spuCode, $stime, $etime, $warCode)
    {
        $receiptModel = ReceiptDao::getInstance($warCode);
        $invoiceModel = InvoiceDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);

        $list = array();
        $spuData = $goodsModel->queryBySpuCode($spuCode);
        $warName = WarehouseDao::getInstance()->queryClientByCode($warCode)['war_name'];
        $file = array(
            'name' => $repName,
            'warCode' => $warCode,//仓库编号
            'warName' => $warName,
            'spCode' => $spuData['spu_code'],//货品编号
            'spNorm' => $spuData['spu_norm'],//规格
            'spUnit' => $spuData['spu_unit'],//单位
            'spName' => $spuData['spu_name'],//名称
            'count' => $spuData['goods_count'],//库存
            'year' => date("Y", strtotime($stime)),
            'month' => date("m", strtotime($stime)),
        );
        unset($spuData);

        $clause = array();
        $clause['sctime'] = $stime;
        $clause['ectime'] = $etime;

        $recCodeList = $receiptModel->queryListByCondition($clause, 0, 100000);
        $recCtimeArr = array();
        foreach ($recCodeList as $recData) {
            $recCtimeArr[$recData['rec_code']] = $recData['rec_ctime'];
        }
        $recCodeArr = array_column($recCodeList, "rec_code");
        $clauseRecGoods = array(
            "reccodes" => $recCodeArr,
            "spucode" => $spuCode
        );

        $receiptDataList = $goodsbatchModel->queryListGoodsByCondition($clauseRecGoods, 0, 100000);

        if (!empty($receiptDataList)) {
            $goodsbatchDataToList = array();
            foreach ($receiptDataList as $receiptData) {
                if ($receiptData['spu_code'] != $spuCode) continue;
                $money = round(bcmul($receiptData['gb_count'], $receiptData['gb_bprice'], 6), 2);
                $ctime = $recCtimeArr[$receiptData['rec_code']];
                $date = date("m-d", strtotime($ctime));
                $month = date("m", strtotime($ctime));
                $day = date("d", strtotime($ctime));
                $goodsbatchDataToList = array(
                    "0" => $month,
                    "1" => $day,
                    "3" => $receiptData['rec_code'],
                    "8" => $receiptData['gb_count'],
                    "9" => $receiptData['gb_bprice'],
                    "10" => $money,
                );
                $list[$ctime][] = $goodsbatchDataToList;
                unset($money);
            }
        }


        $igoodsSpu = array();
        $igoodsentDataList = array();
        $igoodsentDataToList = array();

        $invCodeList = $invoiceModel->queryListByCondition($clause, 0, 100000);
        $invCodeArr = array_column($invCodeList, "inv_code");
        $invCtimeArr = array();
        foreach ($invCodeList as $invData) {
            $invCtimeArr[$invData['inv_code']] = $invData['inv_ctime'];
        }
        $clauseInvGoods = array("invcodes" => $invCodeArr, "spucode" => $spuCode);
        $igoodsentDataList = $igoodsentModel->queryListByCondition($clauseInvGoods, 0, 100000);
        $igsDataArr = array();
        foreach ($igoodsentDataList as $igoodsentKey => $igoodsentData) {
            $money = round(bcmul($igoodsentData['igs_count'], $igoodsentData['igs_bprice'], 6), 2);
            $ctime = $invCtimeArr[$igoodsentData['inv_code']];
            $date = date("m-d", strtotime($invCtimeArr[$igoodsentData['inv_code']]));
            $month = date("m", strtotime($invCtimeArr[$igoodsentData['inv_code']]));
            $day = date("d", strtotime($invCtimeArr[$igoodsentData['inv_code']]));
            $igoodsentDataToList = array(
                "0" => $month,
                "1" => $day,
                "3" => $igoodsentData['inv_code'],
                "11" => $igoodsentData['igs_count'],
                "12" => $igoodsentData['igs_bprice'],
                "13" => $money,
            );

            $list[$ctime][] = $igoodsentDataToList;
            unset($money);
            if (!in_array($spuCode, $igoodsSpu)) {
                $igoodsSpu[] = $spuCode;
            }
        }
        ksort($list);
        unset($invoiceDataList);
        unset($receiptDataList);
        unset($clause);
        $prevMonthDataList = array();


        $clause = array();
        $clause['ectime'] = $stime;

        $recCodeList = $receiptModel->queryListByCondition($clause, 0, 100000);
        $recCodeArr = array_column($recCodeList, "rec_code");
        $clauseRecGoods = array(
            "reccodes" => $recCodeArr,
            "spucode" => $spuCode
        );

        $prevMonthGbData = $goodsbatchModel->queryPrevMonth($clauseRecGoods, 0, 100000);

        if (!empty($prevMonthGbData)) {
            $prevMonthDataList['gb'][] = $prevMonthGbData;
        }
        unset($clause);
        $clause = array(
            "ectime" => $stime,
        );

        $invCodeList = $invoiceModel->queryListByCondition($clause, 0, 100000);
        $invCodeArr = array_column($invCodeList, "inv_code");
        $clauseInvGoods = array("invcodes" => $invCodeArr, "spucode" => $spuCode);
        $igsData = array();
        $igsData = $igoodsentModel->queryPrevMonth($clauseInvGoods, 0, 100000);
        $prevMonthDataList['igs'][] = $igsData;

        if (!empty($prevMonthDataList['gb'])) {
            $gbCount = 0;
            $gbPrice = 0;
            foreach ($prevMonthDataList['gb'] as $prevMonthData) {
                foreach ($prevMonthData as $prevMonthDatum) {
                    $gbCount = bcadd($gbCount, $prevMonthDatum['gb_count'], 2);
                    $gbPrice = bcadd($gbPrice, bcmul($prevMonthDatum['gb_count'], $prevMonthDatum['gb_bprice'], 6), 2);
                }
            }

        } else {
            $gbCount = 0;
            $gbPrice = 0;
        }

        if (!empty($prevMonthDataList['igs'])) {
            $igsCount = 0;
            $igsPrice = 0;
            foreach ($prevMonthDataList['igs'] as $prevMonthData) {
                foreach ($prevMonthData as $prevMonthDatum) {
                    $igsCount = bcadd($igsCount, $prevMonthDatum['igs_count'], 2);
                    $igsPrice = bcadd($igsPrice, bcmul($prevMonthDatum['igs_count'], $prevMonthDatum['igs_bprice'], 6), 2);
                }
            }

        } else {
            $igsCount = 0;
            $igsPrice = 0;
        }

        $count = $gbCount - $igsCount;
        $price = $gbPrice - $igsPrice;
        $sprice = round(bcdiv($price, $count, 6), 2);
        $file['count'] = $count;
        $file['sprice'] = $sprice;
        $file['price'] = $price;
        $letters = array();
        for ($letter = 0; $letter < 17; $letter++) {
            $letters[] = chr(65 + $letter);
        }

        if (empty($prevMonthDataList) && empty($list)) {
            return array(false, array(), "无相关数据");
        }

        $line = array();
        foreach ($list as $reportDatum) {
            foreach ($reportDatum as $reportDaitem)
                $line[] = $reportDaitem;
        }
        $line = array_chunk($line, 30);
        $data = array();
        foreach ($line as $lineKey => $lineValueArr) {
            $count = count($line);
            if ($count > 1) {
                $sheetNameStr = $lineKey + 1;
                $sheetName = $repName . "-" . $sheetNameStr;
            } else {
                $sheetName = $repName;
            }
            $data[$sheetName] = array(
                "A1" => $sheetName,
                "B2" => $file['spCode'],
                "D2" => $file['spNorm'],
                "O2" => $file['spUnit'],
                "Q2" => $file['spName'],
                "A3" => $file['year'],
                "A5" => $file['month'],
                "B5" => "01",
                "D5" => "上月合计",
                "I5" => $file['count'],
                "J5" => $file['sprice'],
                "K5" => $file['price'],
                "O5" => $file['count'],
                "P5" => $file['sprice'],
                "Q5" => $file['price']

            );

            foreach ($lineValueArr as $lineValueKey => $lineValue) {

                foreach ($lineValue as $lineValueRow => $lineValueCell) {
                    $cellStr = $lineValueKey + 6;
                    $num = $letters[$lineValueRow] . $cellStr;
                    $data[$sheetName][$num] = $lineValueCell;
                    if ($lineValueRow == "10" || $lineValueRow == "13") {
                        $cellStrPrev = $lineValueKey + 5;
                        $numPrevToNum = $letters["14"] . $cellStrPrev;
                        $numPrevToPrice = $letters["15"] . $cellStrPrev;
                        $numPrevToMoney = $letters["16"] . $cellStrPrev;
                        $numToNum = $letters["14"] . $cellStr;
                        $numToPrice = $letters["15"] . $cellStr;
                        $numToMoney = $letters["16"] . $cellStr;
                        if ($lineValueRow == "10") {
                            $numNowToNum = $letters["8"] . $cellStr;
                            $numNowToPrice = $letters["9"] . $cellStr;
                            $numNowToMoney = $letters["10"] . $cellStr;
                            $data[$sheetName][$numToNum] = bcadd($data[$sheetName][$numPrevToNum], $data[$sheetName][$numNowToNum], 2);
                            $data[$sheetName][$numToMoney] = bcadd($data[$sheetName][$numNowToMoney], $data[$sheetName][$numPrevToMoney], 2);
                            $data[$sheetName][$numToPrice] = round(bcdiv($data[$sheetName][$numToMoney], $data[$sheetName][$numToNum], 6), 2);
                        } else {
                            $numNowToNum = $letters["11"] . $cellStr;
                            $numNowToPrice = $letters["12"] . $cellStr;
                            $numNowToMoney = $letters["13"] . $cellStr;
                            $data[$sheetName][$numToNum] = $data[$sheetName][$numPrevToNum] - $data[$sheetName][$numNowToNum];
                            $data[$sheetName][$numToMoney] = $data[$sheetName][$numPrevToMoney] - $data[$sheetName][$numNowToMoney];
                            $data[$sheetName][$numToPrice] = round(bcdiv($data[$sheetName][$numToMoney], $data[$sheetName][$numToNum], 6), 2);
                        }
                    }
                }
                if ($lineValueKey == count($lineValueArr) - 1) {
                    $recCount = 0;
                    $recMoney = 0;
                    $recPrice = 0;
                    $invCount = 0;
                    $invMoney = 0;

                    foreach ($data[$sheetName] as $key => $datum) {
                        $invPrice = 0;
                        if (substr($key, 0, 1) == "I") {
                            $recCount += $datum;
                        }
                        if (substr($key, 0, 1) == "K") {
                            $recMoney += $datum;
                        }
                        if (substr($key, 0, 1) == "L") {
                            $invCount += $datum;
                        }
                        if (substr($key, 0, 1) == "N") {
                            $invMoney += $datum;
                        }
                    }

                    $recPrice = round(bcdiv($recMoney, $recCount, 6), 2);
                    $invPrice = round(bcdiv($invMoney, $invCount, 6), 2);
                    $countLastLine = $recCount - $invCount;
                    $moneyLastLine = $recMoney - $invMoney;
                    $priceLastLine = round(bcdiv($moneyLastLine, $countLastLine, 6), 2);
                    $cellLastLineStr = 35;
                    $numLastLineToRecNum = $letters["8"] . $cellLastLineStr;
                    $numLastLineToRecPrice = $letters["9"] . $cellLastLineStr;
                    $numLastLineToRecMoney = $letters["10"] . $cellLastLineStr;
                    $numLastLineToInvNum = $letters["11"] . $cellLastLineStr;
                    $numLastLineToInvPrice = $letters["12"] . $cellLastLineStr;
                    $numLastLineToInvMoney = $letters["13"] . $cellLastLineStr;
                    $numLastLineToNum = $letters["14"] . $cellLastLineStr;
                    $numLastLineToPrice = $letters["15"] . $cellLastLineStr;
                    $numLastLineToMoney = $letters["16"] . $cellLastLineStr;
                    $data[$sheetName][$numLastLineToRecNum] = $recCount;
                    $data[$sheetName][$numLastLineToRecPrice] = $recPrice;
                    $data[$sheetName][$numLastLineToRecMoney] = $recMoney;
                    $data[$sheetName][$numLastLineToInvNum] = $invCount;
                    $data[$sheetName][$numLastLineToInvPrice] = $invPrice;
                    $data[$sheetName][$numLastLineToInvMoney] = $invMoney;
                    $data[$sheetName][$numLastLineToNum] = $countLastLine;
                    $data[$sheetName][$numLastLineToPrice] = $priceLastLine;
                    $data[$sheetName][$numLastLineToMoney] = $moneyLastLine;
                    unset($recCount);
                    unset($recPrice);
                    unset($recMoney);
                    unset($invCount);
                    unset($invPrice);
                    unset($invMoney);
                    unset($countLastLine);
                    unset($moneyLastLine);
                    unset($priceLastLine);
                }
            }
            unset($sheetNameStr);
            unset($sheetName);
        }
        if (empty($data)) return array(false, array(), "本月除期初外无相关数据，请下载上个月记录");
        $fileName = ExcelService::getInstance()->exportExcelByTemplate($data, "040");
        return array(true, array("repName" => $repName, "fileName" => $fileName), "");
    }

    /**
     * @param $excelData
     * @param $repName
     * @return array
     * 入仓单格式
     */
    private
    function export_receipt($excelData, $repName)
    {
        $data = array();

        $letters = array(
            "A", "B", "C", "D", "E", "F"
        );
        foreach ($excelData as $warName => $goods) {
            foreach ($goods as $supplierName => $goodDataArr) {
                $line = array();
                foreach ($goodDataArr as $spuName => $goodDataArray) {
                    foreach ($goodDataArray as $goodData) {
                        foreach ($goodData as $bprice => $goodDatum) {
                            $goodsToLine = array(count($line) + 1, $spuName, $goodDatum['count'], $goodDatum['unit'], $bprice, $goodDatum['money']);
                            $line[] = $goodsToLine;
                        }
                    }
                }
                $money = 0;
                $sheetName = $supplierName . "-" . $warName;
                $data[$sheetName] = array(
                    "A1" => explode("(", $repName)[0],
                    "B2" => $supplierName,
                    "C2" => date("Y年m月d日", time()),
                    "F2" => $warName,
                );
                $countLineItemNum = count($line) + 4;
                for ($lineNum = 4; $lineNum < $countLineItemNum; $lineNum++) {
                    for ($rows = 0; $rows < count($letters); $rows++) {
                        $num = $letters[$rows] . $lineNum;
                        $data[$sheetName][$num] = $line[$lineNum - 4][$rows];
                        if ($rows == count($letters) - 1) {
                            $money += $line[$lineNum - 4][$rows];
                        }
                    }
                }
                if (count($line) >= 9) {
                    $moneyCell = "F" . $countLineItemNum;
                    $moneyBigCell = "B" . $countLineItemNum;
                } else {
                    $moneyCell = "F13";
                    $moneyBigCell = "B13";
                }
                $data[$sheetName][$moneyCell] = $money;
                $data[$sheetName][$moneyBigCell] = venus_money_amount_in_words($money);
                $data[$sheetName]["countLine"] = count($line);
                unset($line);
            }
        }
        $fileName = $this->excel_rec_inv_format($data, "010");
        return array(true, array("repName" => $repName, "fileName" => $fileName), "");
    }

    /**
     * @param $excelData
     * @param $repName
     * @return array
     * 出仓单格式
     */
    private
    function export_invoice($excelData, $repName)
    {
        $data = array();

        $letters = array(
            "A", "B", "C", "D", "E", "F"
        );
        foreach ($excelData as $receiver => $igsData) {
            foreach ($igsData as $user => $typeGoods) {
                $line = array();
                foreach ($typeGoods as $typeName => $goodDataArr) {
                    foreach ($goodDataArr as $goodKey => $goodDataArray) {
                        foreach ($goodDataArray as $goodData) {
                            foreach ($goodData as $bprice => $goodDatum) {
                                $goodsToLine = array(count($line) + 1, $goodKey, $goodDatum['count'], $goodDatum['unit'], $bprice, $goodDatum['money']);
                                $line[] = $goodsToLine;
                            }
                        }
                    }
                }
                $money = 0;
                $sheetName = $receiver;
                if (strstr($receiver, "|") == true && strstr($receiver, "(") == true) {
                    $sheetNameArrs = explode("|", $receiver);
                    $sheetNameArr = explode("(", $sheetNameArrs[1]);
                    $subject = $sheetNameArrs[0] . "(" . $sheetNameArr[1];
                } elseif (strstr($receiver, "|") == true) {
                    $sheetNameArrs = explode("|", $receiver);
                    $subject = $sheetNameArrs[0];
                } else {
                    $subject = $receiver;
                }

                $data[$sheetName] = array(
                    "A1" => $repName,
                    "B2" => $typeName,
                    "C2" => date("Y年m月d日", time()),
                    "F2" => $subject,
                );
                $countLineItemNum = count($line) + 4;
                for ($lineNum = 4; $lineNum < $countLineItemNum; $lineNum++) {
                    for ($rows = 0; $rows < count($letters); $rows++) {
                        $num = $letters[$rows] . $lineNum;
                        $data[$sheetName][$num] = $line[$lineNum - 4][$rows];
                        if ($rows == count($letters) - 1) {
                            $money += $line[$lineNum - 4][$rows];
                        }
                    }
                }
                if (count($line) >= 9) {
                    $moneyCell = "F" . $countLineItemNum;
                    $moneyBigCell = "B" . $countLineItemNum;
                    $userCell = "G" . ($countLineItemNum + 1);
                } else {
                    $moneyCell = "F13";
                    $moneyBigCell = "B13";
                    $userCell = "G14";
                }
                $data[$sheetName][$moneyCell] = $money;
                $data[$sheetName][$moneyBigCell] = venus_money_amount_in_words($money);
                $data[$sheetName][$userCell] = $user;
                $data[$sheetName]["countLine"] = count($line);
            }
        }

        $fileName = $this->excel_rec_inv_format($data, "020");
        return array(true, array("repName" => $repName, "fileName" => $fileName), "");
    }

    /**
     * @param $data
     * @param $typeName
     * @return string
     * 创建出仓单入仓单的模板
     */
    private
    function excel_rec_inv_format($data, $typeName)
    {
        $template = C("FILE_TPLS") . $typeName . ".xlsx";
        $saveDir = C("FILE_SAVE_PATH") . $typeName;

        $fileName = md5(json_encode($data)) . ".xlsx";
        vendor('PHPExcel.class');
        vendor('PHPExcel.IOFactory');
        vendor('PHPExcel.Writer.Excel2007');
        vendor("PHPExcel.Reader.Excel2007");
        $objReader = new \PHPExcel_Reader_Excel2007();
        $objPHPExcel = $objReader->load($template);    //加载excel文件,设置模板

        $templateSheet = $objPHPExcel->getSheet(0);


        foreach ($data as $sheetName => $list) {
            $excelSheet = $templateSheet->copy();

            $excelSheet->setTitle($sheetName);
            //创建新的工作表
            $sheet = $objPHPExcel->addSheet($excelSheet);
            $addLine = $list['countLine'] - 9;
            if ($addLine > 0) $sheet->insertNewRowBefore(4, $addLine);   //在行4前添加n行
            $endLine = $list['countLine'] + 5;
            unset($list['countLine']);
            $sheet->getStyle("A1:F{$endLine}")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//水平方向中间居中
            $sheet->getStyle("A1:F{$endLine}")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);//垂直方向上中间居中
            $line = 3;
            foreach ($list as $index => $value) {
                $sheet->setCellValue("$index", $value);
            }
        }
        //移除多余的工作表
        $objPHPExcel->removeSheetByIndex(0);
        //设置保存文件名字

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        if (!file_exists($saveDir)) {
            mkdir("$saveDir");
        }
        $objWriter->save($saveDir . "/" . $fileName);
        return $fileName;
    }
}