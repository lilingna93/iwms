<?php

namespace Wms\Service;

use Common\Service\PassportService;
use Common\Service\PHPRpcService;
use Wms\Dao\GoodsbatchDao;
use Wms\Dao\GoodsDao;
use Wms\Dao\GoodstoredDao;
use Wms\Dao\IgoodsDao;
use Wms\Dao\IgoodsentDao;
use Wms\Dao\InvoiceDao;
use Wms\Dao\ReceiptDao;
use Wms\Dao\SkuDao;

class ReturnService
{

    static private $RETURN_TYPE_DATARETURN = 1;//数量不足，退回不足货品数量
    static private $RETURN_TYPE_GOODSRETURN = 2;//质量不符，退回相应数量货品

    static private $RETURN_STATUS_CREATE = 1;//退货申请单状态：申请中
    static private $RETURN_STATUS_CONFIRM = 2;//退货申请单状态：已处理
    static private $RETURN_STATUS_REJECT = 3;//退货申请单状态：已拒绝


    public $waCode;

    function __construct()
    {

        $userData = PassportService::getInstance()->loginUser();
        if (empty($userData)) {
            venus_throw_exception(110);
        }
        $this->uCode = $userData["user_code"];
        $this->waCode = $userData["warehousecode"];
        $this->uToken = $userData["user_token"];
        $this->workerWarehouseCode = $userData["war_code"];//user所代表的第三方仓库工作人员的仓库编号
    }

    /**
     * 处理退货单
     */
    public function return_goods_create($param)
    {
        if (!isset($param)) {
            $param = $_POST;
        }
        $skCode = $param["data"]["skCode"];
        $oCode = $param["data"]['oCode'];
        $skCount = $param["data"]['skCount'];
        $warCode = $param['data']['warCode'];

        $skuModel = SkuDao::getInstance();
        $gsModel = GoodstoredDao::getInstance($warCode);
        $gbModel = GoodsbatchDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $recModel = ReceiptDao::getInstance($warCode);

        venus_db_starttrans();
        $skuInfo = $skuModel->queryByCode($skCode);
        $count = $skCount * $skuInfo['spu_count'];
        $issetRec = $recModel->queryListByCondition(array("ecode" => $oCode), 0, 1000);
        if (empty($issetRec)) {
            $success = false;
            $message = "此订单无入库信息，请联系相关人员";
        } else {
            $gsInfo = $gsModel->queryBySkuCodeAndRecEcode($skCode, $oCode);
            if ($gsInfo['sku_count'] >= $skCount) {
                $gsUptRes = $gsModel->updateInitAndCountAndSkuCountByCode($gsInfo['gs_code'], $gsInfo['gs_count'] - $count, $gsInfo['gs_count'] - $count, $gsInfo['sku_count'] - $skCount);
                $gbUptRes = $gbModel->updateByCode($gsInfo['gb_code'], $gsInfo['gs_count'] - $count, $gsInfo['gb_bprice'], $gsInfo['sku_count'] - $skCount);
                $goodsInfo = $goodsModel->queryBySpuCode($gsInfo['spu_code']);
                $goodsUptRes = $goodsModel->updateCountAndInitByCode($goodsInfo['goods_code'], $goodsInfo['goods_init'] - $count, $goodsInfo['goods_count'] - $count);
                if (!$gsUptRes || !$gbUptRes || !$goodsUptRes) {
                    venus_db_rollback();
                    $success = false;
                    $message = "操作失败";
                } else {
                    venus_db_commit();
                    $success = true;
                }
            } else {
                venus_db_rollback();
                $success = false;
                $message = "此货品剩余" . $gsInfo['sku_count'] . $gsInfo['sku_unit'];
            }
        }
        return array($success, array(), $message);
    }

