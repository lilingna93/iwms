<?php
/**
 * Created by PhpStorm.
 * User: lilingna
 * Date: 2018/7/17
 * Time: 14:13
 */

namespace Wms\Service;


use Common\Service\ExcelService;
use Common\Service\PassportService;
use function Couchbase\basicEncoderV1;
use http\Exception;
use Wms\Dao\GoodsbatchDao;
use Wms\Dao\GoodsDao;
use Wms\Dao\GoodstoredDao;
use Wms\Dao\PositionDao;
use Wms\Dao\ReceiptDao;
use Wms\Dao\SkuDao;
use Wms\Dao\SpuDao;
use Wms\Dao\WorkerDao;
use Wms\Dao\IgoodsDao;
use Wms\Dao\IgoodsentDao;
use Wms\Dao\InvoiceDao;
use Wms\Dao\OrderDao;
use Wms\Dao\OrdergoodsDao;

class ReceiptService
{

    static private $RECEIPT_STATUS_CREATE = "1";//入仓单创建状态
    static private $RECEIPT_STATUS_INSPECTION = "2";//inspection入仓单验货状态
    static private $RECEIPT_STATUS_FINISH = "3";//入仓单完成状态
    static private $RECEIPT_STATUS_CANCEL = "4";//入仓单取消状态

    static private $GOODSBATCH_STATUS_CREATE = "1";//货品批次创建状态
    static private $GOODSBATCH_STATUS_INSPECTION = "2";//货品批次验货状态
    static private $GOODSBATCH_STATUS_PUTAWAY = "3";//Putaway货品批次上架状态
    static private $GOODSBATCH_STATUS_FINISH = "4";//货品批次使用完状态


    private $RECEIPT_ALLOW_UPDATE;
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
        $this->RECEIPT_ALLOW_UPDATE = array(
            self::$RECEIPT_STATUS_CREATE,
            self::$RECEIPT_STATUS_INSPECTION,
        );
        $this->worRname = $workerData["wor_rname"];//人员名称
        $this->warAddress = $workerData["war_address"];//仓库地址
        $this->warPostal = $workerData["war_postal"];//仓库邮编
        $this->worPhone = $workerData["wor_phone"];//手机号
    }


    /**
     * @return array|bool
     * 创建入仓单／获取sku
     */
    public function receipt_get_sku()
    {
        $warCode = $this->warCode;
        $skuModel = SkuDao::getInstance($warCode);

        if (empty($_POST['data']['sku'])) {
            $message = "sku";
            venus_throw_exception(1, $message);
            return false;
        } else {
            $sku = trim($_POST['data']['sku']);
            $type = substr($sku, 0, 2);
            $data = array();
            if ($type == "SK") {
                $querySkuData = $skuModel->queryByCode($sku);
                $spuData = array(
                    "skName" => $querySkuData['spu_name'],
                    "skCode" => $querySkuData['sku_code'],
                    "skNorm" => $querySkuData['sku_norm'],
                    "skUnit" => $querySkuData['sku_unit'],
                    "spCode" => $querySkuData['spu_code'],
                    "spCount" => $querySkuData['spu_count'],
                    "spUnit" => $querySkuData['spu_unit'],
                    "spCunit" => $querySkuData['spu_cunit'],
                    'supCode' => $querySkuData['sup_code'],
                    "mark" => $querySkuData['spu_mark']
                );
                $data['list'][] = $spuData;
            } else {
                $spName = trim(str_replace("'", "", $sku));
                if (!empty($spName) && preg_match("/^[a-z]/i", $spName)) {
                    $cond['abname'] = $spName;
                }
                if (!empty($spName) && !preg_match("/^[a-z]/i", $spName)) {//SPU名称
                    $cond["%name%"] = $spName;
                }
                $cond['spustatus'] = 1;
                $querySkuDataList = $skuModel->queryListByCondition($cond);

                foreach ($querySkuDataList as $key => $value) {
                    $spuData = array(
                        "skName" => $value['spu_name'],
                        "skCode" => $value['sku_code'],
                        "skNorm" => $value['sku_norm'],
                        "skUnit" => $value['sku_unit'],
                        "spCode" => $value['spu_code'],
                        "spCount" => $value['spu_count'],
                        "spUnit" => $value['spu_unit'],
                        "spCunit" => $value['spu_cunit'],
                        "spMark" => $value['spu_mark'],
                        'supCode' => $value['sup_code'],
                        "mark" => $value['spu_mark']
                    );
                    $data['list'][] = $spuData;
                }
            }
            $success = true;
            $message = '';
            return array($success, $data, $message);
        }
    }


