<?php

namespace Wms\Service;

use Common\Service\PassportService;
use Wms\Dao\ProfitDao;
use Wms\Dao\SkuDao;
use Wms\Dao\WarehouseDao;

class SkuService {

    public $waCode;
    function __construct()
    {
//        $workerData = PassportService::getInstance()->loginUser();
//        if(empty($workerData)){
//            venus_throw_exception(110);
//        }
//        $this->waCode = $workerData["war_code"];
    }

    //1.SKU搜索
    public function sku_search() {
    
        $spName = $_POST['data']['spName'];
        $spType = $_POST['data']['spType'];
        $spSubtype = $_POST['data']['spSubtype'];
        $skStatus = $_POST['data']['skStatus'];
        $pageCurrent = $_POST['data']['pageCurrent'];//当前页码
        $pageSize = 100;//当前页面总条数


        if(!empty($spName) && preg_match ("/^[a-z]/i", $spName)){
            $condition['abname'] = $spName;
        }
        if (!empty($spName) && !preg_match ("/^[a-z]/i", $spName)) {//SPU名称
            $condition['%name%'] = $spName;
        }

        if (!empty($spType)) {//一级分类编号
            $condition['type'] = $spType;
        }

        if (!empty($spSubtype)) {//状态（上、下线）
            $condition['subtype'] = $spSubtype;
        }

        if (!empty($skStatus)) {//客户仓库
            $condition['status'] = $skStatus;
        }

        //当前页码
        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }

        $SkuDao = SkuDao::getInstance($this->waCode);
        $totalCount = $SkuDao->queryCountByCondition($condition);//获取指定条件的总条数
        $pageLimit = pageLimit($totalCount, $pageCurrent);
        $results = $SkuDao->queryListByCondition($condition, $pageLimit['page'], $pageLimit['pSize']);


