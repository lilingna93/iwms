<?php
/**
 * Created by PhpStorm.
 * User: lilingna
 * Date: 2019/1/23
 * Time: 17:47
 */

namespace Wms\Service;

use Wms\Dao\GoodsbatchDao;
use Wms\Dao\GoodsDao;
use Wms\Dao\GoodstoredDao;
use Wms\Dao\IgoodsDao;
use Wms\Dao\IgoodsentDao;
use Wms\Dao\InvoiceDao;
use Wms\Dao\ReceiptDao;
use Wms\Dao\SkuDao;

class WarehouseService
{
    function __construct()
    {
    }

    /**
     * @param $data [type入仓单类型,warcode仓库编号,worcode入仓人员编号,mark备注信息;list货品信息["count","bprice","spucode","supcode","skucode","skucount"]];
     * @return array
     * 入库操作[入+1，存+1]
     */
    public function create_receipt($data)
    {
        $type = $data["type"];
        $warCode = $data["warCode"];
        $worCode = $data["worCode"];
        $mark = $data["mark"];
        $recData = $data["list"];
        $ecode = $data["ecode"];
        $room = $data["room"];
        $ctime = $data["ctime"];

        if (empty($type)) return array(false, array(), "入仓单类型不能为空");
        if (empty($warCode)) return array(false, array(), "仓库编号不能为空");
        if (empty($worCode)) return array(false, array(), "下单人编号不能为空");
        if (empty($recData)) return array(false, array(), "入仓单货品不能为空");

        $sname = array("war_code" => $warCode, "wor_code" => $worCode, "data" => $data);
        $recUniqeKey = md5(json_encode($sname));
        $recUniqeData = S($recUniqeKey);
        if (false == $recUniqeData) {
            $recUniqeStr = json_encode($sname);
            S($recUniqeKey, $recUniqeStr, 600);
        } else {
            return array(true, array(), "已有相同的入仓单");
        }

        $recModel = ReceiptDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
        $goodstoredModel = GoodstoredDao::getInstance($warCode);
        $skuModel = SkuDao::getInstance($warCode);
        $isSuccess = true;
        if ($type == 2 && (empty($ecode) || $ecode == null)) return array(false, array(), "订单编号不能为空");
        $addRecData = array(
            "worcode" => $worCode,
            "mark" => empty($mark) ? '' : $mark,
            "status" => 3,
            "type" => $type,
            "ecode" => empty($ecode) ? '' : $ecode,
            "room" => empty($room) ? '' : $room,
            "ctime" => empty($ctime) ? '' : $ctime
        );
        if (!empty($ecode) && $ecode != null) {
            $issetRec = $recModel->queryByEcode($ecode);
            if (!empty($issetRec)) {
                $recCode = $issetRec['rec_code'];
                return array(true, array($recCode), "已经入仓");
            } else {
                $recCode = $recModel->insert($addRecData);
            }
        } else {
            $recCode = $recModel->insert($addRecData);
        }
        $isSuccess = $isSuccess && !empty($recCode);
        foreach ($recData as $recDatum) {
            $skuCode = $recDatum["skucode"];
            $skuCount = $recDatum["skucount"];
            if (empty($skuCode)) return array(false, array(), "sku编号不能为空");
            $bPrice = $recDatum["bprice"];
            $skuData = $skuModel->queryByCode($skuCode);
            $spuCunit = $skuData['spu_cunit'];
            $spuCount = $skuData['spu_count'];
            $count = bcmul($spuCount, $skuCount, 2);
            $spuName = $skuData['spu_name'];
            if (ceil(round(bcmod(($skuCount * 100), ($spuCunit * 100)))) > 0) return array(false, array(), "货品" . $spuName . "数量格式不正确");
            $spuCode = $skuData["spu_code"];
            $supCode = !empty($recDatum["supcode"]) ? $recDatum["supcode"] : $skuData['sup_code'];
            $addGbData = array(
                "status" => 3,//已上架
                "count" => $count,
                "bprice" => $bPrice,
                "spucode" => $spuCode,
                "supcode" => $supCode,
                "skucode" => $skuCode,
                "skucount" => $skuCount,
                "reccode" => $recCode,
            );
            $gbCode = $goodsbatchModel->insert($addGbData);
            $isSuccess = $isSuccess && !empty($gbCode);
            $addGsData = array(
                "init" => $count,
                "count" => $count,
                "bprice" => $bPrice,
                "gbcode" => $gbCode,
                "spucode" => $spuCode,
                "skucode" => $skuCode,
                "skucount" => $skuCount,
                "supcode" => $supCode,
            );
            $gsCode = $goodstoredModel->insert($addGsData);
            $isSuccess = $isSuccess && !empty($gsCode);
            $queryGoods = $goodsModel->queryBySkuCode($skuCode);
            if (!empty($queryGoods)) {
                if ($count == 0) continue;
                $goodsCode = $queryGoods["goods_code"];
                $goodsInit = $queryGoods['goods_init'];
                $goodsCount = $queryGoods['goods_count'];
                $newInit = bcadd($goodsInit, $count, 2);
                $newCount = bcadd($goodsCount, $count, 2);
                $isSuccess = $isSuccess &&
                    $goodsModel->updateCountAndInitByCode($goodsCode, $newInit, $newCount);
            } else {
                $addGoodsData = array(
                    "init" => $count,
                    "count" => $count,
                    "spucode" => $spuCode,
                );
                $isSuccess = $isSuccess &&
                    $goodsModel->insert($addGoodsData);
            }

        }
        $isSuccess = $isSuccess && $recModel->updateFinishTimeByCode($recCode);
        if ($isSuccess) {
            return array(true, array($recCode), "入仓成功");
        } else {
            return array(false, array(), "入仓失败");
        }
    }