    public function return_cancel($param)
    {

        if (!isset($param)) {
            $param = $_POST;
        }
        $skCode = $param['data']["skCode"];
        $oCode = $param['data']['oCode'];
        $skCount = $param['data']['skCount'];
        $warCode = $param['data']['warCode'];
        $gsModel = GoodstoredDao::getInstance($warCode);
        $gbModel = GoodsbatchDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        venus_db_starttrans();
        if (empty($skCode)) {
            venus_throw_exception(1, "商品编号不能为空");
            return false;
        }
        if (empty($skCount)) {
            venus_throw_exception(1, "商品数量不能为空");
            return false;
        }
        if (empty($oCode)) {
            venus_throw_exception(1, "订单编号不能为空");
            return false;
        }
        $gsInfo = $gsModel->queryBySkuCodeAndRecEcode($skCode, $oCode);
        if (empty($gsInfo)) {
            venus_db_rollback();
            $success = false;
            $message = "没有此货品相关信息";
            $data = array();
            return array($success, $data, $message);
        } else {
            $count = $skCount * $gsInfo['spu_count'];
            $gsUptRes = $gsModel->updateInitAndCountAndSkuCountByCode($gsInfo['gs_code'], $gsInfo['gs_count'] + $count, $gsInfo['gs_count'] + $count, $gsInfo['sku_count'] + $skCount);
            $gbUptRes = $gbModel->updateByCode($gsInfo['gb_code'], $gsInfo['gs_count'] + $count, $gsInfo['gb_bprice'], $gsInfo['sku_count'] + $skCount);
            $goodsInfo = $goodsModel->queryBySpuCode($gsInfo['spu_code']);
            $goodsUptRes = $goodsModel->updateCountAndInitByCode($goodsInfo['goods_code'], $goodsInfo['goods_init'] + $count, $goodsInfo['goods_count'] + $count);
        }

        if (!$gsUptRes || !$gbUptRes || !$goodsUptRes) {
            venus_db_rollback();
            $success = false;
            $message = "操作失败";
        } else {
            venus_db_commit();
            $success = true;
            $message = "";
        }
        $data = array();
        return array($success, $data, $message);
    }

    public function return_igo_goods($param)
    {
        if (!isset($param)) {
            $param = $_POST;
            $warInfo = $this->getUserInfo();
            $warCode = $warInfo["warCode"];
        } else {
            $warCode = $param['data']['warCode'];
        }

        $igoCode = $param["data"]["igoCode"];


        $igsModel = IgoodsentDao::getInstance($warCode);
        $igoModel = IgoodsDao::getInstance($warCode);
        $invModel = InvoiceDao::getInstance($warCode);
        $gsModel = GoodstoredDao::getInstance($warCode);
        $gbModel = GoodsbatchDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        venus_db_starttrans();
        $igoInfo = $igoModel->queryByCode($igoCode);
        $igsInfo = $igsModel->queryListByCondition(array("igocode" => $igoCode));
        $gsInfoArr = array();
        $isSuccess = true;
        foreach ($igsInfo as $igsData) {
            $gsCode = $igsData['gs_code'];
            if (!isset($gsInfoArr[$gsCode]['count'])) {
                $gsInfoArr[$gsCode]['count'] = 0;
                $gsInfoArr[$gsCode]['sku_count'] = 0;
            }
            $gsInfoArr[$gsCode]['count'] = bcadd($igsData['igs_count'], $gsInfoArr[$gsCode]['count'], 2);
            $gsInfoArr[$gsCode]['sku_count'] = bcadd($igsData['sku_count'], $gsInfoArr[$gsCode]['sku_count'], 2);
        }
        $invCode = $igoInfo['inv_code'];
        $spuCode = $igoInfo['spu_code'];
        $count = 0;
        foreach ($gsInfoArr as $gsCode => $gsData) {
            $igsCount = $gsData['count'];
            $count = bcadd($igsCount, $count, 2);
            $igsSkuCount = $gsData['sku_count'];
            $gsInfo = $gsModel->queryByCode($gsCode);
            $isSuccess = $isSuccess && $gsModel->updateByCode($gsCode, $gsInfo['gs_count'] + $igsCount);
            $isSuccess = $isSuccess && $gsModel->updateSkuCountByCode($gsCode, $gsInfo['sku_count'] + $igsSkuCount);
        }

        $goodsInfo = $goodsModel->queryBySpuCode($spuCode);
        $isSuccess = $isSuccess && $goodsModel->updateCountByCode($goodsInfo['goods_code'], $goodsInfo['goods_count'], $goodsInfo['goods_count'] + $count);
        $isSuccess = $isSuccess && $igsModel->deleteByIgoCode($igoCode);
        $isSuccess = $isSuccess && $igoModel->deleteByCode($igoCode, $invCode);
        $issetIgoList = $igoModel->queryListByInvCode($invCode);
        if (empty($issetIgoList)) {
            $isSuccess = $isSuccess && $invModel->deleteByCode($invCode);//退货，如果出仓单无数据删除调用
        }
        if (!$isSuccess) {
            venus_db_rollback();
            $success = false;
            $message = "操作失败";
        } else {
            venus_db_commit();
            $success = true;
            $message = "";
        }
        $data = array();
        return array($success, $data, $message);
    }

