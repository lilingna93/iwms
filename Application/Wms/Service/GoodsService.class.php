<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2018/7/27
 * Time: 10:59
 */

namespace Wms\Service;


use Common\Service\PassportService;
use Wms\Dao\GoodsDao;
use Wms\Dao\GoodstoredDao;
use Wms\Dao\IgoodsDao;
use Wms\Dao\IgoodsentDao;
use Wms\Dao\InvoiceDao;
use Wms\Dao\SpuDao;
use Wms\Dao\WorkerDao;

class GoodsService
{
    public $warCode;
    public $worcode;

    public function __construct()
    {
        $workerData = PassportService::loginUser();
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
     * @return array
     * 库存管理
     */
    public function goods_search($param)
    {
        $warCode = $this->warCode;

        if (!isset($param)) {
            $param = $_POST;
            $pageSize = 100;
        } else {
            $pageSize = 1000;
        }
        $data = array();
        $tCode = $param['data']['tCode'];
        $cgCode = $param['data']['cgCode'];
        $spName = $param['data']['spName'];
        $isAllGoods = $param['data']['all'];//1全部带0，2全部不带0，3库存0
        $pageCurrent = $param['data']['pageCurrent'];//当前页数
        $clause = array();
        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }
        if (!empty($tCode)) {
            $clause['type'] = $tCode;
        }
        if (!empty($cgCode)) {
            $clause['subtype'] = $cgCode;
        }
        if (!empty($spName)) {
            $clause['%name%'] = $spName;
        }
        if (empty($isAllGoods)) $isAllGoods = 2;
        if (!empty($isAllGoods)) {
            $clause['gcount'] = $isAllGoods;
        }
        $goodsModel = GoodsDao::getInstance($warCode);
        if (!empty($isAllGoods) && ($isAllGoods == 2 || $isAllGoods == 3)) {
            $totalCount = $goodsModel->queryCountByCondition($clause);
            $pageLimit = pageLimit($totalCount, $pageCurrent, $pageSize);
            $goodsData = $goodsModel->queryListByCondition($clause, $pageLimit['page'], $pageLimit['pSize']);
        } else {
            $totalCount = $goodsModel->queryAllCountByCondition($clause);
            $pageLimit = pageLimit($totalCount, $pageCurrent, $pageSize);
            $goodsData = $goodsModel->queryAllListByCondition($clause, $pageLimit['page'], $pageLimit['pSize']);
        }
        $data = array(
            "pageCurrent" => $pageCurrent,
            "pageSize" => $pageLimit['pageSize'],
            "totalCount" => $totalCount,
        );

        foreach ($goodsData as $value) {
            $spuImg = $value['spu_code'];
            $goodsCount = ($value['goods_count'] == intval($value['goods_count'])) ? intval($value['goods_count']) : $value['goods_count'];
            $data['list'][] = array(
                "spCode" => $value['spu_code'],
                "spName" => $value['spu_name'],
                "spNorm" => $value['spu_norm'],
                "spCount" => $goodsCount,
                "spUnit" => $value['spu_unit'],
                "spBrand" => $value['spu_brand'],
                "spCunit" => $value['spu_cunit'],
                "spImg" => empty($spuImg) ? "_" : $spuImg . ".jpg?_=" . C("SKU_IMG_VERSION"),
            );
        }
        $success = true;
        $message = '';
        return array($success, $data, $message);
    }