    //出库操作[存-1, 出+1]
    public function create_invoice($data)
    {
        $type = $data["type"];
        $warCode = $data["warCode"];//仓库编号
        $worCode = $data["worCode"];//人员编号
        $phone = $data["phone"];
        $address = $data["address"];
        $postal = $data["postal"];
        $receiver = $data['receiver'];//客户名称
        $invData = $data["list"];
        $mark = $data['mark'];
        $room = $data['room'];
        $ecode = $data['ecode'];
        $ctime = $data['ctime'];

        if (empty($type)) return array(false, array(), "出仓单类型不能为空");
        if (empty($warCode)) return array(false, array(), "仓库编号不能为空");
        if (empty($receiver)) return array(false, array(), "下单人不能为空");
        if (empty($worCode)) return array(false, array(), "下单人编号不能为空");
        if (empty($invData)) return array(false, array(), "货品不能为空");


        $invModel = InvoiceDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $igoodsModel = IgoodsDao::getInstance($warCode);
        $goodstoredModel = GoodstoredDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);

        $sname = array("war_code" => $warCode, "wor_code" => $worCode, "data" => $data);
        $recUniqeKey = md5(json_encode($sname));
        $recUniqeData = S($recUniqeKey);
        if (false == $recUniqeData) {
            $recUniqeStr = json_encode($sname);
            S($recUniqeKey, $recUniqeStr, 600);
        } else {
            return array(true, array(), "已有相同的出仓单");
        }

