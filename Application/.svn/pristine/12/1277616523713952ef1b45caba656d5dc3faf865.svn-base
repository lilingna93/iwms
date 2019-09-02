<?php
namespace Wms\Controller;

use Common\Service\PHPRpcService;
use Think\Controller;
use Think\Exception;
use Wms\Dao\GoodsDao;
use Wms\Dao\GoodstoredDao;
use Wms\Dao\ReceiptDao;
use Wms\Dao\SkuDao;
use Wms\Dao\SpuDao;
use Wms\Dao\WarehouseDao;
use Common\Service\ExcelService;
use Wms\Service\AuthService;
use Wms\Service\SkuService;

class ServiceController extends Controller {

    //正式平台数据接口
    public function api() {
        try {
            header('Access-Control-Allow-Origin:*');
            $api = I("post.service");
            list($module, $class, $method) = venus_decode_api_request($api);
            $class = "{$module}\\Service\\{$class}Service";

            //调试
            $token = I("post.token");
            !empty($token) && AuthService::getInstance()->remotelogin($token,false);

            if (class_exists($class)) {
                list($success, $data, $message) = call_user_func(array(new $class(), $method));
                venus_encode_api_result($api, 0, "", $success, $data, $message);
            } else {
                E("提醒:未知API", 1);
            }
        } catch (Exception $e) {
            venus_encode_api_result("", $e->getCode(), $e->getMessage(), false, "", "");
        }
    }

    //正式小程序数据接口
    public function mapi() {
        try {
            $api = I("post.service");
            list($module, $class, $method) = venus_decode_api_request($api);
            $class = "{$module}\\Service\\{$class}Service";
            if (class_exists($class)) {
                list($success, $data, $message) = call_user_func(array(new $class(), $method));
                venus_encode_api_result($api, 0, "", $success, $data, $message,session_id());
            } else {
                E("提醒:未知API", 1);
            }
        } catch (Exception $e) {
            venus_encode_api_result("", $e->getCode(), $e->getMessage(), false, "", "",session_id());
        }
    }

    //正式平台文件下载接口
    public function file() {
        try {
            header('Access-Control-Allow-Origin:*');
            header("Content-type:application/vnd.ms-excel;charset=gb2312");
            $fileName = I("post.fname");
            $typeName = I("post.tname");
            $saveName = I("post.sname");
            ExcelService::getInstance()->outPut($typeName, $fileName, $saveName);
        } catch (Exception $e) {
            venus_encode_api_result("", $e->getCode(), $e->getMessage(), false, "", "");
        }
    }

    //正式平台文件打包下载接口
    public function filezip() {
        try {
            header('Access-Control-Allow-Origin:*');
            $fileName = I("post.fname");
            $typeName = I("post.tname");
            $saveName = I("post.sname");
            ExcelService::getInstance()->outPutZip($typeName, $fileName, $saveName);
        } catch (Exception $e) {
            venus_encode_api_result("", $e->getCode(), $e->getMessage(), false, "", "");
        }
    }


    public function napi(){
        venus_encode_api_result("api", 0, "", 1, "ok", "message");
    }
    public function testrpc(){
        $result = PHPRpcService::getInstance()->request('5146628b6202954f450e7a76f10e8f6d', "venus.wms.goods.goods.search", array(
            "tCode" => "0",
            "cgCode" => "0",
            "spName" => ""
        ));
        var_dump($result);
    }



}