//    /**
//     * @param $param "isFast"是否快速入仓; "list"货品列表; "mark"订单备注信息;"ecode"采购单编号
//     * @return array|bool
//     * 创建入仓单/创建入仓预报单
//     */
//    public function receipt_create($param)
//    {
//        if (!isset($param)) {
//            $param = $_POST;
//        }
//
//        $list = $param['data']['list'];
//        $mark = $param['data']['mark'];
//        $ecode = $param['data']['ecode'];
//        $warCode = $this->warCode;
//        $worCode = $this->worcode;
//
//        $data = array();
//
//        $recModel = ReceiptDao::getInstance($warCode);
//        $goodsModel = GoodsDao::getInstance($warCode);
//        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
//        $goodstoredModel = GoodstoredDao::getInstance($warCode);
//        $positionModel = PositionDao::getInstance($warCode);
//        $spuModel = SpuDao::getInstance($warCode);
//        $skuModel = SkuDao::getInstance($warCode);
//
//        venus_db_starttrans();
//
//        //创建入仓单
//        $recStatus = self::$RECEIPT_STATUS_FINISH;
//        $addRecData = array(
//            "worcode" => $worCode,
//            "mark" => $mark,
//            "status" => $recStatus
//        );
//        if (isset($ecode) && !empty($ecode)) {
//            $issetOrder = $recModel->queryListByCondition(array("ecode" => $ecode));
//            if ($issetOrder) {
//                venus_db_rollback();
//                $success = true;
//                $message = '';
//                return array($success, $data, $message);
//            } else {
//                $addRecData['ecode'] = $ecode;
//            }
//        }
//        $recCode = $recModel->insert($addRecData);
//
//
//        $posCode = $positionModel->queryByWarCode($warCode)['pos_code'];
//
//        //创建入仓单清单
//        foreach ($list as $key => $value) {
//
//            if (empty($value['skCode'])) {
//                venus_throw_exception(1, "sku编号不能为空");
//                return false;
//            }
////            if (empty($value['skCount'])) {
////                venus_throw_exception(1, "sku数量不能为空");
////                return false;
////            }
//
//            if (empty($value['spCode'])) {
//                venus_throw_exception(1, "spu编号不能为空");
//                return false;
//            }
//            if (empty($value['spBprice'])) {
//                venus_throw_exception(1, "spu价格不能为空");
//                return false;
//            }
//            if (empty($value['spCunit'])) {
//                venus_throw_exception(1, "spu最小计量单位不能为空");
//                return false;
//            }
//            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value['spBprice'])) {
//                venus_throw_exception(4, "spu价格格式不正确");
//                return false;
//            }
//            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value['skCount'])) {
//                venus_throw_exception(4, "sku数量格式不正确");
//                return false;
//            } else {
//                if (!empty($value['spCunit']) && $value['spCunit'] == 1) {
//                    if (floor($value['skCount']) != $value['skCount']) {
//                        venus_throw_exception(4, "sku数量格式不正确");
//                        return false;
//                    }
//                }
//            }
//            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value['count'])) {
//                venus_throw_exception(4, "spu总数量格式不正确");
//                return false;
//            } else {
//                if (!empty($value['spCunit']) && $value['spCunit'] == 1) {
//                    if (floor($value['count']) != $value['count']) {
//                        venus_throw_exception(4, "spu总数量格式不正确");
//                        return false;
//                    }
//                }
//            }
//            //采购单针对主仓spu插入自己仓库
//            if (isset($value['msg'])) {
//                $dictService = new SkudictService();
//                $addSkuAndSpuData = $value['msg'];
//                $addSkuAndSpuData['supCode'] = $value['supCode'];
//                $addSpuAndSku = $dictService->valid_and_create_skudict($addSkuAndSpuData);
//                if (!$addSpuAndSku) {
//                    venus_db_rollback();
//                    $message = '创建商品数据';
//                    venus_throw_exception(2, $message);
//                    return false;
//                }
//            }
//            $addData['skucode'] = trim($value['skCode']);
//            $addData['skucount'] = $value['skCount'];
//            $addData['spucode'] = $value['spCode'];
//            $addData['count'] = $value['count'];
//            $addData['bprice'] = $value['spBprice'];
//            $addData['supcode'] = $value['supCode'];
//            if (!empty($isFast) && 1 == $isFast) {
//                $addData['status'] = self::$GOODSBATCH_STATUS_PUTAWAY;
//            } else {
//                $addData['status'] = self::$GOODSBATCH_STATUS_CREATE;
//            }
//            $addData['reccode'] = $recCode;
//            $gbcode = $goodsbatchModel->insert($addData);
//            if (!$gbcode) {
//                venus_db_rollback();
//                $message = '添加入仓货品清单';
//                venus_throw_exception(2, $message);
//                return false;
//            }
//            $issetGoods = $goodsModel->queryBySpuCode($value['spCode']);
//            if ($issetGoods) {
//                $goodsCode = $issetGoods['goods_code'];
//                $init = $issetGoods['goods_init'] + $value['count'];
//                $count = $issetGoods['goods_count'] + $value['count'];
//                $goodsRes = $goodsModel->updateCountAndInitByCode($goodsCode, $init, $count);
//            } else {
//                $goodsAddData = array(
//                    'init' => $value['count'],
//                    'count' => $value['count'],
//                    'spucode' => $value['spCode']
//                );
//                $goodsRes = $goodsModel->insert($goodsAddData);
//            }
//
//            $goodstoredAddData = array(
//                'init' => $value['count'],
//                'count' => $value['count'],
//                'bprice' => $value['spBprice'],
//                'gbcode' => $gbcode,
//                'poscode' => $posCode,
//                'spucode' => $value['spCode'],
//                'supcode' => $value['supCode']
//            );
//            $goodstoredAddData['skucode'] = trim($value['skCode']);
//            $goodstoredAddData['skucount'] = $value['skCount'];
//            $goodstoredAddData['skuinit'] = $value['skCount'];
//            $addGoodstoredRes = $goodstoredModel->insert($goodstoredAddData);
//            if (!$goodsRes || !$addGoodstoredRes) {
//                venus_db_rollback();
//                $message = '存入库存';
//                venus_throw_exception(2, $message);
//                return false;
//            }
//        }
//
//        $uptRecFinish = $recModel->updateFinishTimeByCode($recCode);
//        if (empty($uptRecFinish)) {
//            venus_db_rollback();
//            $message = '完成入仓单失败';
//            venus_throw_exception(2, $message);
//            return false;
//        }
//        venus_db_commit();
//        $success = true;
//        $message = '';
//        return array($success, $data, $message);
//    }

    /**
     * @param $param "list"货品列表; "mark"订单备注信息;"ecode"采购单编号
     * @return array|bool
     * 创建入仓单/创建入仓预报单
     */
    public function receipt_create($param)
    {
        if (!isset($param)) {
            $param = $_POST;
            $emptySku = 2;
        } else {
            $emptySku = 1;
        }

        $list = $param['data']['list'];
        $mark = $param['data']['mark'];
        $ecode = $param['data']['ecode'];
        $room = $param['data']["room"];//20190311新增
        $ctime = $param['data']["ctime"];//20190509新增
        $warCode = $this->warCode;
        $worCode = $this->worcode;

        $sname = array("war_code" => $warCode, "wor_code" => $worCode, "list" => $list);
        $recUniqeKey = md5(json_encode($sname));
        $recUniqeData = S($recUniqeKey);
        if (false == $recUniqeData) {
            $recUniqeStr = json_encode($sname);
            S($recUniqeKey, $recUniqeStr, 600);
        } else {
            return array(false, array(), "已有相同货品列表的入仓单");
        }

        if (empty($param['data']['type'])) {
            $type = 1;
        } else {
            $type = $param['data']['type'];
        }
        venus_db_starttrans();
        $data = array(
            "type" => $type,
            "warCode" => $warCode,
            "worCode" => $worCode,
            "mark" => $mark,
            "ecode" => $ecode,
            "room" => $room,
            "ctime" => $ctime
        );

        if ($type == 2 && (empty($ecode) || $ecode == null || $ecode == '')) {
            return array(false, array(), "订单编号不能为空");
        }
        foreach ($list as $value) {

            if (empty($value['skCode'])) return array(false, array(), "sku编号不能为空");
            if ($emptySku == 2 && empty($value['skCount'])) return array(false, array(), "sku数量不能为空");
            if (empty($value['spBprice'])) return array(false, array(), "采购价格不能为空");
            if (empty($value['supCode'])) return array(false, array(), "供应商编号不能为空");

            if (empty($value['spCunit'])) return array(false, array(), "spu最小计量单位不能为空");
            $listData = array(
                "skucode" => trim($value['skCode']),
                "skucount" => $value['skCount'],
                "bprice" => $value['spBprice'],
                "supcode" => $value["supCode"]
            );

            //采购单针对主仓spu插入自己仓库
            if (isset($value['msg'])) {
                $dictService = new SkudictService();
                $addSkuAndSpuData = $value['msg'];
                $addSkuAndSpuData['supCode'] = $value['supCode'];
                $addSkuAndSpuData['supName'] = $value['supName'];
                $addSkuAndSpuData['supPhone'] = $value['supPhone'];
                $addSkuAndSpuData['supManager'] = $value['supManager'];
                $addSpuAndSku = $dictService->valid_and_create_skudict($addSkuAndSpuData);
                if (!$addSpuAndSku) {
                    venus_db_rollback();
                    return array(false, array(), "创建商品数据");
                }
            }
            $data['list'][] = $listData;
        }
        $warehouseService = new WarehouseService();
        $createRecRes = $warehouseService->create_receipt($data);
        $oosFilePath = C("FILE_SAVE_PATH") . "logs/" . date("Y-m-d", time()) . ".log";
        $fileData = $param['data'];
        $fileData['warCode'] = $warCode;
        $fileData['worCode'] = $worCode;
        $fileData['orderCode'] = $ecode;
        $fileData['recCode'] = $createRecRes[1][0];
        $fileContent = "时间：" . date("Y-m-d H:i:s", time()) . PHP_EOL;
        foreach ($fileData as $keyName => $fileDatum) {
            if ($keyName == "list") {
                $fileContent .= $keyName . ":" . json_encode($fileDatum) . PHP_EOL;
            } else {
                $fileContent .= $keyName . ":" . $fileDatum . PHP_EOL;
            }

        }
        $fileContent .= "" . PHP_EOL . PHP_EOL;
        file_put_contents($oosFilePath, $fileContent, FILE_APPEND);
        if ($createRecRes[0] == true) {
            venus_db_commit();
            return $createRecRes;
        } else {
            venus_db_rollback();
            S($recUniqeKey, '');
            return $createRecRes;
        }

    }


    /**
     * @return array
     * 入仓单管理/入仓单管理列表
     */
    public
    function receipt_search()
    {
        $warCode = $this->warCode;

        $stime = $_POST['data']['stime'];//开始时间
        $etime = $_POST['data']['etime'];//结束时间
        $status = $_POST['data']['status'];//状态
        $recCode = $_POST['data']['code'];//入仓单单号
        $pageCurrent = $_POST['data']['pageCurrent'];//当前页数
        $clause = array();
        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }
        if (!empty($stime)) {
            $clause['sctime'] = $stime;
        }
        if (!empty($etime)) {
            $clause['ectime'] = $etime;
        }


        if (!empty($status)) $clause['status'] = $status;
        if (!empty($recCode)) $clause['code'] = $recCode;

        $recModel = ReceiptDao::getInstance($warCode);
        $workerModel = WorkerDao::getInstance($warCode);

        $totalCount = $recModel->queryCountByCondition($clause);
        $pageLimit = pageLimit($totalCount, $pageCurrent);
        $queryData = $recModel->queryListByCondition($clause, $pageLimit['page'], $pageLimit['pSize']);
        $data = array(
            "pageCurrent" => $pageCurrent,
            "pageSize" => $pageLimit['pageSize'],
            "totalCount" => $totalCount,
        );
        foreach ($queryData as $key => $value) {
            $data['list'][] = array(
                "recCode" => $value['rec_code'],
                "recCtime" => $value['rec_ctime'],
                "recUcode" => $value['wor_code'],
                "recUname" => $workerModel->queryByCode($value['wor_code'])['wor_name'],
                "recMark" => $value['rec_mark'],
                "recType" => $value['rec_type'],
                "recStatus" => $value['rec_status'],
                "recStatMsg" => venus_receipt_status_desc($value['rec_status']),
            );
        }

        $success = true;
        $message = '';
        return array($success, $data, $message);
    }


    /**
     * @return array|bool
     * 入仓单管理/入仓单管理之修改(1)入仓单详情
     */
    public
    function receipt_detail()
    {
        $warCode = $this->warCode;

        $pageCurrent = $_POST['data']['pageCurrent'];//当前页数
        if (empty($pageCurrent)) $pageCurrent = 0;
        $recCode = $_POST['data']['recCode'];
        if (empty($recCode)) {
            $message = "入仓单编号不能为空";
            venus_throw_exception(1, $message);
            return false;
        } else {

            $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
            $goodstoredModel = GoodstoredDao::getInstance($warCode);
            $receiptModel = ReceiptDao::getInstance($warCode);

            $totalCount = $goodsbatchModel->queryCountByRecCode($recCode);
            $pageLimit = pageLimit($totalCount, $pageCurrent);
            $queryGbList = $goodsbatchModel->queryListByRecCode($recCode, $pageLimit['page'], $pageLimit['pSize']);
            $recData = $receiptModel->queryByCode($recCode);
            $type = $recData['rec_type'];
            $data = array(
                "pageCurrent" => $pageCurrent,
                "pageSize" => $pageLimit['pageSize'],
                "totalCount" => $totalCount,
            );
            foreach ($queryGbList as $value) {

                $supCode = $value['sup_code'];
                $gsData = $goodstoredModel->queryByGbCode($value['gb_code']);
                $gsSkuInit = $gsData['sku_init'];
                $gsSkuCount = $gsData['sku_count'];
                $igsSkuCount = floatval(bcsub($gsSkuInit, $gsSkuCount, 2));
                //小仓入仓科贸供应商不可修改价格/非小仓入仓不可修改价格
                if ($type == 2) {
                    if ($supCode != "SU00000000000001") {
                        $isUptBprice = true;
                    } else {
                        $isUptBprice = false;
                    }
                    $isUptCount = false;
                } else {
                    $isUptBprice = true;
                    if ($gsSkuCount == 0) {
                        $isUptCount = false;
                    } else {
                        $isUptCount = true;
                    }

                }

                $data['list'][] = array(
                    "gbCode" => $value['gb_code'],
                    "skName" => $value['spu_name'],
                    "skCode" => $value['sku_code'],
                    "skNorm" => $value['sku_norm'],
                    "skCount" => $value['sku_count'],
                    "skUnit" => $value['sku_unit'],
                    "spBprice" => $value['gb_bprice'],//sku总价
                    "spCode" => $value['spu_code'],
                    "spCount" => $value['gb_count'],
                    "spUnit" => $value['spu_unit'],
                    "spCunit" => $value['spu_cunit'],
                    "posCode" => $goodstoredModel->queryPoscodeByCode($value['gb_code']),
                    "skuCount" => $igsSkuCount,//已出仓数量
                    "type" => $type,
                    "isUptBprice" => $isUptBprice,
                    "isUptCount" => $isUptCount,
                );
            }

            $success = true;
            $message = '';
            return array($success, $data, $message);
        }
    }


    /**
     * @return array|bool
     * 入仓单管理/入仓单管理之修改(2)修改入仓单数量
     */
    public
    function receipt_goods_count_update()
    {

        $warCode = $this->warCode;

        if (empty($_POST['data']['recCode'])) venus_throw_exception(1, "入仓单编号不能为空");
        if (empty($_POST['data']['gbCode'])) venus_throw_exception(1, "入仓单货品编号不能为空");
        if (empty($_POST['data']['skCount'])) venus_throw_exception(1, "入仓单货品sku数量不能为空");
        if (empty($_POST['data']['spBprice'])) venus_throw_exception(1, "入仓单货品spu价格不能为空");
        if (empty($_POST['data']['spCunit'])) venus_throw_exception(1, "入仓单货品spu最小计量单位不能为空");

        $recCode = $_POST['data']['recCode'];
        $gbCode = $_POST['data']['gbCode'];
        $skCount = $_POST['data']['skCount'];
        $spBprice = $_POST['data']['spBprice'];
        $count = $_POST['data']['count'];
        $spCunit = $_POST['data']['spCunit'];
        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $spBprice)) {
            venus_throw_exception(4, "spu价格格式不正确");
            return false;
        }

        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $skCount)) {
            venus_throw_exception(4, "sku数量格式不正确");
            return false;
        } else {
            if (!empty($spCunit) && $spCunit == 1) {
                if (floor($skCount) != $skCount) {
                    venus_throw_exception(4, "sku数量格式不正确");
                    return false;
                }
            }
        }
        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $count)) {
            venus_throw_exception(4, "spu总数量格式不正确");
            return false;
        } else {
            if (!empty($spCunit) && $spCunit == 1) {
                if (floor($count) != $count) {
                    venus_throw_exception(4, "spu总数量格式不正确");
                    return false;
                }
            }
        }

        $recModel = ReceiptDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);


        $isUpt = $recModel->queryByCode($recCode)['rec_status'];
        if (in_array($isUpt, $this->RECEIPT_ALLOW_UPDATE)) {
            $gbRes = $goodsbatchModel->updateByCode($gbCode, $count, $spBprice, $skCount);

            if (!$gbRes) {
                $message = "修改失败";
                venus_throw_exception(2, $message);
                return false;
            } else {
//                $data['success'] = true;
                $success = true;
                $message = '';
                return array($success, array(), $message);
            }

        } else {
            venus_throw_exception(2001, '');
            return false;
        }

    }


    /**
     * @return array|bool
     *  入仓单管理之修改（3）增加入仓单货品
     */
    public
    function receipt_goods_create()
    {

        $list = $_POST['data']['list'];

        $warCode = $this->warCode;

        $data = array();
        $recCode = $_POST['data']['recCode'];
        if (empty($recCode)) {
            venus_throw_exception(1, "入仓单编号不能为空");
        }

        $recModel = ReceiptDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);

        $isUpt = $recModel->queryByCode($recCode)['rec_status'];
        if (in_array($isUpt, $this->RECEIPT_ALLOW_UPDATE)) {
            venus_db_starttrans();
            //创建入仓单清单
            foreach ($list as $key => $value) {
                if (empty($value['skCode'])) {
                    venus_throw_exception(1, "sku编号不能为空");
                    return false;
                }
//                if (empty($value['skCount'])) {
//                    venus_throw_exception(1, "sku数量不能为空");
//                    return false;
//                }

                if (empty($value['spCode'])) {
                    venus_throw_exception(1, "spu编号不能为空");
                    return false;
                }
                if (empty($value['spBprice'])) {
                    venus_throw_exception(1, "入仓单货品spu价格不能为空");
                    return false;
                }

                if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value['spBprice'])) {
                    venus_throw_exception(4, "入仓单货品spu价格格式不正确");
                    return false;
                }

