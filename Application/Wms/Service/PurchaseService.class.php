<?php

namespace Wms\Service;

use Common\Service\PassportService;
use Common\Service\PHPRpcService;
use Wms\Dao\OrdergoodsDao;
use Wms\Dao\OrderDao;
use Wms\Dao\SkuDao;

class PurchaseService
{
    private static $ORDER_STATUS_CREATE = "1";
    private static $ORDER_STATUS_FINISH = "2";
    private static $ORDER_STATUS_CANCEL = "3";
    private static $ORDER_STATUS_EXAMINECARGO = "4";

    public $uCode;
    public $waCode;
    public $workerWarehouseCode;
    public $uToken;

    function __construct()
    {
        $userData = PassportService::getInstance()->loginUser();
        if (empty($userData) || $userData["type"] !== "oms") {
            venus_throw_exception(110);
        }
        $this->uCode = $userData["user_code"];
        $this->waCode = $userData["warehousecode"];
        $this->uToken = $userData["user_token"];
        $this->workerWarehouseCode = $userData["war_code"];//user所代表的第三方仓库工作人员的仓库编号
    }

    //2.提交采购订单
    public function order_create()
    {

        $morning = "06:00:00";//当天时间早上
        $night = "19:00:00";//当天时间晚上
        $currentTime = date("H:i:s",time());//当前时间 时分秒
        if($currentTime < $morning || $currentTime > $night){
            return array(false,"","下单时间为  6:00 ～ 19:00\n如遇其他问题请与客服联系");
        }

        $post = json_decode($_POST['data'], true);
        $oMark = $post['oMark'];
        $oPlan = $post['oPlan'];
        $goodsList = $post['list'];

        $presentTime = date("Y-m-d",time());//当前时间 年月日
        if($oPlan < $presentTime){
            return array(false,"","送货日期不是合法日期");
        }

        if (empty($oPlan)) {
            venus_throw_exception(1, "送货日期不能为空");
            return false;
        }
        if (empty($goodsList)) {
            venus_throw_exception(1, "sku货品不能为空");
            return false;
        }

        venus_db_starttrans();//启动事务
        $oData = array(
            "ctime" => venus_current_datetime(),
            "pdate" => $oPlan,
            "status" => self::$ORDER_STATUS_CREATE,//已创建
            "mark" => $oMark,
            "sprice" => 0,
            "bprice" => 0,
            "sprofit" => 0,
            "cprofit" => 0,
            "tprice" => 0,
            "warcode" => $this->workerWarehouseCode,
            "ucode" => $this->uCode
        );
        $orderDao = OrderDao::getInstance();
        $orderCode = $orderDao->insert($oData);
        $success = !empty($orderCode);
        if ($success) {
            $totalBprice = 0;//订单总内部采购价
            $totalSprice = 0;//订单总内部销售价
            $totalSprofit = 0;//订单总内部利润金额
            $totalCprofit = 0;//订单客户总利润额
            $totalTprice = 0;//订单总金额
            $skuDao = SkuDao::getInstance($this->waCode);
            foreach ($goodsList as $goodsItem) {
                $skuData = $skuDao->queryByCode($goodsItem['skCode']);
                if($skuData['sup_code'] == "SU00000000000001"){
                    $bPrice = 0;
                }else{
                    $bPrice = $skuData['spu_bprice'];
                }
                $spuCount = $goodsItem['skNum'] * $skuData['spu_count'];
                $gData = array(
                    "count" => $spuCount,
                    "skucode" => $goodsItem['skCode'],
                    "skuinit" => $goodsItem['skNum'],
                    "spucode" => $skuData['spu_code'],
                    "spucount" => $skuData['spu_count'],
                    "sprice" => $skuData['spu_sprice'],
                    "bprice" => $bPrice,
                    "pproprice" => $skuData['profit_price'],
                    "ocode" => $orderCode,
                    "supcode" => $skuData['sup_code'],
                    "warcode" => $this->workerWarehouseCode,
                    "ucode" => $this->uCode
                );
                $sprice = bcmul($skuData['spu_sprice'], $spuCount, 4);
                $bprice = bcmul($skuData['spu_bprice'], $spuCount, 4);
                $totalBprice += $bprice;
                $totalSprice += $sprice;
                $totalSprofit = $totalSprice - $totalBprice;
                $totalCprofit += bcmul($skuData['profit_price'], $spuCount, 4);
                $totalTprice += venus_calculate_sku_price_by_spu($skuData['spu_sprice'], $spuCount, $skuData['profit_price']);

                $success = $success && OrdergoodsDao::getInstance()->insert($gData);
            }
            $success = $success && $orderDao->updatePriceByCode($orderCode, $totalBprice, $totalSprice, $totalSprofit, $totalCprofit, $totalTprice);

        }

        if ($success) {
            venus_db_commit();//提交事务
            $message = "下单成功";
        } else {
            venus_db_rollback();//回滚事务
            $message = "下单失败";
        }
        return array($success, "", $message);


    }

