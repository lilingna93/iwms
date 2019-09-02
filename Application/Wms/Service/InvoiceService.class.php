<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2018/7/27
 * Time: 10:13
 */

namespace Wms\Service;


use Common\Service\ExcelService;
use Common\Service\PassportService;
use http\Exception;
use Wms\Dao\GoodsbatchDao;
use Wms\Dao\GoodsDao;
use Wms\Dao\GoodstoredDao;
use Wms\Dao\IgoodsDao;
use Wms\Dao\IgoodsentDao;
use Wms\Dao\InvoiceDao;
use Wms\Dao\SkuDao;
use Wms\Dao\SpuDao;
use Wms\Dao\WarehouseDao;
use Wms\Dao\WorkerDao;

class InvoiceService
{
    static private $INVOICE_STATUS_FORECAST = "1";//出仓单已预报状态
    static private $INVOICE_STATUS_CREATE = "2";//出仓单已创建状态
    static private $INVOICE_STATUS_PICK = "3";//inspection出仓单已拣货状态
    static private $INVOICE_STATUS_INSPECTION = "4";//inspection出仓单已验货状态
    static private $INVOICE_STATUS_FINISH = "5";//inspection出仓单已出仓状态
    static private $INVOICE_STATUS_RECEIPT = "6";//出仓单已收货状态
    static private $INVOICE_STATUS_CANCEL = "7";//出仓单已取消状态

    static private $GOODSBATCH_STATUS_CREATE = "1";//货品批次创建状态
    static private $GOODSBATCH_STATUS_INSPECTION = "2";//货品批次验货状态
    static private $GOODSBATCH_STATUS_PUTAWAY = "3";//Putaway货品批次上架状态
    static private $GOODSBATCH_STATUS_FINISH = "4";//货品批次使用完状态

    static private $INVOICE_CREATE_TYPE_HANDWORK = "1";//手工创建
    static private $INVOICE_CREATE_TYPE_API = "2";//API创建
    static private $INVOICE_CREATE_TYPE_FILE = "3";//文件导入

    public $warCode;
    public $worcode;

    public function __construct()
    {
        //获取登录用户信息
        $workerData = PassportService::getInstance()->loginUser();
        if (empty($workerData)) {
            venus_throw_exception(110);
        }

        $this->warCode = $workerData["war_code"];//仓库编号
        $this->worcode = $workerData["wor_code"];//人员编号
        $this->worRname = $workerData["wor_rname"];//人员名称
        $this->warAddress = $workerData["war_address"];//仓库地址
        $this->warPostal = $workerData["war_postal"];//仓库邮编
        $this->worPhone = $workerData["wor_phone"];//手机号
    }