        $isSuccess = true;
        $invAddData = array(
            "status" => 5,//出仓单状态已出仓
            "receiver" => $receiver,//客户名称
            "type" => $type,//出仓单类型
            "mark" => $mark,//出仓单备注
            "worcode" => $worCode,//人员编号
            "phone" => $phone,
            "address" => $address,
            "postal" => $postal,
            "room" => $room,
            "ecode" => $ecode,
            "ctime" => (isset($ctime) ? $ctime : null),
        );//出仓单新增数据
        if (!empty($ecode)) {
            $issetInv = $invModel->queryByEcode($ecode);
            if (!empty($issetRec)) {
                $invCode = $issetInv['inv_code'];
            } else {
                $invCode = $invModel->insert($invAddData);
            }
        } else {
            $invCode = $invModel->insert($invAddData);
        }
        $isSuccess = $isSuccess && !empty($invCode);
        $lessGoods = array();
        foreach ($invData as $invDatum) {
            $skCode = $invDatum['skucode'];
            $skCount = $invDatum['skucount'];
            if (empty($skuCode)) return array(false, array(), "sku编号不能为空");
            if ($skCount == 0) continue;
            $goodsData = $goodsModel->queryBySkuCode($skCode);//获取sku库存
            $goodsCount = $goodsData['goods_count'];//spu库存
            $spuCount = $goodsData['spu_count'];
            $spCode = $goodsData['spu_code'];
            $count = bcmul($spuCount, $skCount, 2);
            if ($goodsCount < $count) {
                $lessGoods[$goodsData['spu_name']] = bcsub($count, $goodsCount, 2) . $goodsData['sku_unit'];
                continue;
            }
            $sprice = !empty($goodsData['spu_sprice']) ? $goodsData['spu_sprice'] : 0;
            $pprice = !empty($goodsData['pro_price']) ? $goodsData['pro_price'] : 0;//spu利润价
            $percent = !empty($goodsData['pro_percent']) ? $goodsData['pro_percent'] : 0;//spu利润率
            $spuCunit = $goodsData['spu_cunit'];
            $spuName = $goodsData['spu_name'];
            if (ceil(round(bcmod(($skCount * 100), ($spuCunit * 100)))) > 0) return array(false, array(), "货品" . $spuName . "数量格式不正确");

            $goodsCode = $goodsData["goods_code"];
            //更新库存
            $addIgoData = array(
                "count" => $count,
                "spucode" => $spCode,
                "sprice" => $sprice,
                "pprice" => $pprice,
                "percent" => $percent,
                "goodscode" => $goodsCode,
                "skucode" => $skCode,
                "skucount" => $skCount,
                "invcode" => $invCode,
            );
            $igoCode = $igoodsModel->insert($addIgoData);
            $isSuccess = $isSuccess && !empty($igoCode);
            $updatedGoodsCount = bcsub($goodsCount, $count, 2);//更新后的SPU

            $isSuccess = $isSuccess &&
                $goodsModel->updateCountByCode($goodsCode, $goodsCount, $updatedGoodsCount);
            $gsCount = $goodstoredModel->queryCountBySkuCode($skCode);
            $gsDataList = $goodstoredModel->queryListBySkuCode($skCode, 0, $gsCount);

            foreach ($gsDataList as $gsData) {
                if (isset($ctime) && $gsData['gb_ctime']>$ctime && $skCount > 0) {
                    venus_db_rollback();
                    $message = "申领时间之前库存不足商品列表" . "<br/>";
                    $message .= $spuName . ":" . $skCount . "<br/>";
                    return array(false,array(),$message);
                }
                $gsCode = $gsData["gs_code"];
                $gsCount = $gsData['gs_count'];
                $gsSkuCount = $gsData['sku_count'];
                $bPrice = $gsData['gb_bprice'];
                if ($gsCount == 0 || $gsSkuCount == 0) continue;
                if ($skCount == 0) break;
                $igsSkuCount = 0;
                if ($skCount <= $gsSkuCount) {
                    $updatedSkuCount = bcsub($gsSkuCount, $skCount, 2);
                    $updatedGoodsCount = bcmul($spuCount, $updatedSkuCount, 2);
                    $igsSkuCount = $skCount;
                } else {
                    $updatedSkuCount = 0;
                    $updatedGoodsCount = bcmul($spuCount, $updatedSkuCount, 2);
                    $igsSkuCount = $gsSkuCount;
                }
                $isSuccess = $isSuccess &&
                    $goodstoredModel->updateCountAndSkuCountByCode($gsCode, $updatedGoodsCount, $updatedSkuCount);
                $addIgsData = array(
                    "count" => bcmul($igsSkuCount, $spuCount, 2),
                    "bprice" => $bPrice,
                    "spucode" => $spCode,
                    "gscode" => $gsCode,
                    "igocode" => $igoCode,
                    "skucode" => $skCode,
                    "skucount" => $igsSkuCount,
                    "invcode" => $invCode,
                );
                $igsCode = $igoodsentModel->insert($addIgsData);
                $isSuccess = $isSuccess && !empty($igsCode);
                $skCount = bcsub($skCount, $igsSkuCount, 2);
            }
            $isSuccess = $isSuccess && ($skCount == 0);
        }