    //采购单列表
    public function order_list()
    {
        $post = json_decode($_POST['data'], true);
        $pageCurrent = $post['pageCurrent'];//当前页码
        $pageSize = 10;//每页显示条数
        $oStatus = $post['oStatus'];

        //当前页码
        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }
        $condition = array();
        if (!empty($oStatus)) {
            $condition['status'] = $oStatus;
        }
        $condition['warcode'] = $this->workerWarehouseCode;
        $OrderDao = OrderDao::getInstance();
        $totalCount = $OrderDao->queryCountByCondition($condition);//获取指定条件的总条数
        $pageLimit = pageLimit($totalCount, $pageCurrent, $pageSize);
        $orderList = $OrderDao->queryListByCondition($condition, $pageLimit['page'], $pageLimit['pSize']);
        if (empty($orderList)) {
            $purchaseList = array(
                "pageCurrent" => 0,
                "pageSize" => 100,
                "totalCount" => 0
            );
            $purchaseList["list"] = array();
        } else {
            $purchaseList = array(
                "pageCurrent" => $pageCurrent,
                "pageSize" => $pageSize,
                "totalCount" => $totalCount
            );
            foreach ($orderList as $index => $orderItem) {
                $purchaseList["list"][$index] = array(
                    "oCode" => $orderItem['order_code'],//订单编号
                    "oCtime" => $orderItem['order_ctime'],//下单时间
                    "oPdate" => $orderItem['order_pdate'],//计划送达日期
                    "oPrice" => $orderItem['order_bprice'],//订单总价
                    "oStatus" => $orderItem['order_status'],//订单状态
                    "oStatusCommn" => venus_order_status_desc($orderItem['order_status'])
                );
            }
        }
        return array(true, $purchaseList, "");
    }

    //采购单详情
    public function order_detail()
    {
        $post = json_decode($_POST['data'], true);
        $oCode = $post['oCode'];
        if (empty($oCode)) {
            venus_throw_exception(1, "订单编号不能为空");
            return false;
        }
        $pnumber = 0;
        $pSize = 10000;
        $orderData = OrderDao::getInstance()->queryByCode($oCode);
        $goodsList = OrdergoodsDao::getInstance()->queryListByOrderCode($oCode, $pnumber, $pSize);
        $detailList = array();
        if ($orderData) {
            $detailList = array(
                "oCode" => $orderData['order_code'],
                "oSprice" => ($orderData['order_tprice']==intval($orderData['order_tprice']))?intval($orderData['order_tprice']):round($orderData['order_tprice'],2),
                "oBprice" => $orderData['order_bprice'],
                "oPprice" => $orderData['order_sprofit'],
                "oTime" => $orderData['order_ctime'],
                "oPlan" => $orderData['order_pdate'],
                "oStatus" => $orderData['order_status'],
                "oStatusCommn" => venus_order_status_desc($orderData['order_status'])
            );
            foreach ($goodsList as $index => $goodsItem) {
//                $percent = $goodsItem["pro_percent"];
                $profitPrice = $goodsItem["profit_price"];
                $sprice = $goodsItem["spu_sprice"];
                $count = $goodsItem["spu_count"];
                $totalCount = $goodsItem['goods_count'];
                $skPrice = venus_calculate_sku_price_by_spu($sprice, $count, $profitPrice);
                $totalPrice = venus_calculate_sku_price_by_spu($sprice, $totalCount, $profitPrice);

                $detailList["list"][$index] = array(
                    "skCode" => $goodsItem['sku_code'],
                    "spName" => $goodsItem['spu_name'],
                    "skPrice" => ($skPrice==intval($skPrice))?intval($skPrice):round($skPrice,2),
                    "totalPrice" => ($totalPrice==intval($totalPrice))?intval($totalPrice):round($totalPrice,2),
                    "skNum" => ($goodsItem['sku_count']==intval($goodsItem['sku_count']))?intval($goodsItem['sku_count']):round($goodsItem['sku_count'],2),
                    "skBrand" => $goodsItem['spu_brand'],
                    "skUnit" => $goodsItem['sku_unit'],
                    "skNorm" => $goodsItem['sku_norm'] . " × {$count}" . $goodsItem["spu_unit"],//规格中增加表示规格数量的信息
                    "skImg" => $goodsItem['spu_img']
                );

            }
        }
        return array(true, $detailList, "");
    }

    //修改采购单状态(完成订单)
    public function order_status_update()
    {
		
		$morning = "06:00:00";//当天时间早上
        $night = "19:00:00";//当天时间晚上
        $currentTime = date("H:i:s",time());//当前时间
        if($currentTime < $morning || $currentTime > $night){
            return array(false,""," 6:00 ～ 19:00可修改订单\n如遇其他问题请与客服联系");
        }
        $post = json_decode($_POST['data'], true);
        $oCode = $post['oCode'];
        $oStatus = $post['oStatus'];

        if (empty($oCode)) {
            venus_throw_exception(1, "订单编号不能为空");
            return false;
        }

        if (empty($oStatus)) {
            venus_throw_exception(1, "订单状态不能为空");
            return false;
        }

        if ($oStatus == self::$ORDER_STATUS_FINISH) {
            $orderData = OrderDao::getInstance()->queryByCode($oCode);//获取订单信息
            $goodsList = OrdergoodsDao::getInstance()->queryListByOrderCode($oCode, $page = 0, $count = 10000);//获取订单里的所有货品数据
            foreach ($goodsList as $index => $goodsItem) {
                $count = round(bcmul($goodsItem['sku_count'], $goodsItem['spu_count'], 3), 2);
                $msg = array(
                    'spCode' => $goodsItem["spu_code"],
                    'spName' => $goodsItem["spu_name"],
                    'spAbname' => $goodsItem["spu_abname"],
                    'spType' => $goodsItem["spu_type"],
                    'spSubtype' => $goodsItem["spu_subtype"],
                    'spStoretype' => $goodsItem["spu_storetype"],
                    'spBrand' => $goodsItem["spu_brand"],
                    'spFrom' => $goodsItem["spu_from"],
                    'spNorm' => $goodsItem["spu_norm"],
                    'spUnit' => $goodsItem["spu_unit"],
                    'spMark' => $goodsItem["spu_mark"],
                    'spCunit' => $goodsItem["spu_cunit"],
                    'skCode' => $goodsItem["sku_code"],
                );
                $list[$index] = array(//入仓数据
                    "skCode" => $goodsItem['sku_code'],
                    "skCount" => $count,
                    "spCode" => $goodsItem['spu_code'],
                    "spBprice" => bcadd($goodsItem['spu_sprice'],$goodsItem['profit_price']),
                    "supCode" => "SU00000000000001",
                    "count" => $count,
                    "spCunit" => $goodsItem['spu_cunit'],
                    "msg" => $msg
                );
            }
            $oMark = $orderData['order_mark'] . "订单编号:" . $orderData['order_code'];
            $ecode = $orderData['order_code'];
			$projectTeam = array("WA100000","WA100001","WA100009","WA100019","WA100020");
			if(in_array($this->workerWarehouseCode,$projectTeam)){
				$result = PHPRpcService::getInstance()->request($this->uToken, "venus.wms.receipt.receipt.create", array(
					"isFast" => 1,
					"list" => $list,
					"mark" => $oMark,
					"ecode"=> $ecode
				));
				if ($result['success']==true) {
					$recRes = true;
				} else {
					$recRes = false;
					$message=$result['msg'];
				}
			}else{
                $recRes = true;
            }
            
        } else {
            $recRes = true;
        }
        if ($recRes) {
            $otCode = OrderDao::getInstance()->queryByCode($oCode);
            if(empty($otCode['ot_code'])){
                $updateOrderStatus = OrderDao::getInstance()->updateStatusByCode($oCode, $oStatus);
                $updateWorderStatus = OrderDao::getInstance()->updateWStatusByCode($oCode, 4);
            }else{
                $success = false;
                $message = "此订单正在处理中，如有问题，请联系客服";
                return array($success, "", $message);
            }
            if ($updateOrderStatus && $updateWorderStatus) {
                $this->updatePrice($oCode);//更新订单相关的所有价格
                $success = true;
                $message = "更新订单状态成功";
            } else {
                $success = false;
                $message = "更新订单状态失败";
            }
            return array($success, "", $message);
        } else {
            return array(false, "", $message);
        }
    }

    //采购单分单列表
    public function order_split_search()
    {
        $post = json_decode($_POST['data'], true);
        $oCode = $post['oCode'];
        if (empty($oCode)) {
            venus_throw_exception(1, "订单编号不能为空");
            return false;
        }
        $pnumber = 0;
        $pSize = 10000;
        $orderGoodsList = OrdergoodsDao::getInstance()->queryListByOrderCode($oCode, $pnumber, $pSize);
        $codes = array();
        if ($orderGoodsList) {
            foreach ($orderGoodsList as $index => $orderGoodsItem) {
                $skPrice = venus_calculate_sku_price_by_spu($orderGoodsItem['spu_sprice'], $orderGoodsItem['spu_count'], $orderGoodsItem['profit_price']);
                $totalPrice = venus_calculate_sku_price_by_spu($orderGoodsItem['spu_sprice'], $orderGoodsItem['goods_count'], $orderGoodsItem['profit_price']);

                if (!empty($orderGoodsItem['sup_code'])) {
                    $count = $orderGoodsItem['spu_count'];
                    $lists = array(
                        "goodscode" => $orderGoodsItem['goods_code'],
                        "skCode" => $orderGoodsItem['sku_code'],
                        "spName" => $orderGoodsItem['spu_name'],
                        "skNum" => ($orderGoodsItem['sku_count']==intval($orderGoodsItem['sku_count']))?intval($orderGoodsItem['sku_count']):round($orderGoodsItem['sku_count'],2),
                        "skBrand" => $orderGoodsItem['spu_brand'],
                        "skNorm" => $orderGoodsItem['sku_norm'] . " × {$count}" . $orderGoodsItem["spu_unit"],//规格中增加表示规格数量的信息
                        "skUnit" => $orderGoodsItem['sku_unit'],
                        "spCunit" => $orderGoodsItem['spu_cunit'],
                        "skPrice" => ($skPrice==intval($skPrice))?intval($skPrice):round($skPrice,2),
                        "totalPrice" => ($totalPrice==intval($totalPrice))?intval($totalPrice):round($totalPrice,2),
                        "spCount" => $orderGoodsItem['spu_count'],
                        "skImg" => $orderGoodsItem['spu_img']
                    );
                }

                if (in_array($orderGoodsItem['sup_code'], $codes)) {
                    $results[$orderGoodsItem['sup_code']]['list'][] = $lists;
                } else {
                    $results[$orderGoodsItem['sup_code']]['status'] = $orderGoodsItem['goods_status'];
                    $results[$orderGoodsItem['sup_code']]['statusname'] = venus_ordergoods_status_desc($orderGoodsItem['goods_status']);
                    $results[$orderGoodsItem['sup_code']]['supName'] = $orderGoodsItem['sup_name'];
                    $results[$orderGoodsItem['sup_code']]['list'][] = $lists;
                    $codes[] = $orderGoodsItem['sku_code'];
                }
            }
            $data = array();
            foreach ($results as $keys => $item) {
                $data[] = $item;
            }

        }
        return array(true, $data, "");
    }

    //确认收货
    public function order_goods_receipt()
    {
        $post = json_decode($_POST['data'], true);
        $goodsList = $post['list'];
        $oCode = $post['oCode'];
        $oStatus = self::$ORDER_STATUS_EXAMINECARGO;
        if (empty($goodsList)) {
            venus_throw_exception(1, "货品编号及数量不能为空");
            return false;
        }
        if (empty($oCode)) {
            venus_throw_exception(1, "订单编号不能为空");
            return false;
        }

        foreach ($goodsList as $goodsItem) {
            $goodsData = OrdergoodsDao::getInstance()->queryByCode($goodsItem['goodsCode']);
            $goodsCount = $goodsItem['skuCount'] * $goodsData['spu_count'];//更改货品数量
            $goodsCountUpd = OrdergoodsDao::getInstance()->updateCountByCode($goodsItem['goodsCode'], $goodsItem['skuCount'], $goodsCount);
        }

        if ($goodsCountUpd) {
            $oStatusUpd = OrderDao::getInstance()->updateStatusByCode($oCode, $oStatus);//更新订单的状态
            $this->updatePrice($oCode);//更新订单相关的所有价格
            $success = true;
            $message = "确认收货成功";
        } else {
            $success = false;
            $message = "确认收货失败";
        }
        return array($success, "", $message);
    }

    //删除订单(修改订单,此订单将被删除，数据会恢复到购物车)
    public function order_delete()
    {
		$morning = "06:00:00";//当天时间早上
        $night = "19:00:00";//当天时间晚上
        $currentTime = date("H:i:s",time());//当前时间
        if($currentTime < $morning || $currentTime > $night){
            return array(false,""," 6:00 ～ 19:00可取消订单\n如遇其他问题请与客服联系");
        }
        $post = json_decode($_POST['data'], true);
        $oCode = $post['oCode'];//订单编号
        if (empty($oCode)) {
            venus_throw_exception(1, "订单编号不能为空");
            return false;
        }
        $otCode = OrderDao::getInstance()->queryByCode($oCode);
        if(empty($otCode['ot_code'])){
            $orderDel = OrderDao::getInstance()->deleteByCode($oCode);
        }else{
            $success = false;
            $message = "此订单正在处理中，如有问题，请联系客服";
            return array($success, "", $message);
        }

        if ($orderDel) {
            $success = true;
            $message = "删除订单成功";
        } else {
            $success = false;
            $message = "删除订单失败";
        }
        return array($success, "", $message);
    }

    //更新订单相关金额
    private function updatePrice($oCode)
    {
        $goodsList = OrdergoodsDao::getInstance()->queryListByOrderCode($oCode, $page = 0, $count = 10000);//获取订单里的所有货品数据
        $totalBprice = 0;//订单总内部采购价
        $totalSprice = 0;//订单总内部销售价
        $totalSprofit = 0;//订单总内部利润金额
        $totalCprofit = 0;//订单客户总利润额
        $totalTprice = 0;//订单总金额
        foreach ($goodsList as $index => $goodsItem) {
            $bprice = bcmul($goodsItem['spu_bprice'], $goodsItem['goods_count'], 4);
            $sprice = bcmul($goodsItem['spu_sprice'], $goodsItem['goods_count'], 4);
            $totalBprice += $bprice;
            $totalSprice += $sprice;
            $totalSprofit = $totalSprice - $totalBprice;
            $totalCprofit += bcmul($goodsItem['profit_price'], $goodsItem['goods_count'], 4);
            $totalTprice += venus_calculate_sku_price_by_spu($goodsItem['spu_sprice'], $goodsItem['goods_count'], $goodsItem['profit_price']);
        }
        $updatePrice = OrderDao::getInstance()->updatePriceByCode($oCode, $totalBprice, $totalSprice, $totalSprofit, $totalCprofit, $totalTprice);
    }
}