    //    //退货查询真实退货数量
//    public function search_goods_count($param)
//    {
//        //更新，可能会查igoCode
//        if (!isset($param)) {
//            $param = $_POST;
//            $warInfo = $this->getUserInfo();
//            $warCode = $warInfo["warCode"];
//        } else {
//            $warCode = $param['data']['warCode'];
//        }
//        $skCode = $param["data"]["skCode"];
//        $oCode = $param["data"]["oCode"];
//        $spCode = $param["data"]["spCode"];
//        $init = $param["data"]["init"];//入库时的数量
//        $oldCount = $param["data"]["oldCount"];//现在剩余数量
//
//
//        $gsModel = GoodstoredDao::getInstance($warCode);
//        $gsInfo = $gsModel->queryBySpuCodeAndInitAndCountAndRecEcode($spCode, $init, $oldCount, $oCode);
//        if (!empty($gsInfo)) {
//            $message = "此货品剩余" . $gsInfo['sku_count'] . $gsInfo['sku_unit'];
//        } else {
//            $message = "此货品无剩余库存";
//        }
//        $success = true;
//        $data = $gsInfo['sku_count'];
//        return array($success, $data, $message);
//    }
    //验货前拒绝退货单
    public function return_rec_cancel($param)
    {

        if (!isset($param)) {
            $param = $_POST;
        }
        $skCode = $param['data']["skCode"];
        $oCode = $param['data']['oCode'];//订单编号
        $skCount = $param['data']['skCount'];//退回数量
        $warCode = $param['data']['warCode'];
        $skInit = $param['data']['skInit'];//入库数量
        $gsModel = GoodstoredDao::getInstance($warCode);
        $gbModel = GoodsbatchDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $igoodsModel = IgoodsDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);
        $isSuccess = true;
        venus_db_starttrans();
        if (empty($skCode)) {
            venus_db_rollback();
            $success = false;
            $message = "商品编号不能为空";
            $data = array();
            return array($success, $data, $message);
        }
        if (empty($skCount)) {
            venus_db_rollback();
            $success = false;
            $message = "商品退货数量不能为空";
            $data = array();
            return array($success, $data, $message);
        }
        if (empty($skInit)) {
            venus_db_rollback();
            $success = false;
            $message = "商品入库数量不能为空";
            $data = array();
            return array($success, $data, $message);
        }
        if (empty($oCode)) {
            venus_db_rollback();
            $success = false;
            $message = "订单编号不能为空";
            $data = array();
            return array($success, $data, $message);
        }

        $gsInfo = $gsModel->queryBySkuCodeAndRecEcodeAndSkInit($skCode, $oCode, $skInit);