        if (!empty($lessGoods)) {
            //输出库存不足商品的缺货数量
            $message = "以下货品库存不足:" . PHP_EOL;
            foreach ($lessGoods as $spuName => $lessGood) {
                $message = $message . $spuName . ":" . $lessGood . "," . PHP_EOL;
            }
            return array(false, array(), $message);
        } else {
            if ($isSuccess) {
                return array(true, array(), "出仓成功");
            } else {
                return array(false, array(), "出仓失败");
            }
        }


    }

    //创建快进快出
    public function create_virtual($data)
    {
        $recType = $data["recType"];
        $invType = $data["invType"];
        $warCode = $data["warCode"];
        $worCode = $data["worCode"];
        $mark = $data["mark"];
        $ecode = $data["ecode"];
        $recData = $data["list"];
        $phone = $data["phone"];//可无
        $address = $data["address"];//可无
        $postal = $data["postal"];//可无
        $receiver = $data['receiver'];//客户名称
        $room = $data['room'];//
        $ctime = $data['ctime'];//

        $recModel = ReceiptDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
        $goodstoredModel = GoodstoredDao::getInstance($warCode);
        $invModel = InvoiceDao::getInstance($warCode);
        $igoodsModel = IgoodsDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);
        $skuModel = SkuDao::getInstance($warCode);

        if (empty($recType)) return array(false, array(), "入仓单类型不能为空");
        if (empty($invType)) return array(false, array(), "出仓单类型不能为空");
        if (empty($warCode)) return array(false, array(), "仓库编号不能为空");
        if (empty($receiver)) return array(false, array(), "下单人不能为空");
        if (empty($worCode)) return array(false, array(), "下单人编号不能为空");
        if (empty($recData)) return array(false, array(), "无货品");

        $sname = array("war_code" => $warCode, "wor_code" => $worCode, "data" => $data);
        $recUniqeKey = md5(json_encode($sname));
        $recUniqeData = S($recUniqeKey);
        if (false == $recUniqeData) {
            $recUniqeStr = json_encode($sname);
            S($recUniqeKey, $recUniqeStr, 600);
        } else {
            return array(true, array(), "已有相同的快进快出单子");
        }

        if ($recType == 2) {
            if (empty($ecode) || $ecode == null) return array(false, array(), "订单编号不能为空");
        }
        $isSuccess = true;
        if (!empty($ecode) && $ecode != null) {
            $issetRec = $recModel->queryByEcode($ecode);
            if (!empty($issetRec)) {
                $recCode = $issetRec['rec_code'];
            } else {
                $addRecData = array(
                    "worcode" => $worCode,
                    "status" => 3,
                    "type" => $recType,
                    "mark" => $mark,
                    "ecode" => empty($ecode) ? '' : $ecode,
                    "room" => empty($room) ? '' : $room,
                    "ctime" => empty($ctime) ? '' : $ctime
                );
                $recCode = $recModel->insert($addRecData);
                $isSuccess = $isSuccess && !empty($recCode);
                $isSuccess = $isSuccess && $recModel->updateFinishTimeByCode($recCode);
            }
        } else {
            $addRecData = array(
                "worcode" => $worCode,
                "status" => 3,
                "type" => $recType,
                "mark" => $mark,
                "ecode" => empty($ecode) ? '' : $ecode,
                "room" => empty($room) ? '' : $room,
                "ctime" => empty($ctime) ? '' : $ctime
            );
            $recCode = $recModel->insert($addRecData);
            $isSuccess = $isSuccess && !empty($recCode);
            $isSuccess = $isSuccess && $recModel->updateFinishTimeByCode($recCode);
        }

//        if (!$isSuccess) return array(false, array(), "rec");
        $issetInv = $invModel->queryByMark($recCode);
        if (!empty($issetInv)) {
            $invCode = $issetInv['inv_code'];
        } else {
            $invAddData = array(
                "status" => 5,//出仓单状态已出仓
                "receiver" => $receiver,//客户名称
                "type" => $invType,//出仓单类型
                "mark" => $recCode,//出仓单备注
                "worcode" => $worCode,//人员编号
                "phone" => $phone,
                "address" => $address,
                "postal" => $postal,
                "room" => $room,
                "ctime" => empty($ctime) ? '' : $ctime
            );//出仓单新增数据
            $invCode = $invModel->insert($invAddData);
        }
        $isSuccess = $isSuccess && !empty($invCode);
