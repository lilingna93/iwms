<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2018/10/9
 * Time: 15:27
 * 订单相关，售后，快进快出
 */

namespace Wms\Service;


use Common\Service\PassportService;
use Wms\Dao\GoodsbatchDao;
use Wms\Dao\GoodsDao;
use Wms\Dao\GoodstoredDao;
use Wms\Dao\IgoodsDao;
use Wms\Dao\IgoodsentDao;
use Wms\Dao\InvoiceDao;
use Wms\Dao\OrderDao;
use Wms\Dao\OrdergoodsDao;
use Wms\Dao\PositionDao;
use Wms\Dao\ReceiptDao;
use Wms\Dao\SpuDao;
use Wms\Dao\UserDao;

class AccidentService
{
    static private $GOODSBATCH_STATUS_FINISH = "4";//货品批次使用完状态
    static private $RECEIPT_STATUS_FINISH = "3";//入仓单完成状态
    static private $INVOICE_STATUS_FINISH = "5";//出仓单已出仓状态
    static private $INVOICE_CREATE_TYPE_HANDWORK = "1";//手工创建

    public $warCode;
    public $worcode;

    public function __construct()
    {
//        $workerData = PassportService::getInstance()->loginUser();
//        if (empty($workerData)) {
//            venus_throw_exception(110);
//        }
//
//        $this->warCode = $workerData["war_code"];
//        $this->worcode = $workerData["wor_code"];
        $this->worcode = "WO30817165215125";
        $this->warCode = "WA000001";
    }

    public function rec_and_inv_fast($param)
    {
        $param['data']['ctime'] = "2018-10-10";
        $param['data']['spuList'] = array(
            "SP000292" => "1.98",
            "SP000291" => "28.89",
            "SP000290" => "28.89",
            "SP000295" => "24.56",
            "SP000294" => "21.11",
            "SP000293" => "21.11",
        );
        $param['data']['orderList'] = array(
            "O31010080125338",
            "O31010081028745",
            "O31010134830632",
            "O31010144621424",
            "O31010144707562",
        );
        if (!isset($param)) {
            $param = $_POST;
            $type = self::$INVOICE_CREATE_TYPE_HANDWORK;//pc端，手工记账
        }
        $warCode = $this->warCode;
        $worCode = $this->worcode;
        $ctime = date("Y-m-d", $param['data']['ctime']) . " 06:00:00";
        $spuList = $param['data']['spuList'];//spuList数据格式{spu_code:count}
        $orderList = $param['data']['orderList'];//orderList数据格式{order_code}


        $ordergoodsModel = OrdergoodsDao::getInstance($warCode);
        $orderModel = OrderDao::getInstance($warCode);
        $recModel = ReceiptDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
        $goodstoredModel = GoodstoredDao::getInstance($warCode);
        $positionModel = PositionDao::getInstance($warCode);
        $invModel = InvoiceDao::getInstance($warCode);
        $igoodsModel = IgoodsDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);
        $spuModel = SpuDao::getInstance($warCode);
        $invoiceService = new InvoiceService();
        venus_db_starttrans();