        if (empty($gsInfo)) {

            $goodsListCount = $goodsModel->queryCountByCondition();
            if ($goodsListCount == 0 || empty($goodsListCount)) {
                venus_db_commit();
                $success = true;
                $message = "此仓库无进销存系统";
                $data = array();
                return array($success, $data, $message);
            } else {
                venus_db_rollback();
                $success = false;
                $message = "没有此货品相关信息";
                $data = array();
                return array($success, $data, $message);
            }
        } else {
            $count = bcmul($skCount, $gsInfo['spu_count'], 2);
            $gsCode = $gsInfo['gs_code'];

            //检测是否是快进快出订单
            $issetVirtual = InvoiceDao::getInstance($warCode)->queryByMark($gsInfo['rec_code']);
            $issetDataGbCode = IgoodsentDao::getInstance($warCode)->queryCountByGbCode(array("gbcode" => $gsInfo['gb_code']));
            if ($gsInfo['sku_count'] == 0 && $issetDataGbCode>0 && !empty($issetVirtual)) {
                $updateGsInit = bcadd($gsInfo['gs_init'], $count, 2);
                $updateGsSkuInit = bcadd($gsInfo['sku_init'], $count, 2);
                $updateGsCount = bcadd($gsInfo['gs_count'], 0, 2);
                $updateGsSkuCount = bcadd($gsInfo['sku_count'], 0, 2);
                $isSuccess = $isSuccess &&
                    $gsModel->updateInitAndSkuInitAndCountAndSkuCountByCode($gsCode, $updateGsInit, $updateGsCount, $updateGsSkuInit, $updateGsSkuCount);
                $gbCode = $gsInfo['gb_code'];
                $gbData = $gbModel->queryByCode($gbCode);
                $updateGbBprice = $gbData['gb_bprice'];
                $updateGbCount = bcadd($gbData['gb_count'], $count, 2);
                $updateGbSkuCount = bcadd($gbData['sku_count'], $count, 2);
                $isSuccess = $isSuccess &&
                    $gbModel->updateByCode($gbCode, $updateGbCount, $updateGbBprice, $updateGbSkuCount);
                $goodsInfo = $goodsModel->queryBySpuCode($gsInfo['spu_code']);
                $goodsCode = $goodsInfo['goods_code'];
                $updateGoodsInit = bcadd($goodsInfo['goods_init'], $count, 2);
                $updateGoodsCount = bcadd($goodsInfo['goods_count'], 0, 2);
                $isSuccess = $isSuccess &&
                    $goodsModel->updateCountAndInitByCode($goodsCode, $updateGoodsInit, $updateGoodsCount);
                $igsData = $igoodsentModel->queryListByCondition(array("invcode" => $issetVirtual["inv_code"], "gscode" => $gsCode));
                $igsData = $igsData[0];//快进快出只有一条出仓记录
                $igoCode = $igsData['igo_code'];
                $igsCode = $igsData['igs_code'];

                $updateIgsSkuCount = bcadd($igsData['sku_count'], $count, 2);
                $updateIgsCount = bcadd($igsData['igs_count'], $count, 2);
                $isSuccess = $isSuccess && $igoodsentModel->updateCountAndSkuCountByCode($igsCode, $updateIgsCount, $updateIgsSkuCount);
                $igoData = $igoodsModel->queryByCode($igoCode);
                $updateIgoSkuCount = bcadd($igoData['sku_count'], $count, 2);
                $updateIgoCount = bcadd($igoData['igo_count'], $count, 2);
                $isSuccess = $isSuccess && $igoodsModel->updateCountAndSkuCountByCode($igoCode, $updateIgoCount, $updateIgoSkuCount);
            } else {
                $updateGsInit = bcadd($gsInfo['gs_init'], $count, 2);
                $updateGsSkuInit = bcadd($gsInfo['sku_init'], $count, 2);
                $updateGsCount = bcadd($gsInfo['gs_count'], $count, 2);
                $updateGsSkuCount = bcadd($gsInfo['sku_count'], $count, 2);
                $isSuccess = $isSuccess &&
                    $gsModel->updateInitAndSkuInitAndCountAndSkuCountByCode($gsCode, $updateGsInit, $updateGsCount, $updateGsSkuInit, $updateGsSkuCount);
                $gbCode = $gsInfo['gb_code'];
                $gbData = $gbModel->queryByCode($gbCode);
                $updateGbBprice = $gbData['gb_bprice'];
                $updateGbCount = bcadd($gbData['gb_count'], $count, 2);
                $updateGbSkuCount = bcadd($gbData['sku_count'], $count, 2);
                $isSuccess = $isSuccess &&
                    $gbModel->updateByCode($gbCode, $updateGbCount, $updateGbBprice, $updateGbSkuCount);
                $goodsInfo = $goodsModel->queryBySpuCode($gsInfo['spu_code']);
                $goodsCode = $goodsInfo['goods_code'];
                $updateGoodsInit = bcadd($goodsInfo['goods_init'], $count, 2);
                $updateGoodsCount = bcadd($goodsInfo['goods_count'], $count, 2);
                $isSuccess = $isSuccess &&
                    $goodsModel->updateCountAndInitByCode($goodsCode, $updateGoodsInit, $updateGoodsCount);
            }


        }

