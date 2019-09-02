<?php

namespace Wms\Service;

use Common\Service\ExcelService;
use Common\Service\PassportService;
use Wms\Dao\SpuDao;
use Wms\Dao\ProfitDao;
use Wms\Service\SkuService;

class SpuService
{

    public $waCode;

    function __construct()
    {
        $workerData = PassportService::getInstance()->loginUser();
        if(empty($workerData)){
            venus_throw_exception(110);
        }
        $this->waCode = $workerData["war_code"];
    }

    //1.SPU搜索
    public function spu_search()
    {

        $spName = $_POST['data']['spName'];
        $spType = $_POST['data']['spType'];
        $spSubtype = $_POST['data']['spSubtype'];
        $exwCode = $_POST['data']['exwCode'];//客户仓库编码
        $pageCurrent = $_POST['data']['pageCurrent'];//当前页码
        $pageSize = 100;//当前页面总条数

        if (!empty($spName) && preg_match("/^[a-z]/i", $spName)) {
            $condition['abname'] = $spName;
        }
        if (!empty($spName) && !preg_match("/^[a-z]/i", $spName)) {//SPU名称
            $condition['%name%'] = $spName;
        }

        if (!empty($spType)) {//一级分类编号
            $condition['type'] = $spType;
        }

        if (!empty($spSubtype)) {//二级分类编号
            $condition['subtype'] = $spSubtype;
        }

        if (!empty($exwCode)) {//客户仓库
            $condition['exwarcode'] = $exwCode;
        }

        //当前页码
        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }

        $SpuDao = SpuDao::getInstance($this->waCode);
        $totalCount = $SpuDao->queryCountByCondition($condition);//获取指定条件的总条数
        $pageLimit = pageLimit($totalCount, $pageCurrent);
        $results = $SpuDao->queryListByCondition($condition, $pageLimit['page'], $pageLimit['pSize']);