//                if (empty($value['count'])) {
//                    venus_throw_exception(1, "spu总数量不能为空");
//                    return false;
//                }
                if (empty($value['spCunit'])) {
                    venus_throw_exception(1, "spu最小计量单位不能为空");
                    return false;
                }

                if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value['skCount'])) {
                    venus_throw_exception(4, "sku数量格式不正确");
                    return false;
                } else {
                    if (!empty($value['spCunit']) && $value['spCunit'] == 1) {
                        if (floor($value['skCount']) != $value['skCount']) {
                            venus_throw_exception(4, "sku数量格式不正确");
                            return false;
                        }
                    }
                }
                if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value['count'])) {
                    venus_throw_exception(4, "spu总数量格式不正确");
                    return false;
                } else {
                    if (!empty($value['spCunit']) && $value['spCunit'] == 1) {
                        if (floor($value['count']) != $value['count']) {
                            venus_throw_exception(4, "spu总数量格式不正确");
                            return false;
                        }
                    }
                }

                $addData = array(
                    'skucode' => $value['skCode'],
                    'skucount' => $value['skCount'],
                    'spucode' => $value['spCode'],
                    'count' => $value['count'],
                    'bprice' => $value['spBprice'],
                    'supcode' => $value['supCode'],
                    'status' => self::$GOODSBATCH_STATUS_CREATE,
                    'reccode' => $recCode,
                );

                $gbCode = $goodsbatchModel->insert($addData);
                if (!$gbCode) {
                    venus_db_rollback();
                    $message = '创建入仓单清单';
                    venus_throw_exception(2, $message);
                    return false;
                }
            }

            venus_db_commit();
            $success = true;
            $message = '';
            return array($success, $data, $message);
        } else {
            venus_throw_exception(2002, '');
            return false;
        }

    }


    /**
     * @return array|bool
     * 入仓单管理之修改（4）删除入仓单货品
     */
    public
    function receipt_goods_delete()
    {
        $warCode = $this->warCode;


        if (empty($_POST['data']['recCode'])) {
            venus_throw_exception(1, "入仓单编号不能为空");
            return false;
        }
        if (empty($_POST['data']['gbCode'])) {
            venus_throw_exception(1, "入仓单货品编号不能为空");
            return false;
        }
        $recCode = $_POST['data']['recCode'];
        $gbCode = $_POST['data']['gbCode'];
        $data = array();

        $recModel = ReceiptDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);

        $isUpt = $recModel->queryByCode($recCode)['rec_status'];
        if (in_array($isUpt, $this->RECEIPT_ALLOW_UPDATE)) {
            $gbUptRes = $goodsbatchModel->deleteByCode($gbCode, $recCode);
            if (!$gbUptRes) {
                venus_throw_exception(2, '删除入仓单货品');
                return false;
            } else {
                $success = true;
                $message = '';
                return array($success, $data, $message);
            }
        } else {
            venus_throw_exception(2003, '');
            return false;
        }

    }


    /**
     * @return array|bool
     * 入仓单管理/入仓单管理之删除
     */
    public
    function receipt_delete()
    {
        $warCode = $this->warCode;

        if (empty($_POST['data']['recCode'])) {
            venus_throw_exception(1, "入仓单编号不能为空");
            return false;
        }
        $data = array();
        $recCode = $_POST['data']['recCode'];

        $recModel = ReceiptDao::getInstance($warCode);

        $isUpt = $recModel->queryByCode($recCode)['rec_status'];
        if (in_array($isUpt, $this->RECEIPT_ALLOW_UPDATE)) {
            $recStatus = self::$RECEIPT_STATUS_CANCEL;
            $uptRec = $recModel->updateStatusByCode($recCode, $recStatus);
            if (!$uptRec) {
                $message = '删除入仓单';
                venus_throw_exception(2, $message);
                return false;
            } else {
                $success = true;
                $message = '';
                return array($success, $data, $message);
            }
        } else {
            venus_throw_exception(2003, '');
            return false;
        }
    }

    /**
     * @return array|bool
     * 快进快出
     */
