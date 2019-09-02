<?php
namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * SPU数据
 * Class SpuDao
 * @package Wms\Dao
 */
class SpuDao extends BaseDao implements BaseDaoInterface {


    /**
     * SpuDao constructor.
     */
    function __construct() {
    }

    //查询
    /**
     * @param $data
     * @return mixed
     */
    public function insert($data) {
        $data['war_code']=$this->warehousecode;
        return M("spu")->add($data);
    }

    //查询
    /**
     * @param $code
     * @return mixed
     */
    public function queryByCode($code) {
        $condition = array("spu.spu_code" => $code);
        return M("spu")->alias('spu')->field('*,spu.spu_code')
            ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code")
            ->where($condition)->order('spu.spu_code desc')->fetchSql(false)->find();
    }

    //查询
    /**
     * @param $cond
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryListByCondition($cond, $page = 0, $count = 100) {
        //$condition = array("spu.war_code" => $this->warehousecode);
        $condition = array();
        $joinconds = array();
        if (isset($cond["%name%"])) {
            $spuname = str_replace(array("'","\""),"",$cond["%name%"]);
            $condition["spu_name"] = array('like', "%{$spuname}%");
        }

        if (isset($cond["name"])) {
            $condition["spu_name"] = $cond["name"];
        }

        if (isset($cond["abname"])) {
            $spuabname = str_replace(array("'","\""),"",$cond["abname"]);
            $condition["spu_abname"] = array('like', "%#{$spuabname}%");
        }

        if (isset($cond["type"])) {
            $condition["spu_type"] = $cond["type"];
        }

        if (isset($cond["subtype"])) {
            $condition["spu_subtype"] = $cond["subtype"];
        }

        if (isset($cond["exwarcode"])) {
            $exwarcode= str_replace(array("'","\""),"",$cond["exwarcode"]);
            $joinconds[] = "pro.exwar_code = '{$exwarcode}'";
            $joinconds = empty($joinconds) ? "" : " AND " . implode(" AND ", $joinconds);
            return M("spu")->alias('spu')->field('*,spu.spu_code')
                ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code")
                ->where($condition)->order('spu.spu_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
        }else{
            return M("spu")->alias('spu')->field('*,spu.spu_code')
                ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code")
                ->where($condition)->order('spu.spu_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
        }
    }

    //总数
    /**
     * @param $cond
     * @return mixed
     */
    public function queryCountByCondition($cond) {
        //$condition = array("spu.war_code" => $this->warehousecode);
        $condition = array();
        $joinconds = array();
        if (isset($cond["%name%"])) {
            $spuname = str_replace(array("'","\""),"",$cond["%name%"]);
            $condition["spu_name"] = array('like', "%{$spuname}%");
        }
        if (isset($cond["name"])) {
            $condition["spu_name"] = $cond["name"];
        }
        if (isset($cond["abname"])) {
            $spuabname = str_replace(array("'","\""),"",$cond["abname"]);
            $condition["spu_abname"] = array('like', "%#{$spuabname}%");
        }

        if (isset($cond["type"])) {
            $condition["spu_type"] = $cond["type"];
        }
        if (isset($cond["subtype"])) {
            $condition["spu_subtype"] = $cond["subtype"];
        }

        if (isset($cond["exwarcode"])) {
            $exwarcode= str_replace(array("'","\""),"",$cond["exwarcode"]);
            $joinconds[] = "pro.exwar_code = '{$exwarcode}'";
            $joinconds = empty($joinconds) ? "" : " AND " . implode(" AND ", $joinconds);
            return M("spu")->alias('spu')->field('*,spu.spu_code')
                ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code")
                ->where($condition)->order('spu.spu_code asc')->fetchSql(false)->count();
        }else{
            return M("spu")->alias('spu')->field('*,spu.spu_code')
                ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code")
                ->where($condition)->order('spu.spu_code asc')->fetchSql(false)->count();
        }
    }

    //更新销售价(2018-07-19 新添加)
    /**
     * @param $code
     * @param $spu_sprice
     * @return mixed
     */
    public function updateSpriceCodeByCode($code, $spu_sprice) {
        return M("spu")
            ->where(array("war_code" => $this->warehousecode, "spu_code" => $code))
            ->save(array("timestamp" => venus_current_datetime(), "spu_sprice" => $spu_sprice));
    }

    //更新采购价(2018-07-19 新添加)
    /**
     * @param $code
     * @param $spu_bprice
     * @return mixed
     */
    public function updateBpriceCodeByCode($code, $spu_bprice) {
        return M("spu")
            ->where(array("war_code" => $this->warehousecode, "spu_code" => $code))
            ->save(array("timestamp" => venus_current_datetime(), "spu_bprice" => $spu_bprice));
    }

    //更新(2018-07-19 修改)
    /**
     * @param $code
     * @param $supCode
     * @return mixed
     */
    public function updateSupCodeByCode($code, $supCode) {
        return M("spu")
            ->where(array("war_code" => $this->warehousecode, "spu_code" => $code))
            ->save(array("timestamp" => venus_current_datetime(), "sup_code" => $supCode));
    }

    /**
     * 查询指定货品
     */
    public function queryOneByCondition($cond,$getField)
    {
        return M("spu")->where($cond)->getField($getField);
    }



    public function queryAllList($count = 10000) {
        return M("spu")->alias('spu')->field('*,spu.spu_code')
            ->join("LEFT JOIN wms_sku sku ON sku.spu_code = spu.spu_code")
            ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code")
            ->order('spu.spu_code asc')->limit($count)->fetchSql(false)->select();
    }

    //针对录入辅仓期初库存方法
    public function queryBySpuCode($code) {
        $condition = array("spu.spu_code" => $code);
        return M("spu")->alias('spu')->field('*,spu.spu_code')
            ->join("LEFT JOIN wms_sku sku ON sku.spu_code = spu.spu_code")
            ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code")
            ->where($condition)->order('spu.spu_code desc')->fetchSql(false)->find();
    }
}