        foreach ($spuList as $spuCode => $spuBprice) {
            if (empty($spuBprice)) {
                venus_db_rollback();
                venus_throw_exception(1, "spu价格格式不能为空");
                return false;
            }
            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $spuBprice)) {
                venus_db_rollback();
                venus_throw_exception(4, "spu价格格式不正确");
                return false;
            }
            $uptOrdergoodsBprice = $ordergoodsModel->updateBpriceByOrderCodeAndSpuCodeAndSpuBprice($spuCode, $spuBprice, $orderList);
            $uptSpuBprice = $spuModel->updateBpriceCodeByCode($spuCode, $spuBprice);
            if (!$uptOrdergoodsBprice || !$uptSpuBprice) {
                venus_db_rollback();
                $message = '修改成本价失败';
                venus_throw_exception(2, $message);
                return false;
            }
        }
        $recSpuDataArr = array();
        $invSpuDataArr = array();
        foreach ($orderList as $oCode) {

            $goodsList = $ordergoodsModel->queryListByOrderCode($oCode, $page = 0, $count = 100000);//获取订单里的所有货品数据
            foreach ($goodsList as $goodsData) {
                if ($goodsData['sup_type'] == 1 && $goodsData['supplier_code'] !== "SU00000000000001" && $goodsData['goods_count'] > 0) {
                    $orderMsg = $orderModel->queryByCode($oCode);
                    //出仓单数据
                    $addInvData = array();
                    $addInvData['receiver'] = $orderMsg['user_name'];
                    $addInvData['phone'] = $orderMsg['user_phone'];
                    $addInvData['address'] = $orderMsg['war_address'];
                    $addInvData['postal'] = $orderMsg['war_postal'];
                    $addInvData['type'] = self::$INVOICE_CREATE_TYPE_HANDWORK;
                    $addInvData['mark'] = $orderMsg['order_mark'];
                    $addInvData['worcode'] = $worCode;
                    $addInvData['ctime'] = $orderMsg['order_ctime'];
                    $addInvData['ecode'] = $oCode;
                    $goodsToInv = array();
                    $goodsToInv['skucode'] = $goodsData['sku_code'];
                    $goodsToInv['skucount'] = $goodsData['sku_count'];
                    $goodsToInv['spucode'] = $goodsData['spu_code'];
                    $goodsToInv['count'] = $goodsData['goods_count'];
                    $goodsToInv['sprice'] = $goodsData['spu_sprice'];
                    $goodsToInv['pprice'] = bcmul($goodsData['spu_sprice'], $goodsData['pro_percent'],2);
                    $goodsToInv['percent'] = $goodsData['pro_percent'];
                    $invSpuDataArr[$oCode]['goods'][] = $goodsToInv;
                    $invSpuDataArr[$oCode]['invMsg'] = $addInvData;
                    unset($goodsToInv);
                    unset($addInvData);
                    //入仓单数据
                    $goodsToRec = array();
                    $posCode = $positionModel->queryByWarCode($warCode)['pos_code'];
                    $goodsToRec['skucode'] = $goodsData['sku_code'];
                    $goodsToRec['skucount'] = $goodsData['sku_count'];
                    $goodsToRec['spucode'] = $goodsData['spu_code'];
                    $goodsToRec['count'] = $goodsData['goods_count'];
                    $goodsToRec['bprice'] = $goodsData['spu_bprice'];
                    $goodsToRec['poscode'] = $posCode;
                    $goodsToRec['ctime'] = $ctime;

                    $recSpuDataArr[] = $goodsToRec;
                    unset($goodsToRec);
                } else {
                    continue;
                }
            }
        }
        $addRecData['ctime'] = $ctime;
        $addRecData['status'] = self::$RECEIPT_STATUS_FINISH;
        $addRecData['worcode'] = $worCode;
        $recCode = $recModel->insert($addRecData);
        foreach ($recSpuDataArr as $recSpuData) {
            $addGbData = $recSpuData;
            $addGbData['status'] = self::$GOODSBATCH_STATUS_FINISH;
            $addGbData['reccode'] = $recCode;
            $gbCode = $goodsbatchModel->insert($addGbData);
            $addGsData = $recSpuData;
            $addGsData['init'] = $recSpuData['count'];
            $addGsData['gbcode'] = $gbCode;
            $gsCode = $goodstoredModel->insert($addGsData);
            $issetGoods = $goodsModel->queryBySpuCode($recSpuData['spucode']);
            if ($issetGoods) {
                $goodsCode = $issetGoods['goods_code'];
                $init = $issetGoods['goods_init'] + $recSpuData['count'];
                $count = $issetGoods['goods_count'] + $recSpuData['count'];
                $goodsRes = $goodsModel->updateCountAndInitByCode($goodsCode, $init, $count);
            } else {
                $goodsAddData = array(
                    'init' => $recSpuData['count'],
                    'count' => $recSpuData['count'],
                    'spucode' => $recSpuData['spucode']
                );
                $goodsRes = $goodsModel->insert($goodsAddData);
            }
            if (!$gbCode) {
                venus_db_rollback();
                $message = '创建批次失败';
                venus_throw_exception(2, $message);
                return false;
            }
            if (!$gsCode) {
                venus_db_rollback();
                $message = '创建库存批次失败';
                venus_throw_exception(2, $message);
                return false;
            }
            if (!$goodsRes) {
                venus_db_rollback();
                $message = '存入库存失败';
                venus_throw_exception(2, $message);
                return false;
            }
        }
        foreach ($invSpuDataArr as $ocode => $invSpuData) {
            $igoodsDataList = array();
            $addInvData = $invSpuData['invMsg'];
            $addInvData['status'] = self::$INVOICE_STATUS_FINISH;
            $invCode = $invModel->insert($addInvData);
            foreach ($invSpuData['goods'] as $invSpuDatum) {
                $addIgoData = $invSpuDatum;
                $addIgoData['invcode'] = $invCode;
                $goodsData = $goodsModel->queryBySpuCode($invSpuDatum['spucode']);
                $addIgoData['goodscode'] = $goodsData['goods_code'];
                $addIgoRes = $igoodsModel->insert($addIgoData);

                if (!$addIgoRes) {
                    venus_db_rollback();
                    $message = '创建出仓单货品失败';
                    venus_throw_exception(2, $message);
                    return false;
                }
            }
            $igoodsDataList = $igoodsModel->queryListByInvCode($invCode, 0, 10000);

            foreach ($igoodsDataList as $igoodsDatum) {

                $goodsData = $goodsModel->queryBySpuCode($igoodsDatum['spu_code']);
                if (empty($igoodsDatum['goods_code']) && !empty($goodsData)) {
                    $uptIgoodsToGoodsCode = $igoodsModel->updateGoodsCodeByCode($igoodsDatum['igo_code'], $goodsData['goods_code']);
                    if (!$uptIgoodsToGoodsCode) {
                        venus_db_rollback();
                        venus_throw_exception(2, "修改发货清单数据失败");
                        return false;
                    }
                }
                $goodsCount = $goodsData['goods_count'];
                $igoCount = $igoodsDatum['igo_count'];
                //创建状态产生批次库位及库存变化
                $goodstoredList = $goodstoredModel->queryListBySkuCode($igoodsDatum['sku_code']);//指定商品的库存货品批次货位列表数据
                $igoodsentDataList = $invoiceService->branch_goodstored($goodstoredList, $igoodsDatum['igo_count'], $igoodsDatum['igo_code'], $igoodsDatum['spu_code'], $invCode);//调用出仓批次方法
                foreach ($igoodsentDataList as $goodsoredCount => $igoodsentData) {
                    foreach ($igoodsentData as $igoodsentDatum) {
                        $uptGsSpuCount = $goodstoredModel->updateByCode($igoodsentDatum['gscode'], $goodsoredCount);//修改发货库存批次剩余数量
                        $gsSkuCount = $goodstoredModel->queryByCode($igoodsentDatum['gscode'])['sku_count'];
                        if ($gsSkuCount < $igoodsentDatum['skucount']) {
                            $spName = $spuModel->queryByCode($igoodsDatum['spu_code'])['spu_name'];
                        } else {

                            $uptGsSkuCount = $goodstoredModel->updateSkuCountByCode($igoodsentDatum['gscode'], $gsSkuCount - $igoodsentDatum['skucount']);//减少发货库存批次sku数量
                            $igoodsentCode = $igoodsentModel->insert($igoodsentDatum);//创建发货批次
                            if (!$uptGsSpuCount || !$uptGsSkuCount) {
                                $spName = $spuModel->queryByCode($igoodsDatum['spu_code'])['spu_name'];
                                venus_db_rollback();
                                venus_throw_exception(2, "修改" . $spName . "库存批次失败");
                                return false;
                            }
                            if (!$igoodsentCode) {
                                venus_db_rollback();
                                venus_throw_exception(2, "创建发货批次失败");
                                return false;
                            }
                        }
                    }
                }
                $newCountGoods = $goodsCount - $igoCount;
                $uptGoods = $goodsModel->updateCountByCode($goodsData['goods_code'], $goodsData['goods_count'], $newCountGoods);
                if (!$uptGoods) {
                    venus_db_rollback();
                    venus_throw_exception(2, "修改库存失败");
                    return false;
                }
            }
        }
        venus_db_commit();
        $success = true;
        $message = '';
        return array($success, array(), $message);
    }
}