//    public function receipt_inv_finish()
//    {
//        $type = 1;//pc端，手工记账
//        $warCode = $this->warCode;
//        $worCode = $this->worcode;
//        $receiver = $this->worRname;//客户名称
//        $phone = $this->worPhone;//客户手机号
//        $address = $this->warAddress;//客户地址
//        $postal = $this->warPostal;//客户邮编
//        $ctime = date("Y-m-d", time()) . " 06:00:00";
//
//        $goodsList = $_POST['data']['list'];
//        $room = $_POST['data']['room'];
//
//        $ordergoodsModel = OrdergoodsDao::getInstance($warCode);
//        $orderModel = OrderDao::getInstance($warCode);
//        $recModel = ReceiptDao::getInstance($warCode);
//        $goodsModel = GoodsDao::getInstance($warCode);
//        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
//        $goodstoredModel = GoodstoredDao::getInstance($warCode);
//        $positionModel = PositionDao::getInstance($warCode);
//        $invModel = InvoiceDao::getInstance($warCode);
//        $igoodsModel = IgoodsDao::getInstance($warCode);
//        $igoodsentModel = IgoodsentDao::getInstance($warCode);
//        $spuModel = SpuDao::getInstance($warCode);
//        venus_db_starttrans();
//        $gsSpuDataArr = array();
//        $invSpuDataArr = array();
//
//        $recStatus = self::$RECEIPT_STATUS_FINISH;
//        $addRecData = array(
//            "worcode" => $worCode,
//            "status" => $recStatus
//        );
//        $recCode = $recModel->insert($addRecData);
//        //创建入仓单清单
//        foreach ($goodsList as $key => $value) {
//
//            if (empty($value['skCode'])) {
//                venus_throw_exception(1, "sku编号不能为空");
//                return false;
//            }
//            if (empty($value['skCount'])) {
//                venus_throw_exception(1, "sku数量不能为空");
//                return false;
//            }
//
//            if (empty($value['spCode'])) {
//                venus_throw_exception(1, "spu编号不能为空");
//                return false;
//            }
//            if (empty($value['supCode'])) {
//                venus_throw_exception(1, "供应商编号不能为空");
//                return false;
//            }
//            if (empty($value['spBprice'])) {
//                venus_throw_exception(1, "spu价格格式不能为空");
//                return false;
//            }
//            if (empty($value['spCunit'])) {
//                venus_throw_exception(1, "spu最小计量单位不能为空");
//                return false;
//            }
//            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value['spBprice'])) {
//                venus_throw_exception(4, "spu价格格式不正确");
//                return false;
//            }
//            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value['skCount'])) {
//                venus_throw_exception(4, "sku数量格式不正确");
//                return false;
//            } else {
//                if (!empty($value['spCunit']) && $value['spCunit'] == 1) {
//                    if (floor($value['skCount']) != $value['skCount']) {
//                        venus_throw_exception(4, "sku数量格式不正确");
//                        return false;
//                    }
//                }
//            }
//            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $value['count'])) {
//                venus_throw_exception(4, "spu总数量格式不正确");
//                return false;
//            } else {
//                if (!empty($value['spCunit']) && $value['spCunit'] == 1) {
//                    if (floor($value['count']) != $value['count']) {
//                        venus_throw_exception(4, "spu总数量格式不正确");
//                        return false;
//                    }
//                }
//            }
//            $addData['skucode'] = trim($value['skCode']);
//            $addData['skucount'] = $value['skCount'];
//            $addData['spucode'] = $value['spCode'];
//            $addData['count'] = $value['count'];
//            $addData['bprice'] = $value['spBprice'];
//            $addData['supcode'] = $value['supCode'];
//            $addData['status'] = self::$GOODSBATCH_STATUS_FINISH;
//            $addData['reccode'] = $recCode;
//            $gbcode = $goodsbatchModel->insert($addData);
//            $invSpuData[] = $addData;
//            if (!$gbcode) {
//                venus_db_rollback();
//                $message = '添加入仓货品清单';
//                venus_throw_exception(2, $message);
//                return false;
//            }
//            $issetGoods = $goodsModel->queryBySpuCode($value['spCode']);
//            if ($issetGoods) {
//                $goodsCode = $issetGoods['goods_code'];
//                $init = $issetGoods['goods_init'] + $value['count'];
//                $count = $issetGoods['goods_count'] + $value['count'];
//                $goodsRes = $goodsModel->updateCountAndInitByCode($goodsCode, $init, $count);
//            } else {
//                $goodsAddData = array(
//                    'init' => $value['count'],
//                    'count' => $value['count'],
//                    'spucode' => $value['spCode']
//                );
//                $goodsRes = $goodsModel->insert($goodsAddData);
//            }
//
//            $goodstoredAddData = array(
//                'init' => $value['count'],
//                'count' => $value['count'],
//                'bprice' => $value['spBprice'],
//                'gbcode' => $gbcode,
//                'spucode' => $value['spCode'],
//                'supcode' => $value['supCode']
//            );
//            $goodstoredAddData['skucode'] = trim($value['skCode']);
//            $goodstoredAddData['skucount'] = $value['skCount'];
//            $goodstoredAddData['skuinit'] = $value['skCount'];
//            $addGoodstoredRes = $goodstoredModel->insert($goodstoredAddData);
//            $gsSpuDataArr[$value['spCode']] = $goodstoredAddData;
//            $gsSpuDataArr[$value['spCode']]['gscode'] = $addGoodstoredRes;
//            if (!$goodsRes || !$addGoodstoredRes) {
//                venus_db_rollback();
//                $message = '存入库存';
//                venus_throw_exception(2, $message);
//                return false;
//            }
//
//        }
//
//        $uptRecFinish = $recModel->updateFinishTimeByCode($recCode);
//        if (empty($uptRecFinish)) {
//            venus_db_rollback();
//            $message = '完成入仓单失败';
//            venus_throw_exception(2, $message);
//            return false;
//        }
//
//        $igoodsDataList = array();
//        $invAddData = array(
//            "status" => 5,//出仓单状态
//            "receiver" => $receiver,//客户名称
//            "phone" => $phone,//客户手机号
//            "address" => $address,//客户地址
//            "postal" => $postal,//客户邮编
//            "type" => $type,//出仓单类型
//            "room" => $room,//餐厅编号
//            "worcode" => $worCode,//人员编号
//        );//出仓单新增数据
//        $invCode = $invModel->insert($invAddData);
//        if (!$invCode) {
//            venus_db_rollback();
//            $message = '创建出仓单失败';
//            venus_throw_exception(2, $message);
//            return false;
//        }
//        foreach ($invSpuData as $invSpuDatum) {
//            $spuData = $spuModel->queryByCode($invSpuDatum['spucode']);//获取spu商品相关信息
//            if (!empty($spuData['spu_sprice'])) {
//                $sprice = $spuData['spu_sprice'];//spu当前销售价
//            } else {
//                $sprice = 0;
//            }
//            if (!empty($spuData['pro_price'])) {
//                $pprice = $spuData['pro_price'];//spu利润价
//            } else {
//                $pprice = 0;
//            }
//            if (!empty($spuData['pro_percent'])) {
//                $percent = $spuData['pro_percent'];//spu利润率
//            } else {
//                $percent = 0;
//            }
//            $goodsData = $goodsModel->queryBySpuCode($invSpuDatum['spucode']);
//            $gsCode = $gsSpuDataArr[$invSpuDatum['spucode']]['gscode'];
//            $addIgoData = array(
//                "count" => $invSpuDatum['count'],//spu总数量
//                "spucode" => $invSpuDatum['spucode'],//spu编号
//                "sprice" => $sprice,//spu当前销售价
//                "pprice" => $pprice,//spu当前利润
//                "goodscode" => $goodsData['goods_code'],//库存编号
//                "percent" => $percent,//spu当前利润率
//                "skucode" => $invSpuDatum['skucode'],//sku编号
//                "skucount" => $invSpuDatum['skucount'],//sku数量
//                "invcode" => $invCode,//所属出仓单单号
//            );
//            $addIgoRes = $igoodsModel->insert($addIgoData);
//            $igoodsentData = array(
//                "count" => $invSpuDatum['count'],
//                "bprice" => $gsSpuDataArr[$invSpuDatum['spucode']]['bprice'],
//                "spucode" => $invSpuDatum['spucode'],
//                "gscode" => $gsCode,
//                "igocode" => $addIgoRes,
//                "skucode" => $invSpuDatum['skucode'],
//                "skucount" => floatval($invSpuDatum['skucount']),
//                "invcode" => $invCode,
//            );
//            $igoodsentCode = $igoodsentModel->insert($igoodsentData);
//            $gsSkuCount = $gsSpuDataArr[$invSpuDatum['spucode']]['skucount'];
//            $goodsoredCount = $gsSpuDataArr[$invSpuDatum['spucode']]['count'];
//            $uptGsSpuCount = $goodstoredModel->updateByCode($gsCode, $goodsoredCount - $igoodsentData['count']);//修改发货库存批次剩余数量
//            $uptGsSkuCount = $goodstoredModel->updateSkuCountByCode($gsCode, $gsSkuCount - $igoodsentData['skucount']);//减少发货库存批次sku数量
//            if (!$igoodsentCode) {
//                venus_db_rollback();
//                venus_throw_exception(2, "创建发货批次失败");
//                return false;
//            }
//
//            if (!$addIgoRes) {
//                venus_db_rollback();
//                $message = '创建出仓单货品失败';
//                venus_throw_exception(2, $message);
//                return false;
//            }
//
//            if (!$uptGsSpuCount || !$uptGsSkuCount) {
//                $spName = $spuModel->queryByCode($invSpuDatum['spucode'])['spu_name'];
//                venus_db_rollback();
//                venus_throw_exception(2, "修改" . $spName . "库存批次失败");
//                return false;
//            }
//            if (!$igoodsentCode) {
//                venus_db_rollback();
//                venus_throw_exception(2, "创建发货批次失败");
//                return false;
//            }
//            $goodsData = $goodsModel->queryBySpuCode($invSpuDatum['spucode']);
//            $goodsCount = $goodsData['goods_count'];
//            $igoCount = $addIgoData['count'];
//            $newCountGoods = $goodsCount - $igoCount;
//            $uptGoods = $goodsModel->updateCountByCode($goodsData['goods_code'], $goodsData['goods_count'], $newCountGoods);
//            if (!$uptGoods) {
//                venus_db_rollback();
//                venus_throw_exception(2, "修改库存失败");
//                return false;
//            }
//        }
//        venus_db_commit();
//        $success = true;
//        $message = '快进快出成功';
//        $data = array();
//        return array($success, $data, $message);
//    }

    /**
     * @return array
     * 快进快出
     */
    public function receipt_inv_finish($param)
    {
        if (!isset($param)) {
            $param = $_POST;
            $emptySku = 2;
            $recType = 1;
        } else {
            $emptySku = 1;
            $recType = $param['data']["recType"];
        }

        $warCode = $this->warCode;
        $worCode = $this->worcode;
        $receiver = $this->worRname;//客户名称
        $phone = $this->worPhone;//客户手机号
        $address = $this->warAddress;//客户地址
        $postal = $this->warPostal;//客户邮编
        $goodsList = $param['data']['listFast'];
        $room = $param['data']['room'];
        $mark = $param['data']["mark"];
        $ecode = $param['data']["ecode"];
        $ctime = $param['data']["ctime"];
        if (empty($recType)) return array(false, array(), "入仓单类型不能为空");

        $sname = array("war_code" => $warCode, "wor_code" => $worCode, "list" => $goodsList);
        $recUniqeKey = md5(json_encode($sname));
        $recUniqeData = S($recUniqeKey);
        if (false == $recUniqeData) {
            $recUniqeStr = json_encode($sname);
            S($recUniqeKey, $recUniqeStr, 600);
        } else {
            return array(false, array(), "已有相同货品列表的入仓单");
        }

        venus_db_starttrans();
        $data = array(
            "recType" => $recType,
            "invType" => 5,
            "warCode" => $warCode,
            "worCode" => $worCode,
            "mark" => $mark,
            "phone" => $phone,
            "address" => $address,
            "postal" => $postal,
            "receiver" => $receiver,
            "room" => $room,
            "ecode" => $ecode,
            "ctime" => empty($ctime) ? '' : $ctime
        );

        foreach ($goodsList as $value) {

            if (empty($value['skCode'])) return array(false, array(), "sku编号不能为空");
            if ($emptySku == 2 && empty($value['skCount'])) return array(false, array(), "sku数量不能为空");
            if (empty($value['spBprice'])) return array(false, array(), "采购价格不能为空");
            if (empty($value['supCode'])) return array(false, array(), "供应商编号不能为空");

            if (empty($value['spCunit'])) return array(false, array(), "spu最小计量单位不能为空");

            //采购单针对主仓spu插入自己仓库
            if (isset($value['msg'])) {
                $dictService = new SkudictService();
                $addSkuAndSpuData = $value['msg'];
                $addSkuAndSpuData['supCode'] = $value['supCode'];
                $addSkuAndSpuData['supName'] = $value['supName'];
                $addSkuAndSpuData['supPhone'] = $value['supPhone'];
                $addSkuAndSpuData['supManager'] = $value['supManager'];
                $addSpuAndSku = $dictService->valid_and_create_skudict($addSkuAndSpuData);
                if (!$addSpuAndSku) {
                    venus_db_rollback();
                    return array(false, array(), "创建商品数据");
                }
            }
            $listData = array(
                "skucode" => trim($value['skCode']),
                "skucount" => $value['skCount'],
                "bprice" => $value['spBprice'],
                "supcode" => $value["supCode"]
            );
            $data['list'][] = $listData;
        }
        $warehouseService = new WarehouseService();
        $createVirtualRes = $warehouseService->create_virtual($data);
        if ($createVirtualRes[0] == true) {
            venus_db_commit();
            return $createVirtualRes;
        } else {
            venus_db_rollback();
            return $createVirtualRes;
        }
    }

    /**
     * @return array|bool
     * 表格创建入仓单
     */
    public function rec_import()
    {
        $warCode = $this->warCode;//仓库编号
        //声明所需要用的Model及服务
        $excelService = ExcelService::getInstance();
        $spuModel = SpuDao::getInstance($warCode);
        $skuModel = SkuDao::getInstance($warCode);

        $fileContent = $excelService->upload("file");//导入文件

        $dicts = array(
            "A" => "skCode",//sku编号
            "D" => "skCount",//sku数量
            "E" => "skBprice",//sku采购价格
            "F" => "supCode",//sku供应商编号
        );
        $skuList = array();
        if (count($fileContent) == 1) {
            $recListData = array();
            $listSkuData = array();
            foreach ($fileContent as $sheetName => $list) {
                unset($list[0]);
                $skuList = array_merge($skuList, $list);
                foreach ($skuList as $line => $skuItem) {
                    $skuData = array();
                    foreach ($dicts as $col => $key) {
                        $skuData[$key] = isset($skuItem[$col]) ? $skuItem[$col] : "";
                    }
                    if (count(array_keys($skuItem, "")) == count($skuItem)) {
                        break;
                    } else {
                        $data = array(
                            "skCode" => $skuData['skCode'],
                            "bprice" => $skuData['skBprice'],
                            "supCode" => $skuData['supCode'],
                        );
                        $skuInfo = $skuModel->queryByCode($skuData['skCode']);
                        $spCunit = $skuInfo['spu_cunit'];
                        if ($spCunit == 1) {
                            $float = 0;
                        } elseif ($spCunit == "0.1") {
                            $float = 1;
                        } else {
                            $float = 2;
                        }
                        if (in_array($data, $listSkuData)) {
                            $skuLine = array_keys($listSkuData, $data);
                            $skuLine = $skuLine[0];
                            $recListData[$skuLine]['skCount'] = bcadd($recListData[$skuLine]['skCount'], $skuData['skCount'], $float);
                            $recListData[$skuLine]['count'] = bcmul($recListData[$skuLine]['skCount'], $skuInfo['spu_count'], $float);
                            continue;
                        } else {
                            $listSkuData[$line] = $data;
                            $recListData[$line] = $skuData;
                            $recListData[$line]['spCode'] = $skuInfo['spu_code'];
                            $recListData[$line]['spBprice'] = bcdiv($skuData['skBprice'], $skuInfo['spu_count'], 2);
                            $recListData[$line]['spCunit'] = $skuInfo['spu_cunit'];
                            $recListData[$line]['count'] = bcmul($skuData['skCount'], $skuInfo['spu_count'], $float);
                        }
                    }
                }
            }
            $param = array(
                "data" => array(
                    "list" => $recListData,
                    "mark" => "表格创建入仓单",
                )
            );
            return $this->receipt_create($param);

        } else {
            $success = false;
            $message = "表格不符合要求，有多个分表";
            return array($success, array(), $message);
        }
    }

    public function receipt_order($param)
    {
        $isSuccess = true;

        $warCode = $this->warCode;
        $worCode = $this->worcode;
        $receiver = $this->worRname;//客户名称
        $phone = $this->worPhone;//客户手机号
        $address = $this->warAddress;//客户地址
        $postal = $this->warPostal;//客户邮编
        $list = $param['data']['list'];
        $goodsList = $param['data']['listFast'];
        $room = $param['data']['room'];
        $mark = $param['data']["mark"];
        $ecode = $param['data']['ecode'];
        $recType = $param['data']["recType"];
        if (empty($recType)) return array(false, array(), "入仓单类型不能为空");
        $sname = array("war_code" => $warCode, "wor_code" => $worCode, "order" => $ecode);
        $recUniqeKey = md5(json_encode($sname));
        $recUniqeData = S($recUniqeKey);
        if (false == $recUniqeData) {
            $recUniqeStr = json_encode($sname);
            S($recUniqeKey, $recUniqeStr, 600);
        } else {
            return array(true, array(), "已有相同货品列表的入仓单");
        }
        $issetRec = ReceiptDao::getInstance($warCode)->queryByEcode($ecode);
        if (!empty($issetRec)) {
            $recCode = $issetRec['rec_code'];
            return array(true, array($recCode), "已经入仓");
        }
        venus_db_starttrans();
        $warehouseService = new WarehouseService();
        $errData = array();
//        if (!empty($list) && !empty($goodsList)) {
//            $list = $this->getListAndListFastData($list, $goodsList);
//        }
        if (!empty($list)) {
            $mark = $param['data']['mark'];
            $data = array(
                "type" => $recType,
                "warCode" => $warCode,
                "worCode" => $worCode,
                "mark" => $mark,
                "ecode" => $ecode,
                "room" => $room
            );

            if (isset($param['data']['ctime'])) {
            	$data['ctime']=$param['data']['ctime'];
            }

            foreach ($list as $value) {

                if (empty($value['skCode'])) return array(false, array(), "sku编号不能为空");
                if (empty($value['spBprice'])) return array(false, array(), "采购价格不能为空");
                if (empty($value['supCode'])) return array(false, array(), "供应商编号不能为空");
                if (empty($value['spCunit'])) return array(false, array(), "spu最小计量单位不能为空");
                $listData = array(
                    "skucode" => trim($value['skCode']),
                    "skucount" => $value['skCount'],
                    "bprice" => $value['spBprice'],
                    "supcode" => $value["supCode"]
                );

                //采购单针对主仓spu插入自己仓库
                if (isset($value['msg'])) {
                    $dictService = new SkudictService();
                    $addSkuAndSpuData = $value['msg'];
                    $addSkuAndSpuData['supCode'] = $value['supCode'];
                    $addSkuAndSpuData['supName'] = $value['supName'];
                    $addSkuAndSpuData['supPhone'] = $value['supPhone'];
                    $addSkuAndSpuData['supManager'] = $value['supManager'];
                    $addSpuAndSku = $dictService->valid_and_create_skudict($addSkuAndSpuData);
                    if (!$addSpuAndSku) {
                        venus_db_rollback();
                        return array(false, array(), "创建商品数据");
                    }
                }

                $data['list'][] = $listData;
            }
            $createRecRes = $warehouseService->create_receipt($data);
            $message = $createRecRes[2];
            $isSuccess = $isSuccess && $createRecRes[0];
            $errData[] = $createRecRes[1];
        }
        if (!empty($goodsList)) {
            $goodsData = array(
                "recType" => $recType,
                "invType" => 5,
                "warCode" => $warCode,
                "worCode" => $worCode,
                "mark" => $mark,
                "phone" => $phone,
                "address" => $address,
                "postal" => $postal,
                "receiver" => $receiver,
                "room" => $room,
                "ecode" => $ecode
            );
            foreach ($goodsList as $value) {

                if (empty($value['skCode'])) return array(false, array(), "sku编号不能为空");
                if (empty($value['spBprice'])) return array(false, array(), "采购价格不能为空");
                if (empty($value['supCode'])) return array(false, array(), "供应商编号不能为空");

                if (empty($value['spCunit'])) return array(false, array(), "spu最小计量单位不能为空");

                //采购单针对主仓spu插入自己仓库
                if (isset($value['msg'])) {
                    $dictService = new SkudictService();
                    $addSkuAndSpuData = $value['msg'];
                    $addSkuAndSpuData['supCode'] = $value['supCode'];
                    $addSkuAndSpuData['supName'] = $value['supName'];
                    $addSkuAndSpuData['supPhone'] = $value['supPhone'];
                    $addSkuAndSpuData['supManager'] = $value['supManager'];
                    $addSpuAndSku = $dictService->valid_and_create_skudict($addSkuAndSpuData);
                    if (!$addSpuAndSku) {
                        venus_db_rollback();
                        return array(false, array(), "创建商品数据");
                    }
                }
                $listData = array(
                    "skucode" => trim($value['skCode']),
                    "skucount" => $value['skCount'],
                    "bprice" => $value['spBprice'],
                    "supcode" => $value["supCode"]
                );
                $goodsData['list'][] = $listData;
            }
            $createVirtualRes = $warehouseService->create_virtual($goodsData);
            $message = $createVirtualRes[2];
            $isSuccess = $isSuccess && $createVirtualRes[0];
            $errData[] = $createVirtualRes[1];

        }
        //写入文件
        $oosFilePath = C("FILE_SAVE_PATH") . "logs/" . date("Y-m-d", time()) . ".log";
        $fileData = $param['data'];
        $fileData["处理状态"] = $isSuccess;
        $fileData["返回信息"] = $message;
        $fileContent = "时间：" . date("Y-m-d H:i:s", time()) . PHP_EOL;
        foreach ($fileData as $keyName => $fileDatum) {
            if ($keyName == "list" || $keyName == "listFast") {
                $fileContent .= $keyName . ":" . json_encode($fileDatum) . PHP_EOL;
            } else {
                $fileContent .= $keyName . ":" . $fileDatum . PHP_EOL;
            }

        }
        $fileContent .= "" . PHP_EOL . PHP_EOL;
        file_put_contents($oosFilePath, $fileContent, FILE_APPEND);
        if ($isSuccess) {
            venus_db_commit();
            return array(true, array(), $message);
        } else {
            venus_db_rollback();
            S($recUniqeKey, '');
            return array(false, array(), $message);
        }
    }

    /**
     * @param $param
     * @return array
     * 修改入仓单货品数量
     */
    public function update_receipt_goods($param)
    {
        $warCode = $this->warCode;
        if (!isset($param)) {
            $param = $_POST;
        }
        $gbCode = $param["data"]['code'];
        $count = $param["data"]['count'];

        if (empty($gbCode)) return array(false, array(), "批次编号不能为空");
        if ($count == null || $count < 0) return array(false, array(), "数量为空或者小于0");
        $goodstoredModel = GoodstoredDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $receiptModel = ReceiptDao::getInstance($warCode);

        $gbData = $goodsbatchModel->queryByCode($gbCode);
        $recCode = $gbData['rec_code'];
        $recData = $receiptModel->queryByCode($recCode);
        if ($recData['rec_type'] == 2) return array(false, array(), "此入仓单为小程序入仓，请在小程序进行相关操作");
        $lessCount = bcsub($gbData['gb_count'], $count, 2);
        $gsData = $goodstoredModel->queryByGbCode($gbCode);
        $gsCode = $gsData['gs_code'];
        if ($gsData['sku_count'] < $lessCount) {
            $success = false;
            $data = "";
            $igsSkuCount = floatval(bcsub($gsData['sku_init'], $gsData['sku_count'], 2));
            $message = "此批次修改数量最少为" . $igsSkuCount;
        } else {

            venus_db_starttrans();
            $isSuccess = true;

            $gsInit = $gsData['gs_init'];
            $gsSkuInit = $gsData['sku_init'];
            $gsCount = $gsData['gs_count'];
            $gsSkuCount = $gsData['sku_count'];

            if ($count == 0) {
                $isSuccess = $isSuccess && $goodsbatchModel->deleteByCode($gbCode, $recCode);
                $isSuccess = $isSuccess && $goodstoredModel->deleteByCode($gsCode);
                $issetGbList = $goodsbatchModel->queryCountByRecCode($recCode);
                if ($issetGbList == 0) $isSuccess = $isSuccess && $receiptModel->deleteByCode($recCode);
            } else {
                $gbSkuCount = $gbData['sku_count'];
                $gbCount = $gbData['gb_count'];
                $updateGbCount = bcsub($gbCount, $lessCount, 2);
                $updateGbSkuCount = bcsub($gbSkuCount, $lessCount, 2);
                $isSuccess = $isSuccess && $goodsbatchModel->updateCountAndSkuCountByCodeAndCurrentCount($gbCode, $gbCount, $updateGbCount, $updateGbSkuCount);
                if ($count != $updateGbCount) return array(false, array(), "计算失败");
                $updateGsInit = bcsub($gsInit, $lessCount, 2);
                $updateGsSkuInit = bcsub($gsSkuInit, $lessCount, 2);
                $updateGsCount = bcsub($gsCount, $lessCount, 2);
                $updateGsSkuCount = bcsub($gsSkuCount, $lessCount, 2);
                $isSuccess = $isSuccess && $goodstoredModel->updateInitAndSkuInitAndCountAndSkuCountByCode($gsCode, $updateGsInit, $updateGsCount, $updateGsSkuInit, $updateGsSkuCount);
            }
            $spuCode = $gbData['spu_code'];
            $goodsData = $goodsModel->queryBySpuCode($spuCode);
            $goodsCode = $goodsData['goods_code'];
            $goodsInit = $goodsData['goods_init'];
            $goodsCount = $goodsData['goods_count'];
            $updateGoodsInit = bcsub($goodsInit, $lessCount, 2);
            $updateGoodsCount = bcsub($goodsCount, $lessCount, 2);
            $isSuccess = $isSuccess && $goodsModel->updateCountAndInitByCode($goodsCode, $updateGoodsInit, $updateGoodsCount);
            if (!$isSuccess) {
                venus_db_rollback();
                $success = false;
                $data = "";
                $message = "修改数量失败";
            } else {
                venus_db_starttrans();
                $success = true;
                $data = "";
                $message = "修改数量成功";
            }
        }
        return array($success, $data, $message);
    }

    /**
     * @param $param
     * @return array
     * 根据货品批次修改入仓单货品价格
     */
    public function update_receipt_goods_bprice($param)
    {
        $warCode = $this->warCode;
        if (!isset($param)) {
            $param = $_POST;
        }
        $gbCode = $param["data"]['code'];
        $bprice = $param["data"]['bprice'];

        if (empty($gbCode)) return array(false, array(), "批次编号不能为空");
        if (empty($bprice)) return array(false, array(), "价格不能为空");
        if ($bprice < 0) return array(false, array(), "价格不能小于0");
        $goodstoredModel = GoodstoredDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
        $receiptModel = ReceiptDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);

        $gbData = $goodsbatchModel->queryByCode($gbCode);
        $supCode = $gbData['sup_code'];
        $recCode = $gbData['rec_code'];
        $recData = $receiptModel->queryByCode($recCode);
        if ($recData['rec_type'] == 2) {
            if ($supCode == "SU00000000000001") return array(false, array(), "只有非科贸供应商的货品可以修改价格");
        }

        $gsData = $goodstoredModel->queryByGbCode($gbCode);
        $gsCode = $gsData['gs_code'];
        $igsClause = array(
            "gscode" => $gsCode
        );
        $igoodsentData = $igoodsentModel->queryListByCondition($igsClause, 0, 1000);
        $igoodsentCodeArr = array_unique(array_column($igoodsentData, "igs_code"));
        $isSuccess = true;
        venus_db_starttrans();
        $isSuccess = $isSuccess && $goodsbatchModel->updateBpriceByCode($gbCode, $bprice);
        $isSuccess = $isSuccess && $goodstoredModel->updateBpriceByCode($gsCode, $bprice);
        if (!empty($igoodsentCodeArr)) {
            foreach ($igoodsentCodeArr as $igoodsentCode) {
                $isSuccess = $isSuccess && $igoodsentModel->updateBpriceByCode($igoodsentCode, $bprice);
            }
        }
        if ($isSuccess) {
            venus_db_commit();
            $success = true;
            $message = "修改采购价成功";
        } else {
            venus_db_rollback();
            $success = false;
            $message = "修改采购价失败，请重新尝试";
        }
        return array($success, array(), $message);
    }

    //过滤快进快出货品在同一个入仓单内的入仓货品
    private function getListAndListFastData($list, $goodsList)
    {
        $sameSkuData = array_intersect($list, $goodsList);
        foreach ($sameSkuData as $sameSkuDatum) {
            $delListIndex = array_search($sameSkuDatum, $list);
            unset($list[$delListIndex]);
        }
        return $list;
    }
}