    /**
     * @return array|bool
     * 库存管理-批次详情
     */
    public function goods_stored()
    {
        $warCode = $this->warCode;
        $data = array();
        $clause = array();
        $spCode = $_POST['data']['spCode'];
        $pageCurrent = $_POST['data']['pageCurrent'];//当前页数
        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }
        if (empty($spCode)) {
            $message = "spu编号不能为空";
            venus_throw_exception(1, $message);
            return false;
        }
        $goodstoredModel = GoodstoredDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);

        $totalCount = $goodstoredModel->queryCountBySpuCode($spCode);
        $pageLimit = pageLimit($totalCount, $pageCurrent);
        $goodstoredData = $goodstoredModel->queryListBySpuCode($spCode, $pageLimit['page'], $pageLimit['pSize']);
        $spuData = $goodsModel->queryBySpuCode($spCode);
        $goodsCount = ($spuData['goods_count'] == intval($spuData['goods_count'])) ? intval($spuData['goods_count']) : $spuData['goods_count'];
        $data = array(
            "pageCurrent" => $pageCurrent,
            "pageSize" => $pageLimit['pageSize'],
            "totalCount" => $totalCount,
            "spCode" => $spuData['spu_code'],
            "spName" => $spuData['spu_name'],
            "spNorm" => $spuData['spu_norm'],
            "spCount" => $goodsCount,
            "spUnit" => $spuData['spu_unit'],
            "spCunit" => $spuData['spu_cunit']
        );

        foreach ($goodstoredData as $value) {
            $skuCount = ($value['sku_count'] == intval($value['sku_count'])) ? intval($value['sku_count']) : $value['sku_count'];
            $gbCount = ($value['gb_count'] == intval($value['gb_count'])) ? intval($value['gb_count']) : $value['gb_count'];
            $gsInit = ($value['gs_init'] == intval($value['gs_init'])) ? intval($value['gs_init']) : $value['gs_init'];
            $gsCount = ($value['gs_count'] == intval($value['gs_count'])) ? intval($value['gs_count']) : $value['gs_count'];
            $data['list'][] = array(
                "gsCode" => $value['gs_code'],
                "recCode" => $value['rec_code'],
                "recCtime" => $value['rec_ctime'],
                "gsNum" => $value['sku_norm'] . "*" . $skuCount . $value['sku_unit'] . "=" . $gbCount . $value['spu_unit'],
                "sprice" => $value['gb_bprice'],
                "totalPrice" => bcmul($value['gb_bprice'], $gsCount, 2),
                "gsInit" => $gsInit,
                "gsCount" => $gsCount,
                "posCode" => $value['pos_code'],
                "supCode" => $value['sup_code'],
                "recEcode" => $value['rec_ecode'],
                "isReturn" => (!empty($value['rec_ecode'])&&$value['sup_code']=="SU00000000000001") || $gsCount == 0 ? false : true
            );
        }
        $success = true;
        $message = '';
        return array($success, $data, $message);

    }

    /**
     * @param $param
     * @return array|bool
     * 小程序购物车实时数据
     */
    public function apply_car_info($param)
    {
        $param = (!isset($param)) ? $_POST : $param;
        $skuCodes = $param['data']['skCodes'];
        $warCode = $this->warCode;
        $goodsModel = GoodsDao::getInstance($warCode);

        if (empty($skuCodes)) {
            venus_throw_exception(1, "货品编号不能为空");
            return false;
        }
        $skuDataList = array();
        foreach ($skuCodes as $index => $skuItem) {
            $skuData = $goodsModel->queryBySpuCode($skuItem);
            $spuImg = $skuData['spu_code'];
            $goodsCount = ($skuData['goods_count'] == intval($skuData['goods_count'])) ? intval($skuData['goods_count']) : $skuData['goods_count'];
            $skuDataList["list"][$index] = array(
                "spCode" => $skuData['spu_code'],
                "spName" => $skuData['spu_name'],
                "spNorm" => $skuData['spu_norm'],
                "spCount" => $goodsCount,
                "spUnit" => $skuData['spu_unit'],
                "spBrand" => $skuData['spu_brand'],
                "spCunit" => $skuData['spu_cunit'],
                "spImg" => empty($spuImg) ? "_" : $spuImg . ".jpg?_=" . C("SKU_IMG_VERSION"),
            );
        }
        return array(true, $skuDataList, "");
    }

    //选择批次退货
    public function goods_return()
    {
        $warCode = $this->warCode;
        $gsCode = $_POST['data']['gsCode'];
        $skCount = $_POST['data']['skCount'];
        $mark = $_POST['data']['mark'];
        if (empty($gsCode)) {
            $message = "批次编号不能为空";
            venus_throw_exception(1, $message);
            return false;
        }
        $gsModel = GoodstoredDao::getInstance($warCode);
        $invModel = InvoiceDao::getInstance($warCode);
        $igoModel = IgoodsDao::getInstance($warCode);
        $goodsModel = GoodsDao::getInstance($warCode);
        $igsModel = IgoodsentDao::getInstance($warCode);
        $gsInfo = $gsModel->queryByCode($gsCode);
        if ($gsInfo['sku_count'] < $skCount) {
            $success = false;
            $data = "";
            $message = "此批次退货数量最多为" . $gsInfo['sku_count'];
        } else {
            venus_db_starttrans();
            $isSuccess = true;
            $receiver = $this->worRname;//客户名称
            $phone = $this->worPhone;//客户手机号
            $address = $this->warAddress;//客户地址
            $postal = $this->warPostal;//客户邮编

            $addInvData = array(
                'receiver' => $receiver,
                'phone' => $phone,
                'address' => $address,
                'postal' => $postal,
                'type' => 6,
                'mark' => $mark,
                'worcode' => $this->worcode,
                'status' => 5,
            );
            $invCode = $invModel->insert($addInvData);

            $isSuccess = $isSuccess && !empty($invCode);
            $goodsData = $goodsModel->queryBySpuCode($gsInfo['spu_code']);
            $addIgoData = array(
                'skucode' => $gsInfo['sku_code'],
                'skucount' => $skCount,
                'spucode' => $gsInfo['spu_code'],
                'count' => bcmul($skCount, $gsInfo['spu_count'], 2),
                'sprice' => $gsInfo['spu_sprice'],
                'pprice' => empty($gsInfo['profit_percent']) ? 0 : $gsInfo['profit_percent'],
                'percent' => empty($gsInfo['spu_percent']) ? 0 : $gsInfo['spu_percent'],
                "goodscode" => $goodsData['goods_code'],
                "invcode" => $invCode,
            );
            $igoCode = $igoModel->insert($addIgoData);
            $isSuccess = $isSuccess && !empty($igoCode);
            $addIgsData = array(
                'skucode' => $gsInfo['sku_code'],
                'skucount' => $skCount,
                'spucode' => $gsInfo['spu_code'],
                'count' => bcmul($skCount, $gsInfo['spu_count'], 2),
                'bprice' => $gsInfo['gb_bprice'],
                'gscode' => $gsInfo['gs_code'],
                'igocode' => $igoCode,
                'invcode' => $invCode,
            );
            $igsCode = $igsModel->insert($addIgsData);
            $isSuccess = $isSuccess && !empty($igsCode);
            $gsCount = bcsub($gsInfo['gs_count'], $addIgsData['count'], 2);
            $gsSkucount = bcsub($gsInfo['sku_count'], $addIgsData['skucount'], 2);
            $isSuccess = $isSuccess && $gsModel->updateCountAndSkuCountByCode($gsInfo['gs_code'], $gsCount, $gsSkucount);
            $count = bcsub($goodsData['goods_count'], $addIgsData['count'], 2);
            $skucount = bcsub($goodsData['sku_count'], $addIgsData['skucount'], 2);
            $isSuccess = $isSuccess && $goodsModel->updateCountByCode($goodsData['goods_code'], $goodsData['goods_count'], $count, $skucount);
            if ($isSuccess) {
                venus_db_commit();
                $success = true;
                $data = "";
                $message = "此批次退货成功";
            } else {
                venus_db_rollback();
                $success = false;
                $data = "";
                $message = "此批次退货失败，请重新退货";
            }
        }
        return array($success, $data, $message);
    }

}