        if (empty($results)) {
            $skuList = array(
                "pageCurrent" => 0,
                "pageSize" => 100,
                "totalCount" => 0
            );
            $skuList["list"] = array();
        } else {
            $skuList = array(
                "pageCurrent" => $pageCurrent,
                "pageSize" => $pageSize,
                "totalCount" => $totalCount
            );
            foreach ($results as $k => $val) {
                if(!empty($val['sku_mark'])){
                  $skMark = "(".$val['sku_mark'].")";
                }
                $skuList["list"][$k] = array(
                        "skCode" => $val['sku_code'],//SKU编号
                        "spCode" => $val['spu_code'],//所属SPU编码
                        "spName" => $val['spu_name'].$skMark,//SPU货品名称
                        "spCount" => $val['spu_count'],//规格数量
                        "skUnit" => $val['sku_unit'],//规格单位
                        "skNorm" => $val['sku_norm'],//规格
                        "skStatus" => $val['sku_status']//状态（上、下线）
                );
            }
        }
        return array(true, $skuList, "");
    }


    //释放最新的sku数据
    public static function release_latestsku(){
        $skuFilePath = C("SKU_FILE_PATH")."latestsku.txt";
        if(file_exists($skuFilePath)){
            unlink($skuFilePath);
            unlink(C("SKU_FILE_PATH")."skuver.txt");
            S(C("SKU_VERSION_KEY"),null);
        }
    }

    //获取最新的sku数据
    public static function latestsku(){
        $skuFilePath = C("SKU_FILE_PATH")."latestsku.txt";

        if(false && file_exists($skuFilePath)){
            $skuData = file_get_contents($skuFilePath);
            return array(true, $skuData,  "已存在");
        }

        //重新生成相应文件
        $typedict = C("SPU_TYPE_DICT");
        $subtypedict = C("SPU_SUBTYPE_DICT");
        $condition=array("status"=>1);
        $skuList = SkuDao::getInstance()->queryListByCondition($condition,0,3000);
        $dict = array();
        $map = array();
        foreach ($skuList as $skuItem){
            $type       = $skuItem["spu_type"];     //一级类型
            $typename   = $typedict[$type];         //一级名称
            $subtype    = $skuItem["spu_subtype"];  //二级类型
            $subtypename = $subtypedict[$subtype];  //二级名称
            $skucode    = $skuItem["spu_code"];     //sku编号
            $mark = $skuItem["spu_mark"];
            if(false && !isset($dict[$skucode])){
                $dict[$skucode] = array(
                    "spName"    =>  $skuItem["spu_name"].(!empty($mark)?"[{$mark}]":""),
                    "spAbName"  =>  $skuItem["spu_abname"],
                    "skBrand"   =>  $skuItem["spu_brand"],
                    "skCode"    =>  $skuItem["sku_code"],//
                    "skNorm"    =>  $skuItem["sku_norm"],//规格数据
                    "skUnit"    =>  $skuItem["sku_unit"],
                    "skCunit"    =>  $skuItem["spu_cunit"],
                    "skImg"  =>  (empty($skuItem["spu_img"])?"_":$skuItem["spu_code"]).".jpg?_=".C("SKU_IMG_VERSION"),
                );
            }
            if(!isset($map[$type])){
                $map[$type] = array(
                    "tCode"=>$type,
                    "tName"=>$typename,
                    "{$type}"=>array(
                        "0"=>array(
                            "cName"=>"全部",
                            "cCode"=>0,
                            "list"=>array()
                        )
                    )
                );
            }

            //$map[$type][$type]["0"]["list"][] = $skucode;

            if(!isset($map[$type][$type][$subtype])){
                $map[$type][$type][$subtype] = array(
                    "cName"=>"{$subtypename}",
                    "cCode"=>"{$subtype}",
                    "list"=>array()
                );
            }

            //$map[$type][$type][$subtype]["list"][] = $skucode;
        }
        $list = array_values($map);
        foreach ($list as $index=>$item ){
            $key = $item["tCode"];
            $list[$index][$key] = array_values($item[$key]);
        }

        $skuData = json_encode(array("R"=>$list, "D"=>$dict));
        file_put_contents($skuFilePath, $skuData);

        $skuversion = md5($skuData);
        S(C("SKU_VERSION_KEY"),$skuversion,3600*24*365);
        file_put_contents(C("SKU_FILE_PATH")."skuver.txt",$skuversion);
        return array(true,$skuData,"新创建");

    }

    
    public static function latestminisku(){




        //重新生成相应文件
        $typedict = C("SPU_TYPE_DICT");
        $subtypedict = C("SPU_SUBTYPE_DICT");
        $condition=array("status"=>1);
        $skuList = SkuDao::getInstance()->queryListByCondition($condition,0,3000);
        $dict = array();
        $map = array();
        foreach ($skuList as $skuItem){
            $type       = $skuItem["spu_type"];     //一级类型
            $typename   = $typedict[$type];         //一级名称
            $subtype    = $skuItem["spu_subtype"];  //二级类型
            $subtypename = $subtypedict[$subtype];  //二级名称
            $spucode    = $skuItem["spu_code"];     //sku编号
            $mark = $skuItem["spu_mark"];
            if(false && !isset($dict[$spucode])){
                $dict[$spucode] = array(
                    "spName"    =>  $skuItem["spu_name"].(!empty($mark)?"[{$mark}]":""),
                    "spAbName"  =>  $skuItem["spu_abname"],
                    "skBrand"   =>  $skuItem["spu_brand"],
                    "skCode"    =>  $skuItem["sku_code"],//
                    "skNorm"    =>  $skuItem["sku_norm"],//规格数据
                    "skUnit"    =>  $skuItem["sku_unit"],
                    "skCunit"    =>  $skuItem["spu_cunit"],
                    "skImg"  =>  (empty($skuItem["spu_img"])?"_":$skuItem["spu_code"]).".jpg?_=".C("SKU_IMG_VERSION"),
                );
            }
            if(!isset($map[$type])){
                $map[$type] = array(
                    "tCode"=>$type,
                    "tName"=>$typename,
//                    "{$type}"=>array(
//                        "0"=>array(
//                            "cName"=>"全部",
//                            "cCode"=>0,
//                            "list"=>array()
//                        )
//                    )
                );
            }
            //$map[$type][$type]["0"]["list"][] = $spucode;

//            if(!isset($map[$type][$type][$subtype])){
//                $map[$type][$type][$subtype] = array(
//                    "cName"=>"{$subtypename}",
//                    "cCode"=>"{$subtype}",
//                    "list"=>array()
//                );
//            }
            //$map[$type][$type][$subtype]["list"][] = $spucode;
        }
        $list = array_values($map);
//        foreach ($list as $index=>$item ){
//            $key = $item["tCode"];
//            $list[$index][$key] = array_values($item[$key]);
//        }
        $skuData = json_encode(array("R"=>$list, "D"=>$dict));
        //$skuFilePath = C("SKU_FILE_PATH")."latestminisku.txt";
        //file_put_contents($skuFilePath,$skuData);

        return array(true,$skuData,"");
    }




}