//        if (!$isSuccess) return array(false, array(), "inv");
        foreach ($recData as $recDatum) {
            $skuCount = $recDatum["skucount"];
            $skuCode = $recDatum["skucode"];
            $bPrice = $recDatum["bprice"];
            $sprice = $recDatum['sprice'];
            $pprice = $recDatum['pprice'];
            $percent = $recDatum['percent'];
            $skuData = $skuModel->queryByCode($skuCode);
            $spuCount = $skuData['spu_count'];
            $count = bcmul($spuCount, $skuCount, 2);
            $spuName = $skuData['spu_name'];
            if (empty($skuCode)) return array(false, array(), "sku编号不能为空");
            if (empty($bPrice)) return array(false, array(), $spuName . "成本价不能为空");
            if (empty($sprice)) $sprice = 0;
            if (empty($pprice)) $pprice = 0;
            if (empty($percent)) $percent = 0;
            $spuCunit = $skuData['spu_cunit'];
            if (ceil(round(bcmod(($skuCount * 100), ($spuCunit * 100)))) > 0) return array(false, array(), "货品" . $spuName . "数量格式不正确");

            $spuCode = $skuData["spu_code"];
            $supCode = !empty($recDatum["supcode"]) ? $recDatum["supcode"] : $skuData['sup_code'];

            $addGbData = array(
                "status" => 4,
                "count" => $count,
                "bprice" => $bPrice,
                "spucode" => $spuCode,
                "supcode" => $supCode,
                "skucode" => $skuCode,
                "skucount" => $skuCount,
                "reccode" => $recCode,
            );
            $gbCode = $goodsbatchModel->insert($addGbData);
            $isSuccess = $isSuccess && !empty($gbCode);
            $addGsData = array(
                "init" => $count,
                "count" => $count,
                "bprice" => $bPrice,
                "gbcode" => $gbCode,
                "spucode" => $spuCode,
                "skucode" => $skuCode,
                "skucount" => $skuCount,
                "supcode" => $supCode
            );
            $gsCode = $goodstoredModel->insert($addGsData);
            $isSuccess = $isSuccess && !empty($gsCode);
//            if (!$isSuccess) return array(false, array(), "gs");
            if ($skuCount > 0) {
                $updateGsSkuCount = 0;
                $updateGsCount = 0;
                $isSuccess = $isSuccess &&
                    $goodstoredModel->updateCountAndSkuCountByCode($gsCode, $updateGsCount, $updateGsSkuCount);
                $queryGoods = $goodsModel->queryBySkuCode($skuCode);
                if (!empty($queryGoods)) {
                    $goodsCode = $queryGoods['goods_code'];
                    $goodsInit = $queryGoods['goods_init'];
                    $updateGoodsInit = bcadd($goodsInit, $count, 2);
                    $isSuccess = $isSuccess &&
                        $goodsModel->updateInitByCode($goodsCode, $goodsInit, $updateGoodsInit);
                } else {
                    $addGoodsData = array(
                        "init" => $count,
                        "count" => 0,
                        "skuinit" => $skuCount,
                        "skucount" => 0,
                        "skucode" => $skuCode,
                        "spucode" => $spuCode,
                    );
                    $goodsCode = $goodsModel->insert($addGoodsData);
                }
                $isSuccess = $isSuccess && !empty($goodsCode);
//                if (!$isSuccess) return array(false, array(), "goods");
                $addIgoData = array(
                    "count" => $count,
                    "spucode" => $spuCode,
                    "sprice" => $sprice,
                    "pprice" => $pprice,
                    "percent" => $percent,
                    "goodscode" => $goodsCode,
                    "skucode" => $skuCode,
                    "skucount" => $skuCount,
                    "invcode" => $invCode,
                );
                $igoCode = $igoodsModel->insert($addIgoData);
                $isSuccess = $isSuccess && !empty($igoCode);
//                if (!$isSuccess) return array(false, $addIgoData, "igo");
                $addIgsData = array(
                    "count" => $count,
                    "spucode" => $spuCode,
                    "bprice" => $bPrice,
                    "gscode" => $gsCode,
                    "igocode" => $igoCode,
                    "skucode" => $skuCode,
                    "skucount" => $skuCount,
                    "invcode" => $invCode,
                );
                $igsCode = $igoodsentModel->insert($addIgsData);
                $isSuccess = $isSuccess && !empty($igsCode);
//                if (!$isSuccess) return array(false, $addIgsData, "igs");
            } else {
                $queryGoods = $goodsModel->queryBySkuCode($skuCode);
                if (!empty($queryGoods)) {
                    $goodsCode = $queryGoods['goods_code'];
                } else {
                    $addGoodsData = array(
                        "init" => $count,
                        "count" => 0,
                        "skuinit" => $skuCount,
                        "skucount" => 0,
                        "skucode" => $skuCode,
                        "spucode" => $spuCode,
                    );
                    $goodsCode = $goodsModel->insert($addGoodsData);
                }
                $isSuccess = $isSuccess && !empty($goodsCode);
            }
        }
        $queryCountByIgoods = $igoodsModel->queryCountByInvCode($invCode);
        $queryCountByIgoodsent = $igoodsentModel->queryCountByInvCode($invCode);
        if ($queryCountByIgoods == 0 && $queryCountByIgoodsent == 0) $isSuccess = $isSuccess && $invModel->deleteByCode($invCode);
//        if (!$isSuccess) return array(false, array(), "delinv");
        if ($isSuccess) {
            return array(true, array(), "快进快出操作成功");
        } else {
            return array(false, array(), "快进快出操作失败");
        }
    }
}