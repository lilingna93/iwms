<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * SKU数据
 * Class SkuDao
 * @package Wms\Dao
 */
class SkuDao extends BaseDao implements BaseDaoInterface
{


    /**
     * SkuDao constructor.
     */
    function __construct()
    {
    }

    /**
     * @param $data
     * @return mixed
     */
    public function insert($data)
    {
//        $code = venus_unique_code("SK");
//        $data['sku_code'] = $code;
        $data['war_code'] = $this->warehousecode;
        return M("sku")->add($data);
    }

    //查询

    /**
     * @param $code
     * @return mixed
     */
    public function queryByCode($code)
    {
        //$condition = array("sku.war_code" => $this->warehousecode, "sku.sku_code" => $code);
        $condition = array("sku.sku_code" => $code);
        return M("sku")->alias('sku')->field('*,sku.sku_code,spu.spu_code')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('spu.spu_code desc')->fetchSql(false)->find();
    }


    //查询

    /**
     * @param $cond
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryListByCondition($cond, $page = 0, $count = 100)
    {
        $condition = array();
        $skujoinconds = array();
        if (isset($cond["%name%"])) {
            $skuname = str_replace(array("'", "\""), "", $cond["%name%"]);
            array_push($skujoinconds, "spu.spu_name LIKE '%{$skuname}%'");
        }
        if (isset($cond["name"])) {
            array_push($skujoinconds, "spu.spu_name = " . $cond["name"]);
        }

        if (isset($cond["abname"])) {
            $spuabname = str_replace(array("'", "\""), "", $cond["abname"]);
            array_push($skujoinconds, "spu.spu_abname LIKE '%#{$spuabname}%'");
        }

        if (isset($cond["type"])) {
            array_push($skujoinconds, "spu.spu_type = " . $cond["type"]);
        }
        if (isset($cond["subtype"])) {
            array_push($skujoinconds, "spu.spu_subtype = " . $cond["subtype"]);
        }
        if (isset($cond["status"])) {
            array_push($skujoinconds, "sku.sku_status = " . $cond["status"]);
        }
        if (isset($cond["spustatus"])) {
            array_push($skujoinconds, "spu.spu_status = " . $cond["spustatus"]);
        }
        $skujoinconds = empty($skujoinconds) ? "" : " AND " . implode(" AND ", $skujoinconds);


//        return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
//            ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
//            ->where($condition)->order('spu_subtype asc')->limit("{$page},{$count}")->fetchSql(true)->select();


        if (isset($cond["exwarcode"])) {
            $exwarcode = str_replace(array("'", "\""), "", $cond["exwarcode"]);
            array_push($projoinconds, "pro.exwar_code = '{$exwarcode}'");
            $projoinconds = empty($projoinconds) ? "" : " AND " . implode(" AND ", $projoinconds);
            return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
                ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
                ->join("JOIN wms_profit pro ON pro.spu_code = spu.spu_code {$projoinconds}")
//                ->where($condition)->order('sku.sku_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
                ->where($condition)->order('spu_subtype asc')->limit("{$page},{$count}")->fetchSql(false)->select();
        } else {
            return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
                ->join("JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
//                ->where($condition)->order('sku.sku_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
                ->where($condition)->order('spu_subtype asc')->limit("{$page},{$count}")->fetchSql(false)->select();
        }


    }

    /**
     * @param $cond
     * @return mixed
     */
    public function queryCountByCondition($cond)
    {
        //$condition = array("sku.war_code" => $this->warehousecode);
        $condition = array();
        $skujoinconds = array();
        $projoinconds = array();
        if (isset($cond["%name%"])) {
            $skuname = str_replace(array("'", "\""), "", $cond["%name%"]);
            array_push($skujoinconds, "spu.spu_name LIKE '%{$skuname}%'");
        }
        if (isset($cond["name"])) {
            array_push($skujoinconds, "spu.spu_name = " . $cond["name"]);
        }
        if (isset($cond["abname"])) {
            $spuabname = str_replace(array("'", "\""), "", $cond["abname"]);
            array_push($skujoinconds, "spu.spu_abname LIKE '%#{$spuabname}%'");
        }
        if (isset($cond["type"])) {
            array_push($skujoinconds, "spu.spu_type = " . $cond["type"]);
        }
        if (isset($cond["subtype"])) {
            array_push($skujoinconds, "spu.spu_subtype = " . $cond["subtype"]);
        }
        if (isset($cond["status"])) {
            array_push($skujoinconds, "sku.sku_status = " . $cond["status"]);
        }
        if (isset($cond["spustatus"])) {
            array_push($skujoinconds, "spu.spu_status = " . $cond["spustatus"]);
        }
        $skujoinconds = empty($skujoinconds) ? "" : " AND " . implode(" AND ", $skujoinconds);
        if (isset($cond["exwarcode"])) {
            $exwarcode = str_replace(array("'", "\""), "", $cond["exwarcode"]);
            array_push($projoinconds, "pro.exwar_code = '{$exwarcode}'");
            $projoinconds = empty($projoinconds) ? "" : " AND " . implode(" AND ", $projoinconds);
            return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
                ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
                ->where($condition)->order('sku.sku_code desc')->fetchSql(false)->count();
        } else {
            return M("sku")->alias('sku')->field('*,spu.spu_code,sku.sku_code')
                ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code {$skujoinconds}")
                ->where($condition)->order('sku.sku_code desc')->fetchSql(false)->count();
        }

    }

    //更新货品状态(2018-07-19 新添加)

    /**
     * @param $code
     * @param $skuStatus
     * @return mixed
     */
    public function updateStatusCodeByCode($code)
    {
//        $condition['sku_code'] = array('in', $code);
        return M("sku")->where(array("sku_code"=>$code))
            ->save(array("timestamp" => venus_current_datetime(), "sku_status" => 2));
    }

//querySkuCodeBySpuCodeToIwms
//此方法仅用于副仓
    public function querySkuCodeBySpuCodeToIwms($spuCode)
    {
        return M("sku")->alias('sku')->where(array("spu_code" => $spuCode))->fetchSql(false)->getField("sku_code");
    }

    public function queryBySpuNameAndSkuNormAndSpuBrand($name, $norm, $brand = '')
    {
        $condition = array("sku.sku_norm" => $norm, "spu.spu_name" => $name);
        if (!empty($barnd)) {
            $condition['spu.spu_brand'] = $brand;
        }
        return M("sku")->alias('sku')->field('*,sku.sku_code,spu.spu_code')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('spu.spu_code desc')->fetchSql(false)->find();
    }
}