        if (!$isSuccess) {
            venus_db_rollback();
            $success = false;
            $message = "修改验货时小仓数据失败";
        } else {
            venus_db_commit();
            $success = true;
            $message = "修改验货时小仓数据成功";
        }
        $data = array();
        return array($success, $data, $message);
    }

    //验货后退货创建出仓单
    public function return_invoice_create($param)
    {
        $type = 6;
        $warInfo = $this->getUserInfo();
        $receiver = $warInfo['worRname'];//客户名称
        $phone = $warInfo['worPhone'];//客户手机号
        $address = $warInfo['warAddress'];//客户地址
        $postal = $warInfo['warPostal'];//客户邮编
        $worCode = $warInfo['worCode'];
        $warCode = $warInfo['warCode'];

        $skCode = $param["data"]["skCode"];
        $spCode = $param["data"]["spCode"];
        $oCode = $param["data"]["oCode"];
        $init = floatval($param["data"]["init"]);//入库时的数量
        $count = $param["data"]["count"];//退货数量
        if (empty($skCode)) {
            venus_db_rollback();
            $success = false;
            $message = "商品编号不能为空";
            $data = array();
            return array($success, $data, $message);
        }
        if (empty($count)) {
            venus_db_rollback();
            $success = false;
            $message = "商品退货数量不能为空";
            $data = array();
            return array($success, $data, $message);
        }
        if (empty($init)) {
            venus_db_rollback();
            $success = false;
            $message = "商品入库数量不能为空";
            $data = array();
            return array($success, $data, $message);
        }
        if (empty($oCode)) {
            venus_db_rollback();
            $success = false;
            $message = "订单编号不能为空";
            $data = array();
            return array($success, $data, $message);
        }
        $gsModel = GoodstoredDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $invModel = InvoiceDao::getInstance($warCode);
        $igoodsModel = IgoodsDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);
        venus_db_starttrans();
        $isSuccess = true;
        $issetInv = $invModel->queryByEcode($oCode);
        if (empty($issetInv)) {
            $invAddData = array(
                "status" => 5,//出仓单状态
                "receiver" => $receiver,//客户名称
                "phone" => $phone,//客户手机号
                "address" => $address,//客户地址
                "postal" => $postal,//客户邮编
                "type" => $type,//出仓单类型
                "ecode" => $oCode,//出仓单备注
                "worcode" => $worCode,//人员编号
                "returnmark" => "退货",
            );//出仓单新增数据
            $invCode = $invModel->insert($invAddData);//出仓单创建操作
            $isSuccess = $isSuccess && !empty($invCode);
            if (empty($invCode)) {
                venus_db_rollback();
                $success = false;
                $data = array();
                $msg = "创建出仓单失败";
                return array($success, $data, $msg);
            }
        } else {
            $invCode = $issetInv['inv_code'];
        }

        $gsInfo = $gsModel->queryBySpuCodeAndInitAndRecEcode($spCode, $init, $oCode);
        if (empty($gsInfo)) {
            venus_db_rollback();
            $success = false;
            $message = "无相关退货货品信息";
            $data = array();
            return array($success, $data, $message);
        }
        $gsCount = $gsInfo['gs_count'];
        $gsSkuCount = $gsInfo['sku_count'];
        $gsCode = $gsInfo['gs_code'];
        $bPrice = $gsInfo['gb_bprice'];
        $goodsData = $goodsModel->queryBySpuCode($spCode);
        $goodsCode = $goodsData['goods_code'];
        $goodsCount = $goodsData['goods_count'];
        //数量小于批次库存数量
        if ($count <= $gsCount) {
            $igoodsAddData = array(
                "count" => $count,//spu总数量
                "spucode" => $spCode,//spu编号
                "sprice" => 0,//spu当前销售价
                "pprice" => 0,//spu当前利润
                "goodscode" => $goodsCode,//库存编号
                "percent" => 0,//spu当前利润率
                "skucode" => $skCode,//sku编号
                "skucount" => $count,//sku数量
                "invcode" => $invCode,//所属出仓单单号
            );

            $igoCode = $igoodsModel->insert($igoodsAddData);//创建发货清单
            if (empty($igoCode)) {
                venus_db_rollback();
                $success = false;
                $data = array();
                $msg = "创建出仓单发货清单失败";
                return array($success, $data, $msg);
            }
            $isSuccess = $isSuccess && !empty($igoCode);
            $igsAddData = array(
                "count" => $count,
                "bprice" => $bPrice,
                "spucode" => $spCode,
                "gscode" => $gsCode,
                "igocode" => $igoCode,
                "skucode" => $skCode,
                "skucount" => $count,
                "invcode" => $invCode,
            );
            $igsCode = $igoodsentModel->insert($igsAddData);//创建发货记录
            if (empty($igsCode)) {
                venus_db_rollback();
                $success = false;
                $data = array();
                $msg = "创建出仓单发货记录失败";
                return array($success, $data, $msg);
            }
            $isSuccess = $isSuccess && !empty($igsCode);
        } else {
            venus_db_rollback();
            $success = false;
            $data = array();
            if (floatval($gsSkuCount) == 0) {
                $msg = "此批次货品已经全部出库，无法进行退货操作";
            } else {
                $msg = "此批次货品可退货数量为 " . $gsSkuCount . $gsInfo['sku_unit'];
            }
            return array($success, $data, $msg);
        }
        $updateGsSkuCount = bcsub($gsSkuCount, $count, 2);
        $updateGsCount = bcsub($gsCount, $count, 2);
        $isSuccess = $isSuccess &&
            $gsModel->updateCountAndSkuCountByCode($gsCode, $updateGsCount, $updateGsSkuCount);//减少发货库存批次数量
        $newCountGoods = bcsub($goodsCount, $count, 2);//新库存
        $isSuccess = $isSuccess &&
            $goodsModel->updateCountByCode($goodsCode, $goodsCount, $newCountGoods);//修改库存
        if (!$isSuccess) {
            venus_db_rollback();
            $success = false;
            $data = array();
            $msg = "创建退货出仓记录失败";
            return array($success, $data, $msg);
        } else {
            venus_db_commit();
            $success = true;
            $data = array(
                "igoCode" => $igoCode
            );
            $msg = "创建退货出仓记录成功，退货出库编号为$igoCode";
            return array($success, $data, $msg);
        }
    }

    //拒绝退货操作
    public function return_goods_cancel($param)
    {
        $warInfo = $this->getUserInfo();
        $warCode = $warInfo["warCode"];
        $igoCode = $param["data"]["igoCode"];
        if (empty($igoCode)) {
            venus_db_rollback();
            $success = false;
            $message = "出仓货品批次编号不能为空";
            $data = array();
            return array($success, $data, $message);
        }

        $igsModel = IgoodsentDao::getInstance($warCode);
        $igoModel = IgoodsDao::getInstance($warCode);
        $invModel = InvoiceDao::getInstance($warCode);
        $gsModel = GoodstoredDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);

        $igoInfo = $igoModel->queryByCode($igoCode);
        $igsInfo = $igsModel->queryListByCondition(array("igocode" => $igoCode));
        $gsInfoArr = array();
        $isSuccess = true;
        venus_db_starttrans();
        //查出此货品相关退货信息
        foreach ($igsInfo as $igsData) {
            $gsCode = $igsData['gs_code'];
            if (!isset($gsInfoArr[$gsCode]['count'])) {
                $gsInfoArr[$gsCode]['count'] = 0;
                $gsInfoArr[$gsCode]['sku_count'] = 0;
            }
            $gsInfoArr[$gsCode]['count'] = bcadd($igsData['igs_count'], $gsInfoArr[$gsCode]['count'], 2);//要退的spu数量
            $gsInfoArr[$gsCode]['sku_count'] = bcadd($igsData['sku_count'], $gsInfoArr[$gsCode]['sku_count'], 2);//要退的sku数量
        }
        $invCode = $igoInfo['inv_code'];
        $spuCode = $igoInfo['spu_code'];
        $count = 0;
        foreach ($gsInfoArr as $gsCode => $gsData) {
            $igsCount = $gsData['count'];
            $count = bcadd($igsCount, $count, 2);
            $igsSkuCount = $gsData['sku_count'];
            $gsInfo = $gsModel->queryByCode($gsCode);
            $gsCount = $gsInfo['gs_count'];
            $gsSkuCount = $gsInfo['sku_count'];
            $updateGsCount = bcadd($gsCount, $igsCount, 2);
            $updateGsSkuCount = bcadd($gsSkuCount, $igsSkuCount, 2);
            $isSuccess = $isSuccess && $gsModel->updateCountAndSkuCountByCode($gsCode, $updateGsCount, $updateGsSkuCount);
        }

        $goodsInfo = $goodsModel->queryBySpuCode($spuCode);
        $isSuccess = $isSuccess && $goodsModel->updateCountByCode($goodsInfo['goods_code'], $goodsInfo['goods_count'], bcadd($goodsInfo['goods_count'], $count, 2));
        $isSuccess = $isSuccess && $igsModel->deleteByIgoCode($igoCode);
        $isSuccess = $isSuccess && $igoModel->deleteByCode($igoCode, $invCode);
        $issetIgoList = $igoModel->queryListByInvCode($invCode);

        if (empty($issetIgoList)) {
            $isSuccess = $isSuccess && $invModel->deleteByCode($invCode);//退货，如果出仓单无数据删除调用
        }

        if (!$isSuccess) {
            venus_db_rollback();
            $success = false;
            $message = "小仓退货操作失败";
        } else {
            venus_db_commit();
            $success = true;
            $message = "小仓退货成功";
        }
        $data = array();
        return array($success, $data, $message);

    }

    //修改退货信息/实际数量小于退货数量
    public function return_goods_update($param)
    {
        $warInfo = $this->getUserInfo();
        $warCode = $warInfo["warCode"];
        $igoCode = $param["data"]["igoCode"];
        $count = $param["data"]["count"];//实际发货数量
        if (empty($igoCode)) {
            venus_db_rollback();
            $success = false;
            $message = "出仓货品批次编号不能为空";
            $data = array();
            return array($success, $data, $message);
        }
        if (empty($igoCode)) {
            venus_db_rollback();
            $success = false;
            $message = "货品实际退货数量不能为空";
            $data = array();
            return array($success, $data, $message);
        }
        $igsModel = IgoodsentDao::getInstance($warCode);
        $igoModel = IgoodsDao::getInstance($warCode);
        $gsModel = GoodstoredDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        venus_db_starttrans();

        $igoInfo = $igoModel->queryByCode($igoCode);
        $igoCount = $igoInfo['igo_count'];
        $igsInfo = $igsModel->queryListByCondition(array("igocode" => $igoCode));
        $isSuccess = true;
        $igsData = $igsInfo[0];//发出数据只有一条
        $gsCode = $igsData['gs_code'];
        //实际数量小于发货数量
        //修改igo数量
        $isSuccess = $isSuccess && $igoModel->updateCountAndSkuCountByCode($igoCode, $count, $count);
//        if(!$isSuccess) return array(false,"","igo");
        $igsCode = $igsData['igs_code'];
        $isSuccess = $isSuccess && $igsModel->updateCountAndSkuCountByCode($igsCode, $count, $count);
//        if(!$isSuccess) return array(false,"","igs");
        $spuCode = $igoInfo['spu_code'];
        $gsInfo = $gsModel->queryByCode($gsCode);//查询批次库存
        $addGsCount = bcsub($igoCount, $count, 2);//增加数量
        $gsCount = bcadd($gsInfo['gs_count'], $addGsCount, 2);//剩余数量
        $isSuccess = $isSuccess && $gsModel->updateCountAndSkuCountByCode($gsCode, $gsCount, $gsCount);
//        if(!$isSuccess) return array(false,"","gs");
        $goodsInfo = $goodsModel->queryBySpuCode($spuCode);//查询库存
        $goodsCode = $goodsInfo['goods_code'];
        $goodsCount = $goodsInfo['goods_count'];
        $updateGoodsCount = bcadd($goodsCount, $addGsCount, 2);
        $isSuccess = $isSuccess && $goodsModel->updateCountByCode($goodsCode, $goodsCount, $updateGoodsCount);
//        if(!$isSuccess) return array(false,"","goods");
        if (!$isSuccess) {
            venus_db_rollback();
            $success = false;
            $message = "小仓修改退货操作失败";
        } else {
            venus_db_commit();
            $success = true;
            $message = "小仓修改退货成功";
        }
        $data = array();
        return array($success, $data, $message);
    }

    private
    function getUserInfo()
    {
        $workerData = PassportService::getInstance()->loginUser();
        if (empty($workerData)) {
            venus_throw_exception(110);
        }
        return array(
            'warCode' => $workerData["war_code"],
            'worCode' => $workerData["wor_code"],
            'worName' => $workerData["wor_name"],
            'worRname' => $workerData["wor_rname"],
        );
    }
}




