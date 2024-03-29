<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * SKU数据
 * Class SkuDao
 * @package Wms\Dao
 */
class SkudictDao extends BaseDao implements BaseDaoInterface
{


    /**
     * SkuDao constructor.
     */
    function __construct() {
    }

    /**
     * @param $data
     * @return mixed
     */
    public function insert($data) {
        $spucode = $data["spucode"];
        $skucode = $data["skucode"];
        $supcode = (!empty($spucode)&&!empty($spucode))?"SU00000000000001":$data["supcode"];
        $spucode = !empty($spucode)?$spucode:venus_unique_code("PD");
        $skucode = !empty($skucode)?$skucode:venus_unique_code("KD");
        $spudata = array(
            "spu_code"=>$spucode,
            "spu_name"  =>$data["spuname"],
            "spu_abname"=>$data["spuabname"],
            "spu_type"  =>$data["sputype"],
            "spu_subtype"   =>$data["spusubtype"],
            "spu_reptype"   =>$data["spureptype"],
            "spu_storetype" =>$data["spustoretype"],
            "spu_brand"     =>$data["spubrand"],//品牌
            "spu_from"      =>$data["spufrom"],//产地
            "spu_norm"      =>$data["spunorm"],//规格
            "spu_cunit"     =>$data["spucunit"],//最小计量单位1,0.1,0.01
            "spu_unit"      =>$data["spuunit"],//单位
            "spu_mark"      =>$data["spumark"],//备注
            "spu_bprice"    =>0,//采购价，默认0，以后删除
            "spu_sprice"    =>0,//销售价，默认0，以后删除
            "spu_img"       =>"",//图片
            "sup_code"      =>$supcode,//供应商编码
            "spu_status"    =>1,
            "war_code"      =>"",//所属仓库编号，默认空，以后删除
            "timestamp"     =>venus_current_datetime(),
        );
        $skudata = array(
            "sku_code"  =>$skucode,
            "sku_norm"  =>$data["spunorm"],
            "sku_unit"  =>$data["spuunit"],
            "spu_code"  =>$spucode,
            "spu_count" =>1,
            "sku_mark"  =>$data["spumark"],
            "sku_status"=>1,
            "war_code"  =>"",//所属仓库编号，默认空，以后删除
            "timestamp" =>venus_current_datetime(),
        );
        return (M("sku")->add($skudata) && M("spu")->add($spudata))?$spucode:false;
    }
    //条件过滤
    private function conditionFilter($cond) {
        $condition = array( "spu_status" => 1);
        if (isset($cond["sputype"])) {
            $condition["spu_type"] = $cond["sputype"];
        }
        if (isset($cond["spusubtype"])) {
            $condition["spu_subtype"] = $cond["spusubtype"];
        }
        if (isset($cond["supcode"])) {
            $condition["spu.sup_code"] = $cond["supcode"];
        }
        if (isset($cond["spcode"])) {
            $condition["spu.spu_code"] = $cond["spcode"];
        }
        if (isset($cond["%name%"])) {
            $spuname = $cond["%name%"];
            $condition["spu_name"] = array('like', "%{$spuname}%");
        }
        return $condition;
    }

    //查询货品数据
    public function queryByCode($code){
        return M("spu")->where(array("spu_code"=>$code))->find();
    }


    //2018-10-14 新修改的
    public function queryListByCondition($condition, $page = 0, $count = 100){
        $condition = $this->conditionFilter($condition);
        return M("spu")->alias('spu')->field('*,spu.spu_code')
            ->join("LEFT JOIN wms_sku sku ON sku.spu_code = spu.spu_code")
            ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code")
            ->where($condition)->order('spu.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    //查询符合条件货品数量
    public function queryCountByCondition($condition){
        $condition = $this->conditionFilter($condition);
        return M("spu")->alias('spu')->where($condition)->fetchSql(false)->count();
    }

    //删除某商品字典
    public function deleteByCode($code){
        return M("spu")->where(array("spu_code"=>$code,"sup_code"=>array("NEQ","SU00000000000001")))
            ->save(array("timestamp" => venus_current_datetime(), "spu_status" => 2));
    }

    //更新供货商编号
    public function updateSupByCode($code,$supcode){
        return M("spu")->where(array("spu_code"=>$code,"sup_code"=>array("NEQ","SU00000000000001")))
            ->save(array("timestamp" => venus_current_datetime(), "sup_code" => $supcode));
    }

    //更新报表类型编号
    public function updateSpureptypeByCode($code,$spurepcode){
        return M("spu")->where(array("spu_code"=>$code))
            ->save(array("timestamp" => venus_current_datetime(), "spu_reptype" => $spurepcode));
    }

}