        if (empty($results)) {
            $spuList = array(
                "pageCurrent" => 0,
                "pageSize" => 100,
                "totalCount" => 0
            );
            $spuList["list"] = array();
        } else {
            $spuList = array(
                "pageCurrent" => $pageCurrent,
                "pageSize" => $pageSize,
                "totalCount" => $totalCount
            );
            foreach ($results as $k => $val) {
                if (!empty($val['sku_mark'])) {
                    $spMark = "(" . $val['sku_mark'] . ")";
                }
                $cltProfit = round(bcmul($val['spu_sprice'], $val['pro_percent'], 3), 2);//客户利润价

                $spuList["list"][$k] = array(
                    "spCode" => $val['spu_code'],//编号
                    "spName" => $val['spu_name'] . $spMark,//品名
                    "spUnit" => $val['spu_unit'],//品牌
                    "spNorm" => $val['spu_norm'],//单位
                    "spBprice" => $val['spu_bprice'],//内部采购价
                    "spSprice" => $val['spu_sprice'],//内部销售价
                    "cltPercent" => $val['pro_percent'] ?: "",//客户利润率
                    "cltProfit" => $cltProfit,//客户利润价
                    "cltSprice" => $val['spu_sprice'] + $cltProfit,//客户销售价
                    "supCode" => $val['sup_code'] ?: "",//供货商编码
                    "supName" => $val['sup_name'] ?: ""//供货商名称
                );
            }
        }
        return array(true, $spuList, "");
    }

    //2.导入客户利润率
    public function percent_import()
    {
        $datas = ExcelService::getInstance()->upload("file");
        $dicts = array(
            "B" => "spucode",//SPU编号
            "T" => "percent",//利润率
            "S" => "exwarcode"//客户编号
        );

        $proList = array();
        foreach ($datas as $sheetName => $list) {
            unset($list[0]);
            $proList = array_merge($proList, $list);
        }
        $dataArr = array();
        venus_db_starttrans();//启动事务
        $result = true;
        foreach ($proList as $index => $proItem) {
            $proData = array();
            foreach ($dicts as $col => $key) {
                $proData[$key] = isset($proItem[$col]) ? $proItem[$col] : "";
            }

            if (trim($proData['spucode']) == '' || trim($proData['percent']) == '' || trim($proData['exwarcode']) == '') {
                if (trim($proData['spucode']) == '' && trim($proData['percent']) == '' && trim($proData['exwarcode'])) {
                    continue;
                } else {
                    if (trim($proData['spucode']) == '') {
                        venus_db_rollback();
                        venus_throw_exception(1, "货品编号不能为空");
                        return false;
                    }

                    if (trim($proData['percent']) == '') {
                        venus_db_rollback();
                        venus_throw_exception(1, "利润率不能为空");
                        return false;
                    }

                    if (trim($proData['exwarcode']) == '') {
                        venus_db_rollback();
                        venus_throw_exception(1, "客户编号不能为空");
                        return false;
                    }
                }
            } else {
                //查询数据库是否有重复数据
                $condition['spucode'] = $proData['spucode'];
                $condition['percent'] = $proData['percent'];
                $condition['exwarcode'] = $proData['exwarcode'];

                $jsonCon = json_encode($condition);
                if (in_array($jsonCon, $dataArr)) {
                    $redata = json_decode($jsonCon, true);
                    $spCode = $redata['spucode'];
                    venus_throw_exception(5001, $spCode);
                } else {

                    if (empty($proData["spucode"])) {
                        $result = $result && ProfitDao::GetInstance()->insert($proData);
                    } else {
                        $datas = ProfitDao::GetInstance()->queryByCondition($proData['spucode'], $proData['exwarcode']);
                        if (empty($datas)) {
                            $result = $result && ProfitDao::GetInstance()->insert($proData);
                        } else {
                            $result = $result && ProfitDao::GetInstance()->updatePercentBySkucode($proData['spucode'], $proData['exwarcode'], $proData['percent']);
                        }
                    }
                }
            }

        }
        if ($result) {
            venus_db_commit();
            $SkuService = new SkuService();
            $SkuService->release_latestsku($this->waCode, $proData['exwarcode']);
            $success = true;
            $message = "导入客户利润率成功";

        } else {
            venus_db_rollback();
            $success = false;
            $message = "导入客户利润率失败";
        }
        return array($success, "", $message);
    }

    //3.SPU导入内部销售价
    public function sprice_import()
    {

        $datas = ExcelService::getInstance()->upload("file");
        $dicts = array(
            "B" => "spu_code",//spu品类编号
            "Q" => "spu_sprice",//销售价
        );

        $spuList = array();
        foreach ($datas as $sheetName => $list) {
            unset($list[0]);
            $spuList = array_merge($spuList, $list);
        }

        $dataArr = array();
        venus_db_starttrans();//启动事务
        $result = true;
        foreach ($spuList as $index => $spuItem) {
            $spuData = array();
            foreach ($dicts as $col => $key) {
                $spuData[$key] = isset($spuItem[$col]) ? $spuItem[$col] : "";
            }

            if (trim($spuData['spu_code']) == '' || trim($spuData['spu_sprice']) == '') {
                if (trim($spuData['spu_code']) == '' && trim($spuData['spu_sprice']) == '') {
                    continue;
                } else {
                    if (trim($spuData['spu_code']) == '') {
                        venus_db_rollback();//回滚事务
                        venus_throw_exception(1, "货品编号不能为空");
                        return false;
                    }

                    if (trim($spuData['spu_sprice']) == '') {
                        venus_db_rollback();//回滚事务
                        venus_throw_exception(1, "销售价不能为空");
                        return false;
                    }
                }
            } else {
                $result = $result && SpuDao::getInstance($this->waCode)->updateSpriceCodeByCode($spuData['spu_code'], $spuData['spu_sprice']);
            }
        }
        if ($result) {
            venus_db_commit();
            $SkuService = new SkuService();
            $SkuService->release_latestsku($this->waCode);
            $success = true;
            $message = "导入内部销售价成功";
        } else {
            venus_db_rollback();
            $success = false;
            $message = "导入内部销售价失败";
        }
        return array($success, "", $message);
    }

    //4.SPU导入内部采购价
    public function bprice_import()
    {

        $datas = ExcelService::getInstance()->upload("file");
        $dicts = array(
            "A" => "spu_code",//spu品类编号
            "M" => "spu_bprice",//采购价
        );

        $spuList = array();
        foreach ($datas as $sheetName => $list) {
            unset($list[0]);
            $spuList = array_merge($spuList, $list);
        }

        $dataArr = array();
        venus_db_starttrans();//启动事务
        $result = true;
        foreach ($spuList as $index => $spuItem) {
            $spuData = array();
            foreach ($dicts as $col => $key) {
                $spuData[$key] = isset($spuItem[$col]) ? $spuItem[$col] : "";
            }

            if (trim($spuData['spu_code']) == '' || trim($spuData['spu_bprice']) == '') {
                if (trim($spuData['spu_code']) == '' && trim($spuData['spu_bprice']) == '') {
                    continue;
                } else {
                    if (trim($spuData['spu_code']) == '') {
                        venus_db_rollback();//回滚事务
                        venus_throw_exception(1, "货品编号不能为空");
                        return false;
                    }

                    if (trim($spuData['spu_bprice']) == '') {
                        venus_db_rollback();//回滚事务
                        venus_throw_exception(1, "采购价不能为空");
                        return false;
                    }
                }
            } else {
                $result = $result && SpuDao::getInstance($this->waCode)->updateBpriceCodeByCode($spuData['spu_code'], $spuData['spu_bprice']);
            }
        }
        if ($result) {
            venus_db_commit();
            $SkuService = new SkuService();
            $SkuService->release_latestsku($this->waCode);
            $success = true;
            $message = "导入内部采购价成功";

        } else {
            venus_db_rollback();
            $success = false;
            $message = "导入内部采购价失败";
        }
        return array($success, "", $message);
    }

    //5.SPU导入供货商设置
    public function supplier_import()
    {

        $datas = ExcelService::getInstance()->upload("file");
        $dicts = array(
            "B" => "spu_code",//spu品类编号
            "R" => "sup_code"//供货商编号
        );

        $supList = array();
        foreach ($datas as $sheetName => $list) {
            unset($list[0]);
            $supList = array_merge($supList, $list);
        }

        $dataArr = array();
        venus_db_starttrans();//启动事务
        $result = true;
        foreach ($supList as $index => $supItem) {
            $supData = array();
            foreach ($dicts as $col => $key) {
                $supData[$key] = isset($supItem[$col]) ? $supItem[$col] : "";
            }

            if (trim($supData['spu_code']) == '' || trim($supData['sup_code']) == '') {
                if (trim($supData['spu_code']) == '' && trim($supData['sup_code']) == '') {
                    continue;
                } else {
                    if (trim($supData['spu_code']) == '') {
                        venus_db_rollback();//回滚事务
                        venus_throw_exception(1, "货品编号不能为空");
                        return false;
                    }

                    if (trim($supData['sup_code']) == '') {
                        venus_db_rollback();//回滚事务
                        venus_throw_exception(1, "供货商不能为空");
                        return false;
                    }
                }
            } else {
                $result = $result && SpuDao::getInstance($this->waCode)->updateSupCodeByCode($supData['spu_code'], $supData['sup_code']);
            }
        }
        if ($result) {
            venus_db_commit();
            $success = true;
            $message = "导入供货商成功";
        } else {
            venus_db_rollback();
            $success = false;
            $message = "导入供货商失败";
        }
        return array($success, "", $message);
    }

    //6.下载全部SPU数据
    public function news_spu_export()
    {
        $exwarcode = $_POST['data']['exwCode'];
        if (empty($exwarcode)) {
            venus_throw_exception(1, "客户编号不能为空");
            return false;
        }

        $cond = array();
        $page = 0;
        $count = 10000;
        $result = SpuDao::getInstance($this->waCode)->queryListByCondition($cond, $page, $count);

        $vv = array();
        $spuBprice = array();
        $fname = "SPU全部数据";
        $spuBprice[$fname][] = array('SPU编号', '品名', '二级分类', '二级名称', '品牌', '规格', '单位', '产地', '仓储方式', '仓储方式名称', '备注', '内部销售价', '内部采购价', '供货商', '客户利润率', '客户编号');
        foreach ($result as $k => $v) {
            $vv = array(
                "spCode" => $v['spu_code'],
                "spName" => $v['spu_name'],
                "spuSubtype" => $v['spu_subtype'],
                "spBrand" => $v['spu_brand'],
                "spNorm" => $v['spu_norm'],
                "spUnit" => $v['spu_unit'],
                "spFrom" => $v['spu_from'],
                "spTgtype" => $v['spu_storetype'],
                "spMark" => $v['spu_mark'],
                "spSprice" => $v['spu_sprice'],
                "spBprice" => $v['spu_bprice'],
                "suCode" => $v['sup_code'],
                "proPercent" => $v['pro_percent'],
                "exwarCode" => $v['exwar_code']
            );
            $spuBprice[$fname][] = array($vv['spCode'], $vv['spName'], $vv['spuSubtype'], venus_spu_catalog_name($vv['spuSubtype']), $vv['spBrand'], $vv['spNorm'], $vv['spUnit'], $vv['spFrom'], $vv['spTgtype'], venus_spu_storage_desc($vv['spTgtype']), $vv['spMark'], $vv['spSprice'], $vv['spBprice'], $vv['suCode'], $vv['proPercent'], $exwarcode);
        }
        $fileName = ExcelService::getInstance()->exportExcel($spuBprice, '', "001");
        if ($fileName) {
            $success = true;
            $data = $fileName;
            $message = "";
            return array($success, $fileName, $message);
        } else {
            $success = false;
            $data = "";
            $message = "下载失败";
        }
        return array($success, $data, $message);
    }


    //6.1下载全部SPU数据
    public function spu_export()
    {

        $result = SpuDao::getInstance()->queryAllList();

        $spuBprice = array();
        $fname = "SPU全部数据";
        $header = array("SKU编号", "SPU编号", "一级分类", "一级名称", "二级分类", "二级名称	","仓储方式", "仓储方式名称", "品名","品牌", "产地", "备注", "图片", "可计算最小单位", "规格", "规格单位", "内部销售价", "供货商编号	","客户编号", "客户利润率", "规格", "单位", "所含标准货品数量", "备注");
        foreach ($result as $k => $v) {
            $vv = array(
                "skCode" => $v['sku_code'],
                "spCode" => $v['spu_code'],
                "spType" => $v['spu_type'],
                "spuSubtype" => $v['spu_subtype'],
                "spTgtype" => $v['spu_storetype'],
                "spName" => $v['spu_name'],
                "spBrand" => $v['spu_brand'],
                "spFrom" => $v['spu_from'],
                "spMark" => $v['spu_mark'],
                "spNorm" => $v['spu_norm'],
                "spUnit" => $v['spu_unit'],
                "spImg" => $v['spu_img'],
                "spCunit" => $v['spu_cunit'],
                "spSprice" => $v['spu_sprice'],
                "skNorm" => $v['sku_norm'],
                "skUnit" => $v['sku_unit'],
                "spCount" => $v['spu_count'],
                "skMark" => $v['sku_mark']
            );
            $spuBprice[$fname][] = array(
                $vv['skCode'], $vv['spCode'], $vv['spType'], venus_spu_type_name($vv['spType']),
                $vv['spuSubtype'],
                venus_spu_catalog_name($vv['spuSubtype']), $vv['spTgtype'],
                venus_spu_storage_desc($vv['spTgtype']), $vv['spName'],
                $vv['spBrand'], $vv['spFrom'], $vv['spMark'], $vv['spImg'], $vv['spCunit'],
                $vv['spNorm'], $vv['spUnit'], $vv['spSprice'],
                '', '', '', $vv['skNorm'], $vv['skUnit'], $vv['spCount'], $vv['skMark']);
        }

        $fileName = ExcelService::getInstance()->exportExcel($spuBprice, $header, "001");

        if ($fileName) {
            $success = true;
            $data = $fileName;
            $message = "";
            return array($success, $data, $message);
        } else {
            $success = false;
            $data = "";
            $message = "下载失败";
        }
        return array($success, $data, $message);
    }


}



