<?php

namespace Wms\Service;

use Common\Service\PassportService;
use Common\Service\PHPRpcService;
use Wms\Dao\SkuDao;
use Wms\Dao\SpuDao;

class ApplyService
{
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
    //库存商品列表
    public function goods_list()
    {
        $projectTeam = array("WA100000", "WA100001","WA100009","WA100019","WA100020");
        if(!in_array($this->workerWarehouseCode,$projectTeam)) {
            return array(false, "", "该功能暂时不开放！");
        }
        $post = json_decode($_POST['data'], true);
        $tCode = $post['tCode'];
        $cgCode = $post['cgCode'];
        $spName = $post['spName'];
        $toKen = PassportService::loginUser();
        $goodList = PHPRpcService::getInstance()->request($toKen['user_token'], "venus.wms.goods.goods.search", array(
            "tCode" => $tCode,
            "cgCode" => $cgCode,
            "spName" => $spName
        ));
        if (!empty($goodList['data']['list'])) {
            $goodList = $goodList['data'];
        } else {
            $goodList = array(
                "pageCurrent" => $goodList['data']['pageCurrent'],
                "pageSize" => $goodList['data']['pageSize'],
                "totalCount" => $goodList['data']['totalCount']
            );
            $goodList["list"] = array();
        }
        return array(true, $goodList, "");
    }

    //申领订单
    public function order_create()
    {
        $post = json_decode($_POST['data'], true);
        $mark = $post['mark'];
        $room = $post['room'];
        $spuList = $post['list'];
        $invSkuList = array();
        foreach ($spuList as $spuItem) {
            $list = $spuItem;
            $list['skCode'] = SkuDao::getInstance()->querySkuCodeBySpuCodeToIwms($spuItem['spCode']);
            $list['skCount'] = $spuItem['count'];
            $list['spCunit'] = SpuDao::getInstance()->queryByCode($spuItem['spCode'])['spu_cunit'];
            $invSkuList[] = $list;
        }

        if (empty($list)) {
            venus_throw_exception(1, "spu不能为空");
            return false;
        }

        $toKen = PassportService::loginUser();
        $applyStatusResult = PHPRpcService::getInstance()->request($toKen['user_token'], "venus.wms.invoice.invoice.create", array(
            "room" => $room,
            "mark" => $mark,
            "isFast" => 1,
            "list" => $invSkuList
        ));

        if ($applyStatusResult['success'] == true) {
            $message = "申领成功";
        } else {
            $message = explode(":",$applyStatusResult['msg'])[1];
        }
        return array($applyStatusResult['success'], "", $message);
    }

    //申领单列表
    public function order_list()
    {
        $projectTeam = array("WA100000","WA100001","WA100009","WA100019","WA100020");
        if(!in_array($this->workerWarehouseCode,$projectTeam)) {
            return array(false, "", "该功能暂时不开放！");
        }
        $post = json_decode($_POST['data'], true);
        $pageCurrent = $post['pageCurrent'];
        $pageSize = $post['pageSize'];
        $status = $post['status'];

        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }

        $toKen = PassportService::loginUser();
        $orderList = PHPRpcService::getInstance()->request($toKen['user_token'], "venus.wms.invoice.invoice.search", array(
            "pageCurrent" => $pageCurrent,
            "pageSize" => $pageSize,
            "status" => $status
        ));

        if (!empty($orderList['data']['list'])) {
            $orderList = $orderList['data'];
        } else {
            $orderList = array(
                "pageCurrent" => $orderList['data']['pageCurrent'],
                "pageSize" => $orderList['data']['pageSize'],
                "totalCount" => $orderList['data']['totalCount']
            );
            $orderList["list"] = array();
        }
        return array(true, $orderList, "");

    }

    //申领单详情
    public function order_detail()
    {
        $post = json_decode($_POST['data'], true);
        $invCode = $post['invCode'];
        if (empty($invCode)) {
            venus_throw_exception(1, "订单编号不能为空");
            return false;
        }

        $toKen = PassportService::loginUser();
        $invList = PHPRpcService::getInstance()->request($toKen['user_token'], "venus.wms.invoice.invoice.search", array(
            "code" => $invCode
        ));

        $spuList = PHPRpcService::getInstance()->request($toKen['user_token'], "venus.wms.invoice.invoice.detail", array(
            "invCode" => $invCode
        ));

        $invDataItem = $invList['data']['list'][0];
        if ($invDataItem) {
            $orderDetail = array(
                "invCode" => $invDataItem['invCode'],
                "invMark" => $invDataItem['invMark'],
                "invCtime" => $invDataItem['invCtime'],
                "invStatus" => $invDataItem['invStatus'],
                "invStatMsg" => $invDataItem['invStatMsg'],
                "list" => $spuList['data']['list']
            );
        } else {
            $orderDetail["list"] = array();
        }
        return array(true, $orderDetail, "");
    }

    //取消申领
    public function order_cancel()
    {
        $post = json_decode($_POST['data'], true);
        $invCode = $post['invCode'];
        if (empty($invCode)) {
            venus_throw_exception(1, "订单编号不能为空");
            return false;
        }

        $toKen = PassportService::loginUser();
        $orderCancelResult = PHPRpcService::getInstance()->request($toKen['user_token'], "venus.wms.invoice.invoice.delete", array(
            "invCode" => $invCode
        ));

        if ($orderCancelResult['success'] == true) {
            $message = "订单取消成功";
        } else {
            $message = "订单取消失败";
        }
        return array($orderCancelResult['success'], "", $message);
    }


}