    /**
     * @param $param
     * @return array|bool
     * 创建出仓单／获取sku
     */
    public function invoice_get_sku($param)
    {

        $warCode = $this->warCode;
        if (!isset($param)) {
            $param = $_POST;
        }
        $data = array();
        if (empty($param['data']['sku'])) {
            $message = "sku为空";
            venus_throw_exception(1, $message);
            return false;
        } else {

            $goodsModel = GoodsDao::getInstance($warCode);

            $sku = trim($param['data']['sku']);
            $type = substr($sku, 0, 2);
            if ($type == "SK") {
                $queryGoodsData = $goodsModel->queryBySkuCode($sku);
                $spuCount = intval($queryGoodsData['spu_count']);
                $goodsCount = ($queryGoodsData['goods_count'] == intval($queryGoodsData['goods_count'])) ? intval($queryGoodsData['goods_count']) : $queryGoodsData['goods_count'];
                $spuData = array(
                    "skName" => $queryGoodsData['spu_name'],
                    "skCode" => $queryGoodsData['sku_code'],
                    "skNorm" => $queryGoodsData['sku_norm'],
                    "skUnit" => $queryGoodsData['sku_unit'],
                    "spCode" => $queryGoodsData['spu_code'],
                    "spCount" => $spuCount,
                    "spUnit" => $queryGoodsData['spu_unit'],
                    "spCunit" => $queryGoodsData['spu_cunit'],
                    "goods" => $goodsCount,
                    "mark" => $queryGoodsData['spu_mark']
                );
                $data['list'][] = $spuData;
            } else {
                //去除由于输入法造成的字符串含单引号问题
                $spName = str_replace("'", "", $sku);
                //用拼音字典搜索spu名字
                if (!empty($spName) && preg_match("/^[a-z]/i", $spName)) {
                    $cond['abname'] = $spName;
                }
                //用中文搜索spu名字
                if (!empty($spName) && !preg_match("/^[a-z]/i", $spName)) {//SPU名称
                    $cond["%name%"] = $spName;
                }
                //获取仓库满足条件的商品列表
                $goodsDataList = $goodsModel->queryListByCondition($cond);
                foreach ($goodsDataList as $goodsData) {
                    $spuData = array(
                        "skName" => $goodsData['spu_name'],
                        "skCode" => $goodsData['sku_code'],
                        "skNorm" => $goodsData['sku_norm'],
                        "skUnit" => $goodsData['sku_unit'],
                        "spCode" => $goodsData['spu_code'],
                        "spCount" => $goodsData['spu_count'],
                        "spUnit" => $goodsData['spu_unit'],
                        "spCunit" => $goodsData['spu_cunit'],
                        "goods" => $goodsData['goods_count'],
                        "mark" => $goodsData['spu_mark']
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
//     * @param $param
//     * @return array|bool
//     * 创建出仓单
//     */
//    public function invoice_create($param)
//    {
//
//        if (!isset($param)) {
//            $param = $_POST;
//            $type = self::$INVOICE_CREATE_TYPE_HANDWORK;//pc端，手工记账
//        } else {
//            $type = self::$INVOICE_CREATE_TYPE_API;//小程序端,api
//        }
//
//        $warCode = $this->warCode;//仓库编号
//        $worCode = $this->worcode;//人员编号
//
//        $list = $param['data']['list'];//出仓单货品列表
//        //送达时间->备注
//        if (!empty($param['data']['mark'])) {
//            $mark = $param['data']['mark'];
//        }
//        if (!empty($param['data']['room'])) {
//            $room = $param['data']['room'];//客户餐厅名称
//        }
//
//        $receiver = $this->worRname;//客户名称
//        $phone = $this->worPhone;//客户手机号
//        $address = $this->warAddress;//客户地址
//        $postal = $this->warPostal;//客户邮编
//
//        $understockArr = array();//库存不足商品列表
//
//        //快速入仓
//        if (!empty($param['data']['isFast'])) {
//            $isFast = $param['data']['isFast'];
//        } else {
//            $isFast = 0;
//        }
//
//        $data = array();//返回数据声明
//        //声明所需要用的Model及服务
//        $invModel = InvoiceDao::getInstance($warCode);
//        $goodsModel = GoodsDao::getInstance($warCode);
//        $spuModel = SpuDao::getInstance($warCode);
//        $skuModel = SkuDao::getInstance($warCode);
//        $igoodsModel = IgoodsDao::getInstance($warCode);
//        $goodstoredModel = GoodstoredDao::getInstance($warCode);
//        $goodsbatchModel = GoodsbatchDao::getInstance($warCode);
//        $igoodsentModel = IgoodsentDao::getInstance($warCode);
//
//        venus_db_starttrans();//开启事务
//
//        //快速出仓出仓单状态为完成，正常出仓出仓单状态为创建
//        if ($isFast != 1) {
//            $invStatus = self::$INVOICE_STATUS_CREATE;
//        } else {
//            $invStatus = self::$INVOICE_STATUS_FINISH;
//        }
//        $invAddData = array(
//            "status" => $invStatus,//出仓单状态
//            "receiver" => $receiver,//客户名称
//            "phone" => $phone,//客户手机号
//            "address" => $address,//客户地址
//            "postal" => $postal,//客户邮编
//            "type" => $type,//出仓单类型
//            "mark" => $mark,//出仓单备注
//            "worcode" => $worCode,//人员编号
//            "room" => $room,//人员编号
//        );//出仓单新增数据
//        $invCode = $invModel->insert($invAddData);//出仓单创建操作
//
//        foreach ($list as $value) {
//            if (empty($value['spCode'])) {
//                $message = "出仓单spu编号不能为空";
//                venus_throw_exception(1, $message);
//                return false;
//            }
//            if (empty($value['count'])) {
//                $message = "出仓单货品spu总数量不能为空";
//                venus_throw_exception(1, $message);
//                return false;
//            }
//            if (empty($value['spCunit'])) {
//                venus_throw_exception(1, "spu最小计量单位不能为空");
//                return false;
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
//            if (empty($value['skCode'])) {
//                $skCode = SkuDao::getInstance()->querySkuCodeBySpuCodeToIwms($value['spCode']);
//            } else {
//                $skCode = $value['skCode'];
//            }
//            if (empty($skCode)) {
//                $message = "出仓单sku编号不能为空";
//                venus_throw_exception(1, $message);
//                return false;
//            }
//            if (empty($value['skCount'])) {
//                $message = "出仓单货品sku数量不能为空";
//                venus_throw_exception(1, $message);
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
//            $goodsData = $goodsModel->queryBySpuCode($value['spCode']);//获取spu库存
//            $goodsCount = $goodsData['goods_count'];//spu库存
//            if (!empty($goodsData['spu_sprice'])) {
//                $sprice = $goodsData['spu_sprice'];//spu当前销售价
//            } else {
//                $sprice = 0;
//            }
//            if (!empty($goodsData['pro_price'])) {
//                $pprice = $goodsData['pro_price'];//spu利润价
//            } else {
//                $pprice = 0;
//            }
//            if (!empty($goodsData['pro_percent'])) {
//                $percent = $goodsData['pro_percent'];//spu利润率
//            } else {
//                $percent = 0;
//            }
//
//            //出仓单货品数量小于等于库存
//            if ($value['count'] <= $goodsCount) {
//                $igoodsAddData = array(
//                    "count" => $value['count'],//spu总数量
//                    "spucode" => $value['spCode'],//spu编号
//                    "sprice" => $sprice,//spu当前销售价
//                    "pprice" => $pprice,//spu当前利润
//                    "goodscode" => $goodsData['goods_code'],//库存编号
//                    "percent" => $percent,//spu当前利润率
//                    "skucode" => $skCode,//sku编号
//                    "skucount" => $value['skCount'],//sku数量
//                    "invcode" => $invCode,//所属出仓单单号
//                );
//
//                $igoodsCode = $igoodsModel->insert($igoodsAddData);//创建发货清单
//                if (!$igoodsCode) {
//                    venus_db_rollback();
//                    venus_throw_exception(2, "创建发货清单失败");
//                    return false;
//                }
//
//                //创建状态产生批次库位及库存变化
//                $goodstoredList = $goodstoredModel->queryListBySkuCode($skCode);//指定商品的库存货品批次货位列表数据
//                $igoodsentDataListOne = $this->branch_goodstored($goodstoredList, $value['count'], $igoodsCode, $value['spCode'], $invCode);//调用出仓批次方法
//                $igoodsentDataList = array();
//                if ($igoodsentDataListOne["sentNum"] < $value['count']) {
//                    $goodstoredSpuList = $goodstoredModel->queryListNotSkuBySpuCode($value['spCode'], $skCode, 0, 100000);//指定商品的库存货品批次货位列表数据
//                    $igoodsentDataListTwo = $this->branch_goodstored($goodstoredSpuList, $value['count'] - $igoodsentDataListOne["sentNum"], $igoodsCode, $value['spCode'], $invCode);
//                    $igoodsentData = $igoodsentDataListOne + $igoodsentDataListTwo;
//                } else {
//                    $igoodsentData = $igoodsentDataListOne;
//                }
//                foreach ($igoodsentData as $igoodsentDatum) {
//                    if (is_array($igoodsentDatum)) {
//                        $goodsoredCount = $igoodsentDatum['remaining'];
//                        if ($goodsoredCount == 0) {
//                            $gbCode = $goodstoredModel->queryByCode($igoodsentDatum['gscode'])['gb_code'];
//                            $uptGb = $goodsbatchModel->updateStatusByCode($gbCode, self::$GOODSBATCH_STATUS_FINISH);//此批次库存全发完，批次状态改为已用完
//                            if (!$uptGb) {
//                                venus_db_rollback();
//                                venus_throw_exception(2, "修改货品批次状态失败");
//                                return false;
//                            }
//                        }
//                        $uptGsSpuCount = $goodstoredModel->updateByCode($igoodsentDatum['gscode'], $goodsoredCount);//修改发货库存批次剩余数量
//                        $gsSkuInfo = $goodstoredModel->queryByCode($igoodsentDatum['gscode']);
//                        $gsSkuCount = $gsSkuInfo['sku_count'];
//                        if ($gsSkuCount < $igoodsentDatum['skucount']) {
//                            $spName = $goodsData['spu_name'];
//                            if (!array_key_exists($spName, $understockArr)) {
//                                $understockArr[$spName] = $goodsCount . "/" . $gsSkuInfo['sku_unit'];
//                            }
//                        } else {
//                            $uptGsSkuCount = $goodstoredModel->updateSkuCountByCode($igoodsentDatum['gscode'], $gsSkuCount - $igoodsentDatum['skucount']);//减少发货库存批次sku数量
//                            $igoodsentCode = $igoodsentModel->insert($igoodsentDatum);//创建发货批次
//                            if (!$uptGsSpuCount || !$uptGsSkuCount) {
//                                venus_db_rollback();
//                                venus_throw_exception(2, "修改库存批次失败");
//                                return false;
//                            }
//                            if (!$igoodsentCode) {
//                                venus_db_rollback();
//                                venus_throw_exception(2, "创建发货批次失败");
//                                return false;
//                            }
//                        }
//                    }
//                }
//
//                $newCountGoods = $goodsCount - $value['count'];//新库存
//                $uptGoods = $goodsModel->updateCountByCode($goodsData['goods_code'], $goodsData['goods_count'], $newCountGoods);//修改库存
//                if (!$uptGoods) {
//                    venus_db_rollback();
//                    venus_throw_exception(2, "修改库存失败");
//                    return false;
//                }
//
//            } else {
//                $skuInfo = $skuModel->queryByCode($skCode);
//                //出仓单货品数量大于库存，计入统计库存不足数组
//                $spName = $goodsData['spu_name'];
//                if (!array_key_exists($spName, $understockArr)) {
//                    $understockArr[$spName] = $goodsCount . "/" . $skuInfo['sku_unit'];
//                }
//            }
//        }
//        if (!empty($understockArr)) {
//            venus_db_rollback();
//            $message = "库存不足商品库存列表" . PHP_EOL;
//            foreach ($understockArr as $spuName => $spuCount) {
//                $message .= $spuName . ":" . $spuCount . PHP_EOL;
//            }
//
//            venus_throw_exception(5, $message);
//            return false;
//
//        } else {
//            venus_db_commit();
//            $success = true;
//            $message = '';
//            return array($success, $data, $message);
//        }
//
//    }

    /**
     * @return array
     * 出仓单管理
     */
    public
    function invoice_search($param)
    {
        $warCode = $this->warCode;
        if (!isset($param)) {
            $param = $_POST;
        }
        $data = array();
        $stime = $param['data']['stime'];//开始时间
        $etime = $param['data']['etime'];//结束时间
        $status = $param['data']['status'];//状态
        $type = $param['data']['type'];//状态
        $pageCurrent = $param['data']['pageCurrent'];//当前页数
        $invCode = $param['data']['code'];//出仓单单号

        $clause = array();
        if (empty($pageCurrent)) {
            $pageCurrent = 0;//当前页数
        }
        if (!empty($stime)) {
            $clause['sctime'] = $stime;//开始时间
        }
        if (!empty($etime)) {
            $clause['ectime'] = $etime;//结束时间
        }
        if (!empty($status)) $clause['status'] = $status;//出仓单状态
        if (!empty($invCode)) $clause['code'] = $invCode;//出仓单单号
        if (!empty($type)) $clause['type'] = $type;//出仓单类型

        $invModel = InvoiceDao::getInstance($warCode);//出仓单model

        $totalCount = $invModel->queryCountByCondition($clause);//符合条件的出仓单个数
        $pageLimit = pageLimit($totalCount, $pageCurrent);//获取分页信息
        $invDataList = $invModel->queryListByCondition($clause, $pageLimit['page'], $pageLimit['pSize']);//符合条件的出仓单列表

        $data = array(
            "pageCurrent" => $pageCurrent,//当前页数
            "pageSize" => $pageLimit['pageSize'],//每页条数
            "totalCount" => $totalCount,//总条数
        );
        foreach ($invDataList as $value) {
            $data['list'][] = array(
                "invCode" => $value['inv_code'],//所属出仓单单号
                "invCtime" => $value['inv_ctime'],//出仓单创建时间
                "invUcode" => $value['wor_code'],//下单人
                "invUname" => $value['wor_rname'],//下单人名称
                "invMark" => $value['inv_mark'],//备注信息
                "invType" => venus_invoice_type_desc($value['inv_type']),//出仓单类型
                "invStatus" => $value['inv_status'],//出仓单状态
                "invStatMsg" => venus_invoice_status_desc($value['inv_status']),//出仓单状态信息
            );
        }

        $success = true;
        $message = '';
        return array($success, $data, $message);
    }

    /**
     * @return array|bool
     *出仓单管理之修改（1）出仓单详情
     */
    public
    function invoice_detail($param)
    {
        $warCode = $this->warCode;//仓库编号
        if (!isset($param)) {
            $param = $_POST;
        }

        $igoodsModel = IgoodsDao::getInstance($warCode);//出仓单货品清单表model
        $invModel = InvoiceDao::getInstance($warCode);//出仓单model
        $data = array();
        $pageCurrent = $param['data']['pageCurrent'];//当前页数
        if (empty($pageCurrent)) $pageCurrent = 0;//当前页数
        if (empty($param['data']['invCode'])) {
            $message = '出仓单编号不能为空';
            venus_throw_exception(1, $message);
            return false;
        } else {
            $invCode = $param['data']['invCode'];//'出仓单编号
            $totalCount = $igoodsModel->queryCountByInvCode($invCode);//出仓单货品总个数
            $pageLimit = pageLimit($totalCount, $pageCurrent);//获取分页信息
            $igoodsDataList = $igoodsModel->queryListByInvCode($invCode, $pageLimit['page'], $pageLimit['pSize']);//出仓单货品列表

            $data = array(
                "pageCurrent" => $pageCurrent,//当前页数
                "pageSize" => $pageLimit['pageSize'],//每页条数
                "totalCount" => $totalCount,//总条数

            );

            $invData = $invModel->queryByCode($invCode);
            foreach ($igoodsDataList as $value) {
                $data['list'][] = array(
                    "igoCode" => $value['igo_code'],
                    "skName" => $value['spu_name'],
                    "skCode" => $value['sku_code'],
                    "skNorm" => $value['sku_norm'],
                    "skCount" => $value['igo_count'] / $value['spu_count'],
                    "skUnit" => $value['sku_unit'],
                    "spCode" => $value['spu_code'],
                    "count" => $value['igo_count'],
                    "spUnit" => $value['spu_unit'],
                    "spCunit" => $value['spu_cunit'],
                    "spBrand" => $value['spu_brand'],
                    "spNorm" => $value['spu_norm'],
                    "spImg" => $value['spu_img'],
                    "isUpdate" => $invData['inv_type'] == 6 && $invData['return_mark'] == "退货" ? false : true
                );
            }

            $success = true;
            $message = '';
            return array($success, $data, $message);

        }
    }


    /**
     * @return array|bool
     * 出仓单管理之修改（2）修改出仓单货品数量
     */
    public
    function invoice_goods_count_update()
    {
        $warCode = $this->warCode;

        $data = array();

        if (empty($_POST['data']['invCode'])) {
            $message = "出仓单sku编号不能为空";
            venus_throw_exception(1, $message);
            return false;
        }
        if (empty($_POST['data']['igoCode'])) {
            $message = "出仓单货品编号不能为空";
            venus_throw_exception(1, $message);
            return false;
        }
        if (empty($_POST['data']['skCode'])) {
            $message = "出仓单sku编号不能为空";
            venus_throw_exception(1, $message);
            return false;
        }

        if (empty($_POST['data']['skCount'])) {
            $message = "出仓单sku数量不能为空";
            venus_throw_exception(1, $message);
            return false;
        }
        if (empty($_POST['data']['spCunit'])) {
            venus_throw_exception(1, "spu最小计量单位不能为空");
            return false;
        }
        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $_POST['data']['skCount'])) {
            venus_throw_exception(4, "sku数量格式不正确");
            return false;
        } else {
            if (!empty($_POST['data']['spCunit']) && $_POST['data']['spCunit'] == 1) {
                if (floor($_POST['data']['skCount']) != $_POST['data']['skCount']) {
                    venus_throw_exception(4, "sku数量格式不正确");
                    return false;
                }
            }
        }

        if (empty($_POST['data']['spCode'])) {
            $message = "出仓单spu编号不能为空";
            venus_throw_exception(1, $message);
            return false;
        }
        if (empty($_POST['data']['count'])) {
            $message = "出仓单货品spu总数量不能为空";
            venus_throw_exception(1, $message);
            return false;
        }
        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $_POST['data']['count'])) {
            venus_throw_exception(4, "spu总数量格式不正确");
            return false;
        } else {
            if (!empty($_POST['data']['spCunit']) && $_POST['data']['spCunit'] == 1) {
                if (floor($_POST['data']['count']) != $_POST['data']['count']) {
                    venus_throw_exception(4, "spu总数量格式不正确");
                    return false;
                }
            }
        }

        $invCode = $_POST['data']['invCode'];
        $igoCode = $_POST['data']['igoCode'];
        $spCode = $_POST['data']['spCode'];
        $count = $_POST['data']['count'];

        $invModel = InvoiceDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $igoodsModel = IgoodsDao::getInstance($warCode);
        $spuModel = SpuDao::getInstance($warCode);

        $isUpt = $invModel->queryByCode($invCode)['inv_status'];

        //订单处于预报状态，可修改
        if ($isUpt == self::$INVOICE_STATUS_FORECAST) {
            $goodsData = $goodsModel->queryBySpuCode($spCode);
            $goodsCount = $goodsData['goods_count'];
            if ($count <= $goodsCount) {
                $uptIgoRes = $igoodsModel->updateByCode($igoCode, $count);
                if (!$uptIgoRes) {
                    $message = "修改发货清单失败";
                    venus_throw_exception(2, $message);
                    return false;
                } else {
                    $data = '';
                    $success = true;
                    $message = '';
                    return array($success, $data, $message);
                }
            } else {
                $spName = $spuModel->queryByCode($spCode)['spu_name'];
                $message = "出仓单货品" . $spName . "库存不足";
                venus_throw_exception(2, $message);
                return false;
            }
        } else {
            venus_throw_exception(2501, '');
            return false;
        }
    }


    /**
     * @return array|bool
     * 出仓单管理之修改（3）增加出仓单货品
     */
    public
    function invoice_goods_create()
    {
        $warCode = $this->warCode;
        $list = $_POST['data']['list'];
        $data = array();
        if (empty($_POST['data']['invCode'])) {
            $message = '出仓单编号不能为空';
            venus_throw_exception(1, $message);
            return false;
        } else {
            $invCode = $_POST['data']['invCode'];
        }


        $invModel = InvoiceDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $spuModel = SpuDao::getInstance($warCode);
        $igoodsModel = IgoodsDao::getInstance($warCode);

        $isUpt = $invModel->queryByCode($invCode)['inv_status'];

        //订单处于预报状态，可修改
        if ($isUpt == self::$INVOICE_STATUS_FORECAST) {
            $spArr = array();
            foreach ($list as $value) {

                if (empty($value['skCode'])) {
                    $message = "出仓单sku编号不能为空";
                    venus_throw_exception(1, $message);
                    return false;
                }
                if (empty($value['skCount'])) {
                    $message = "出仓单sku数量不能为空";
                    venus_throw_exception(1, $message);
                    return false;
                }
                if (empty($value['spCode'])) {
                    $message = "出仓单spu编号不能为空";
                    venus_throw_exception(1, $message);
                    return false;
                }
                if (empty($value['count'])) {
                    $message = "出仓单货品spu总数量不能为空";
                    venus_throw_exception(1, $message);
                    return false;
                }
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
                $goodsData = $goodsModel->queryBySpuCode($value['spCode']);
                $spuData = $spuModel->queryByCode($value['spCode']);
                $goodsCount = $goodsData['goods_count'];
                if (empty($spuData['spu_sprice'])) {
                    $sprice = 0;
                } else {
                    $sprice = $spuData['spu_sprice'];
                }
                if (empty($spuData['pro_price'])) {
                    $pprice = 0;
                } else {
                    $pprice = $spuData['pro_price'];
                }

                if (empty($spuData['pro_percent'])) {
                    $percent = 0;
                } else {
                    $percent = $spuData['pro_percent'];
                }
                if ($value['count'] <= $goodsCount) {
                    $igoodsAddData = array(
                        "count" => $value['count'],
                        "spucode" => $value['spCode'],
                        "sprice" => $sprice,
                        "pprice" => $pprice,
                        "goodscode" => $goodsData['goods_code'],
                        "skucode" => $value['skCode'],//sku编号
                        "skucount" => $value['skCount'],//sku数量
                        "percent" => $percent,
                        "invcode" => $invCode,
                    );

                    $igoodsCode = $igoodsModel->insert($igoodsAddData);
                    if (!$igoodsCode) {
                        $message = "创建发货清单失败";
                        venus_throw_exception(2, $message);
                        return false;
                    }
                } else {
                    $spName = $spuModel->queryByCode($value['spCode'])['spu_name'];
                    $spArr[] = $spName;
                }
            }
            if (!empty($spArr)) {
                $spuList = join(",<br>", $spArr);
                $message = "库存不足商品列表" . "<br>" . $spuList;
                venus_throw_exception(2, $message);
                return false;
            } else {
                $success = true;
                $message = '';
                return array($success, $data, $message);
            }
        } else {
            venus_throw_exception(2502, '');
            return false;
        }
    }

    /**
     * @return array|bool
     * 出仓单管理之修改（4）删除出仓单货品
     */
    public
    function invoice_goods_delete()
    {
        $warCode = $this->warCode;
        $data = array();
        if (empty($_POST['data']['invCode'])) {
            $message = '出仓单编号不能为空';
            venus_throw_exception(1, $message);
            return false;
        }
        if (empty($_POST['data']['igoCode'])) {
            $message = '出仓单货品编号不能为空';
            venus_throw_exception(1, $message);
            return false;
        }
        $invCode = $_POST['data']['invCode'];
        $igoCode = $_POST['data']['igoCode'];

        $invModel = InvoiceDao::getInstance($warCode);
        $igoodsModel = IgoodsDao::getInstance($warCode);

        $isUpt = $invModel->queryByCode($invCode)['inv_status'];
        //订单处于预报状态，可修改
        if ($isUpt == self::$INVOICE_STATUS_FORECAST) {
            $igoodsCode = $igoodsModel->deleteByCode($igoCode, $invCode);

            if (!$igoodsCode) {
                venus_throw_exception(2, "删除发货清单失败");
                return false;
            } else {
                $success = true;
                $message = '';
                return array($success, $data, $message);
            }

        } else {
            venus_throw_exception(2501, '');
            return false;
        }
    }

    /**
     * @return array|bool
     * 出仓单管理之删除
     */
    public
    function invoice_delete($param)
    {
        $warCode = $this->warCode;
        if (!isset($param)) {
            $param = $_POST;
        }
        $data = array();
        if (empty($param['data']['invCode'])) {
            $message = '出仓单编号不能为空';
            venus_throw_exception(1, $message);
            return false;
        } else {
            $invCode = $param['data']['invCode'];
        }
        $invModel = InvoiceDao::getInstance($warCode);

        $isUpt = $invModel->queryByCode($invCode)['inv_status'];
        //订单处于预报状态，可修改
        if ($isUpt == self::$INVOICE_STATUS_FORECAST) {
            $invStatus = self::$INVOICE_STATUS_CANCEL;
            $deleteInvData = $invModel->updateStatusByCode($invCode, $invStatus);
            if (!$deleteInvData) {
                $message = "删除出仓单";
                venus_throw_exception(2, $message);
                return false;
            } else {
                $success = true;
                $message = '';
                return array($success, $data, $message);
            }
        } else {
            venus_throw_exception(2501, '');
            return false;
        }

    }

//暂不使用(退部分货品)
//    public function return_goods()
//    {
//        $warCode = $this->warCode;
//        $data = array();
//        if (empty($_POST['data']['invCode'])) {
//            $message = '出仓单编号不能为空';
//            venus_throw_exception(1, $message);
//            return false;
//        }
//        if (empty($_POST['data']['igoCode'])) {
//            $message = '出仓单货品编号不能为空';
//            venus_throw_exception(1, $message);
//            return false;
//        }
//        $invCode = $_POST['data']['invCode'];
//        $igoCode = $_POST['data']['igoCode'];
//        $skCount = $_POST['data']['skCount'];
//
//        $igsModel = IgoodsentDao::getInstance($warCode);
//        $igoModel = IgoodsDao::getInstance($warCode);
//        $invModel = InvoiceDao::getInstance($warCode);
//        $gsModel = GoodstoredDao::getInstance($warCode);
//        $gbModel = GoodsbatchDao::getInstance($warCode);
//        $goodsModel = GoodsDao::getInstance($warCode);
//        venus_db_starttrans();
//        $igoInfo = $igoModel->queryByCode($igoCode);
//        $invCode = $igoInfo['inv_code'];
//        $spuCode = $igoInfo['spu_code'];
//        $isSuccess = true;
//        if ($igoInfo['sku_count'] > $skCount) {
//            $skuCountNew = bcsub($igoInfo['sku_count'], $skCount);
//            $countNew = bcsub($igoInfo['igo_count'], bcmul($skCount, $igoInfo['spu_count']));
//            $isSuccess = $isSuccess && $igoModel->updateCountAndSkuCountByCode($igoCode, $countNew, $skuCountNew);
//            $igsInfo = $igsModel->queryListByCondition(array("igocode" => $igoCode));
//            $gsInfoArr = array();
//            $num = $skCount;
//            foreach ($igsInfo as $igsData) {
//                $gsCode = $igsData['gs_code'];
//                if (!isset($gsInfoArr[$gsCode]['count'])) {
//                    $gsInfoArr[$gsCode]['count'] = 0;
//                    $gsInfoArr[$gsCode]['sku_count'] = 0;
//                }
//                if ($num >= $igsData['sku_count']) {
//                    $gsInfoArr[$gsCode]['count'] = bcadd($igsData['igs_count'], $gsInfoArr[$gsCode]['count']);
//                    $gsInfoArr[$gsCode]['sku_count'] = bcadd($igsData['sku_count'], $gsInfoArr[$gsCode]['sku_count']);
//                    $num = bcadd($num, $igsData['sku_count']);
//                    $isSuccess = $isSuccess && $igsModel->deleteByCode($igsData['igs_code']);
//                } else {
//                    $gsInfoArr[$gsCode]['count'] = bcadd($num, $gsInfoArr[$gsCode]['count']);
//                    $gsInfoArr[$gsCode]['sku_count'] = bcadd(bcmul($num, $igsData['spu_count']), $gsInfoArr[$gsCode]['sku_count']);
//                    $isSuccess = $isSuccess && $igsModel->updateCountAndSkuCountByCode($igsData['igs_code'], bcsub($igsData['sku_count'], $num), bcsub($igsData['igs_count'], bcmul($num, $igsData['spu_count'])));
//                    break;
//                }
//            }
//            $count = 0;
//            foreach ($gsInfoArr as $gsCode => $gsData) {
//                $igsCount = $gsData['count'];
//                $count = bcadd($igsCount, $count);
//                $igsSkuCount = $gsData['sku_count'];
//                $gsInfo = $gsModel->queryByCode($gsCode);
//                $isSuccess = $isSuccess && $gsModel->updateByCode($gsCode, $gsInfo['count'] + $igsCount);
//                $isSuccess = $isSuccess && $gsModel->updateSkuCountByCode($gsCode, $gsInfo['sku_count'] + $igsSkuCount);
//            }
//
//        } elseif ($igoInfo['sku_count'] == $skCount) {
//            $count = bcmul($skCount, $igoInfo['spu_count']);
//            $isSuccess = $isSuccess && $igoModel->deleteByCode($igoCode);
//            $isSuccess = $isSuccess && $igsModel->deleteByIgoCode($igoCode);
//        } else {
//            venus_db_rollback();
//            $success = false;
//            $message = "请重新选择退货数量";
//            return array($success, $data, $message);
//        }
//        $goodsInfo = $goodsModel->queryBySpuCode($spuCode);
//        $isSuccess = $isSuccess && $goodsModel->updateCountByCode($goodsInfo['goods_code'], $goodsInfo['goods_count'], $goodsInfo['goods_count'] + $count);
//        $issetIgoList = $igoModel->queryListByInvCode($invCode);
//        if (empty($issetIgoList)) {
//            $isSuccess = $isSuccess && $invModel->deleteByCode($invCode);//退货，如果出仓单无数据删除调用
//        }
//        if (!$isSuccess) {
//            venus_db_rollback();
//            $success = false;
//            $message = "操作失败";
//        } else {
//            venus_db_commit();
//            $success = true;
//            $message = "";
//        }
//        $data = array();
//        return array($success, $data, $message);
//    }

    /**
     * @param $goodstored array 库存批次货位数据
     * @param $igoCount string 需要发出的货品数量
     * @param $igoCode string 需要发出的igoods编号
     * @param $spuCode string 需要发出的spu编号
     * @param $invcode string 出仓单编号
     * @return mixed
     */
    public
    function branch_goodstored($goodstored, $igoCount, $igoCode, $spuCode, $invcode)
    {
        $sentNum = 0;
        $igoodsentAddData = array();
        foreach ($goodstored as $item) {
            $skuCode = $item['sku_code'];
            if ($item['gs_count'] > 0) {
                if ($igoCount - $sentNum - $item['gs_count'] >= 0) {
                    $sentNum += $item['gs_count'];
                    $igoodsentAddData[] = array(
                        "count" => $item['gs_count'],
                        "bprice" => $item['gb_bprice'],
                        "spucode" => $spuCode,
                        "gscode" => $item['gs_code'],
                        "igocode" => $igoCode,
                        "skucode" => $skuCode,
                        "skucount" => $item['gs_count'] / $item['spu_count'],
                        "invcode" => $invcode,
                        "remaining" => 0
                    );
                } else {
                    if ($igoCount - $sentNum != 0) {
                        $gscount = $item['gs_count'] - ($igoCount - $sentNum);
                        $igoodsentCount = $igoCount - $sentNum;
                        $sentNum += $igoodsentCount;
                        $igoodsentAddData[] = array(
                            "count" => $igoodsentCount,
                            "bprice" => $item['gb_bprice'],
                            "spucode" => $spuCode,
                            "gscode" => $item['gs_code'],
                            "igocode" => $igoCode,
                            "skucode" => $skuCode,
                            "skucount" => $igoodsentCount / $item['spu_count'],
                            "invcode" => $invcode,
                            "remaining" => $gscount
                        );
                        break;
                    }

                }
            } else {
                continue;
            }
        }
        $igoodsentAddData["sentNum"] += $sentNum;
        return $igoodsentAddData;
    }

    /**
     * @param $param
     * @return array|bool
     * 创建出仓单
     */
    public function invoice_create($param)
    {
        if (!isset($param)) {
            $param = $_POST;
            if (empty($param['data']['type'])) {
                $type = 5;//领用出仓
            } else {
                $type = $param['data']['type'];
            }
        } else {
            $type = 5;//领用出仓
        }

        $warCode = $this->warCode;//仓库编号
        $worCode = $this->worcode;//人员编号

        $list = $param['data']['list'];//出仓单货品列表
        //送达时间->备注
        if (!empty($param['data']['mark'])) {
            $mark = $param['data']['mark'];
        }
        if (!empty($param['data']['room'])) {
            $room = $param['data']['room'];//客户餐厅名称
        }
        if (!empty($param['data']['ctime'])) {
            $ctime = $param['data']['ctime'] . " 21:00:00";
        }

        $receiver = $this->worRname;//客户名称
        $phone = $this->worPhone;//客户手机号
        $address = $this->warAddress;//客户地址
        $postal = $this->warPostal;//客户邮编

        $data = array();//返回数据声明
        //声明所需要用的Model及服务
        $invModel = InvoiceDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $spuModel = SpuDao::getInstance($warCode);
        $igoodsModel = IgoodsDao::getInstance($warCode);
        $goodstoredModel = GoodstoredDao::getInstance($warCode);
        $igoodsentModel = IgoodsentDao::getInstance($warCode);
        $isSuccess = true;
        venus_db_starttrans();//开启事务

        $invStatus = self::$INVOICE_STATUS_FINISH;

        $invAddData = array(
            "status" => $invStatus,//出仓单状态
            "receiver" => $receiver,//客户名称
            "phone" => $phone,//客户手机号
            "address" => $address,//客户地址
            "postal" => $postal,//客户邮编
            "type" => $type,//出仓单类型
            "mark" => $mark,//出仓单备注
            "worcode" => $worCode,//人员编号
            "room" => $room,//人员编号
            "ctime" => (isset($ctime) ? $ctime : null),
        );//出仓单新增数据
        $invCode = $invModel->insert($invAddData);//出仓单创建操作
        $isSuccess = $isSuccess && !empty($invCode);

        $invSkuData = array();
        foreach ($list as $spData) {
            if (empty($spData['spCode'])) {
                $message = "出仓单spu编号不能为空";
                venus_throw_exception(1, $message);
                return false;
            }
            if (empty($spData['count'])) {
                $message = "出仓单货品spu总数量不能为空";
                venus_throw_exception(1, $message);
                return false;
            }
            if (empty($spData['spCunit'])) {
                venus_throw_exception(1, "spu最小计量单位不能为空");
                return false;
            }
            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $spData['count'])) {
                venus_throw_exception(4, "spu总数量格式不正确");
                return false;
            } else {
                if (!empty($spData['spCunit']) && $spData['spCunit'] == 1) {
                    if (floor($spData['count']) != $spData['count']) {
                        venus_throw_exception(4, "spu总数量格式不正确");
                        return false;
                    }
                }
            }
            if (empty($spData['skCode'])) {
                $skuCode = SkuDao::getInstance()->querySkuCodeBySpuCodeToIwms($spData['spCode']);
            } else {
                $skuCode = $spData['skCode'];
            }
            if (empty($spData['skCount'])) {
                $message = "出仓单货品sku数量不能为空";
                venus_throw_exception(1, $message);
                return false;
            }
            if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $spData['skCount'])) {
                venus_throw_exception(4, "sku数量格式不正确");
                return false;
            } else {
                if (!empty($spData['spCunit']) && $spData['spCunit'] == 1) {
                    if (floor($spData['skCount']) != $spData['skCount']) {
                        venus_throw_exception(4, "sku数量格式不正确");
                        return false;
                    }
                }
            }

            if (!array_key_exists($spData['spCode'], $invSkuData)) {
                $invSkuData[$spData['spCode']] = $spData;
                $invSkuData[$spData['spCode']]['skCode'] = $skuCode;
            } else {
                $invSkuData[$spData['spCode']]['count'] = bcadd($invSkuData[$spData['skCode']]['count'], $spData['count'], 2);
                $invSkuData[$spData['spCode']]['skCount'] = bcadd($invSkuData[$spData['skCode']]['skCount'], $spData['skCount'], 2);
            }

        }
        $understockArr = array();
        foreach ($invSkuData as $spCode => $value) {
            $skuCode = $value['skCode'];
            $skuCount = $value['skCount'];
            $spuCode = $value['spCode'];
            $goodsData = $goodsModel->queryBySpuCode($spuCode);//获取sku库存
            $goodsCount = $goodsData['goods_count'];//spu库存
            $goodsSkuCount = $goodsData['sku_count'];//spu库存
            $spuCount = 1;
            $goodsCode = $goodsData['goods_code'];
            $count = bcmul($skuCount, $spuCount, 2);
            if (!empty($goodsData['spu_sprice'])) {
                $sprice = $goodsData['spu_sprice'];//spu当前销售价
            } else {
                $sprice = 0;
            }
            if (!empty($goodsData['pro_price'])) {
                $pprice = $goodsData['pro_price'];//spu利润价
            } else {
                $pprice = 0;
            }
            if (!empty($goodsData['pro_percent'])) {
                $percent = $goodsData['pro_percent'];//spu利润率
            } else {
                $percent = 0;
            }
            $spName = $goodsData['spu_name'];
            //出仓单货品数量小于等于库存
            if ($count <= $goodsCount) {
                $igoodsAddData = array(
                    "count" => $count,//spu总数量
                    "spucode" => $spuCode,//spu编号
                    "sprice" => $sprice,//spu当前销售价
                    "pprice" => $pprice,//spu当前利润
                    "goodscode" => $goodsCode,//库存编号
                    "percent" => $percent,//spu当前利润率
                    "skucode" => $skuCode,//sku编号
                    "skucount" => $skuCount,//sku数量
                    "invcode" => $invCode,//所属出仓单单号
                );

                $igoodsCode = $igoodsModel->insert($igoodsAddData);//创建发货清单
                $isSuccess = $isSuccess && !empty($igoodsCode);
                $newCountGoods = bcsub($goodsCount, $count, 2);//新库存
                $newSkuCountGoods = bcsub($goodsSkuCount, $skuCount, 2);

                $uptGoods = $goodsModel->updateCountByCode($goodsCode, $goodsCount, $newCountGoods, $newSkuCountGoods);//修改库存
                $isSuccess = $isSuccess && $uptGoods;
                //创建状态产生批次库位及库存变化
                $gsCount = $goodstoredModel->queryCountBySkuCode($skuCode);
                $goodstoredList = $goodstoredModel->queryListBySkuCode($skuCode, 0, $gsCount);//指定商品的库存货品批次货位列表数据
                foreach ($goodstoredList as $gsData) {

                    if (isset($ctime) && $gsData['gb_ctime'] > $ctime && $skuCount > 0) {
                        venus_db_rollback();
                        $message = "申领时间之前库存不足商品列表" . "<br/>";
                        $message .= $spName . ":" . $skuCount . "<br/>";
                        return array(false, array(), $message);
                    }

                    $gsCode = $gsData["gs_code"];
                    $gsCount = $gsData['gs_count'];
                    $gsSkuCount = $gsData['sku_count'];
                    $bPrice = $gsData['gb_bprice'];
                    if ($gsCount == 0 || $gsSkuCount == 0) continue;
                    if ($skuCount == 0) break;
                    $igsSkuCount = 0;
                    if ($skuCount <= $gsSkuCount) {
                        $updatedSkuCount = bcsub($gsSkuCount, $skuCount, 2);
                        $updatedGoodsCount = bcmul($spuCount, $updatedSkuCount, 2);
                        $igsSkuCount = $skuCount;
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
                        "spucode" => $spuCode,
                        "gscode" => $gsCode,
                        "igocode" => $igoodsCode,
                        "skucode" => $skuCode,
                        "skucount" => $igsSkuCount,
                        "invcode" => $invCode,
                    );
                    $igsCode = $igoodsentModel->insert($addIgsData);
                    $isSuccess = $isSuccess && !empty($igsCode);
                    $skuCount = bcsub($skuCount, $igsSkuCount, 2);
                }
                $isSuccess = $isSuccess && ($skuCount == 0);
            } else {
                //出仓单货品数量大于库存，计入统计库存不足数组
                if (!array_key_exists($spName, $understockArr)) {
                    $understockArr[$spName] = bcsub($value['count'], $goodsCount, 2);
                }
            }
        }

        if (!empty($understockArr)) {
            venus_db_rollback();
            $message = "库存不足商品列表" . "<br/>";
            foreach ($understockArr as $spuName => $spuCount) {
                $message .= $spuName . ":" . $spuCount . "<br/>";
            }
            venus_throw_exception(2, $message);
            return false;
        } else {
            if (!$isSuccess) {
                venus_db_rollback();
                $success = false;
                $message = '创建出仓单批次失败';
                return array($success, $data, $message);
            } else {
                venus_db_commit();
                $success = true;
                $message = '';
                return array($success, $data, $message);
            }

        }

    }

    //添加查询出仓单状态，临时用于判断是否可以取消申领
    public function is_update_invoice($param)
    {
        $warCode = $this->warCode;//仓库编号
        if (!isset($param)) {
            $param = $_POST;
        }

        if (!empty($param['warCode'])) $warCode = $param['warCode'];
        $igoodsModel = IgoodsDao::getInstance($warCode);//出仓单model
        $invModel = InvoiceDao::getInstance($warCode);//出仓单model
        $data = array();

        if (empty($param['data']['igoCode'])) {
            return array(false, "", "出仓批次编号不能为空");
        } else {
            $igoCode = $param['data']['igoCode'];//'出仓单编号
            $igoInfo = $igoodsModel->queryByCode($igoCode);
            $invCode = $igoInfo['inv_code'];//'出仓单编号

            $invData = $invModel->queryByCode($invCode);

            $success = $invData['inv_type'] == 6 && $invData['return_mark'] == "退货" ? false : true;

            if ($success) {
                return array($success, "", "");
            } else {
                return array($success, "", "此出仓单为向科贸提出的退货出仓货品，不可取消");
            }
        }
    }
}
