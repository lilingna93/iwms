<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * 货品库存批次
 * Class GoodstoredDao
 * @package Wms\Dao
 */
class GoodstoredDao extends BaseDao implements BaseDaoInterface
{

    /**
     * GoodstoredDao constructor.
     */
    function __construct()
    {

    }
    //添加数据[init,count,bprice,gbcode,poscode,spucode]

    /**
     * @param $item
     * @return bool
     */
    public function insert($item)
    {
        $code = venus_unique_code("GW");
        $data = array(
            "gs_code" => $code,
            "gs_init" => $item["init"],     //初次写入的货品数量，即spu的数量
            "gs_count" => $item["count"],   //当前货品数量，即spu的实际数量
            "gb_bprice" => $item["bprice"], //货品的采购价格，即spu的采购价格

            "gb_code" => $item["gbcode"],   //所属入仓货品批次表激励编号
            "pos_code" => $item["poscode"], //仓库货位编号

            "spu_code" => $item["spucode"], //spu编号

            "sku_code" => $item["skucode"], //sku编号，货品采购和上架时的规格数据信息
            "sku_init" => $item["skucount"],//sku采购数量，即按货品采购时规格的采购数量
            "sku_count" => $item["skucount"],//sku的实际数量

            "sup_code" => $item["supcode"],//货品供货商编号
            "war_code" => $this->warehousecode,//所属仓库
        );
        return M("Goodstored")->add($data) ? $code : false;
    }

    //查询

    /**
     * @param $code
     * @return mixed
     */
    public function queryByCode($code)
    {
        $condition = array("gs.war_code" => $this->warehousecode, "gs_code" => $code);
        return M("Goodstored")->alias('gs')->field('*,spu.spu_code,sku.sku_code,gs.sup_code')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = gs.sku_code")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('gs.gs_code desc')->fetchSql(false)->find();
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
        $condition = array("gs.war_code" => $this->warehousecode);
        return M("Goodstored")->alias('gs')->field('*,spu.spu_code,sku.sku_code,gs.sup_code')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = gs.sku_code")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('gs.gs_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }
    //总数

    /**
     * @param $cond
     * @return mixed
     */
    public function queryCountByCondition($cond)
    {
        $condition = array("gs.war_code" => $this->warehousecode);
        return M("Goodstored")->alias('gs')->field('*,spu.spu_code,sku.sku_code,gs.sup_code')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = gs.sku_code")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('gs.gs_code desc')->fetchSql(false)->count();
    }

    //获取仓位编码

    /**
     * @param $code
     * @return mixed
     */
    public function queryPoscodeByCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "gb_code" => $code);
        return M("Goodstored")->where($condition)->getField("pos_code");
    }

    //更新货品数量

    /**
     * @param $code
     * @param $count
     * @return mixed
     */
    public function updateByCode($code, $count)
    {
        $condition = array("war_code" => $this->warehousecode, "gs_code" => $code);
        return M("Goodstored")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(),
                "gs_count" => $count));
    }

    /**
     * 入仓单根据货品批次单号，更新数量
     * @param $code
     * @param $count
     * @param $bprice
     * @param $count
     * @return mixed
     */
    public function updateByGbCode($code, $count)
    {
        $condition = array("war_code" => $this->warehousecode, "gb_code" => $code);
        return M("Goodstored")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(),
                "gs_count" => $count));
    }

    //减少sku数量
    public function updateSkuCountByCode($code, $count)
    {
        $condition = array("war_code" => $this->warehousecode, "gs_code" => $code);
        return M("Goodstored")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(),
                "sku_count" => $count));
    }

    /**
     * @param $code
     * @param $init
     * @param $count
     * @param $skcount
     * @return mixed
     * 退货修改库存信息
     */
    public function updateInitAndSkuInitAndCountAndSkuCountByCode($code, $init, $count, $skinit, $skcount)
    {
        $condition = array("war_code" => $this->warehousecode, "gs_code" => $code);
        return M("Goodstored")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(),
                "gs_init" => $init, "gs_count" => $count, "sku_init" => $skinit, "sku_count" => $skcount));
    }

    //同时更新count和skucount
    public function updateCountAndSkuCountByCode($code, $count, $skcount)
    {
        $condition = array("war_code" => $this->warehousecode, "gs_code" => $code);
        return M("Goodstored")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(),
                "sku_count" => $skcount, "gs_count" => $count));
    }

    //查询

    /**
     * @param $code
     * @return mixed
     */
    public function queryListBySpuCode($code, $page = 0, $count = 100)
    {
        $condition = array("gs.war_code" => $this->warehousecode);
        return M("Goodstored")->alias('gs')->field('*,spu.spu_code,sku.sku_code,spu.spu_sprice,gs.sku_count,gs.sup_code')
            ->join("JOIN wms_goodsbatch gb ON gb.gb_code = gs.gb_code AND gs.spu_code = '{$code}'")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code AND spu.spu_code = '{$code}'")
            ->where($condition)->order('gs.gs_code asc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    public function queryCountBySpuCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "spu_code" => $code);
        return M("Goodstored")->where($condition)->fetchSql(false)->count();
    }


    public function queryListBySkuCode($code, $page = 0, $count = 100)
    {
        $condition = array("gs.war_code" => $this->warehousecode);
        return M("Goodstored")->alias('gs')->field('*,spu.spu_code,sku.sku_code,gs.sku_count,gs.sup_code,gb.gb_ctime')
            ->join("JOIN wms_goodsbatch gb ON gb.gb_code = gs.gb_code AND gs.sku_code = '{$code}'")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code AND gb.sku_code = '{$code}'")
            ->join("JOIN wms_spu spu ON spu.spu_code = gs.spu_code ")
            ->where($condition)->order('gs.gs_code asc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }


    public function queryBySkuCodeAndRecEcode($code, $ecode)
    {
        $condition = array("gs.war_code" => $this->warehousecode);
        return M("Goodstored")->alias('gs')->field('*,spu.spu_code,sku.sku_code,gs.sku_count,gs.sup_code')
            ->join("JOIN wms_goodsbatch gb ON gb.gb_code = gs.gb_code AND gs.sku_code = '{$code}'")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code AND rec.rec_ecode = '{$ecode}'")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code AND gb.sku_code = '{$code}'")
            ->join("JOIN wms_spu spu ON spu.spu_code = gs.spu_code")
            ->where($condition)->order('gs.gs_code asc')->fetchSql(false)->find();
    }

    //查找不是同规格的货品列表
    public function queryListNotSkuBySpuCode($code, $skuCode, $page = 0, $count = 100)
    {
        $condition = array("gs.war_code" => $this->warehousecode, "gs.sku_code" => array("neq", $skuCode));
        return M("Goodstored")->alias('gs')->field('*,spu.spu_code,sku.sku_code,gs.sku_count,gs.sup_code')
            ->join("JOIN wms_goodsbatch gb ON gb.gb_code = gs.gb_code AND gs.spu_code = '{$code}'")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code AND spu.spu_code = '{$code}'")
            ->where($condition)->order('gs.gs_code asc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    public function queryCountBySkuCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "sku_code" => $code);
        return M("Goodstored")->where($condition)->fetchSql(false)->count();
    }


    public function deleteByCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "gs_code" => $code);
        return M("Goodstored")->where($condition)->fetchSql(false)
            ->delete();
    }

    public function queryBySpuCodeAndInitAndRecEcode($spCode, $init, $oCode)
    {
        $condition = array("gs.war_code" => $this->warehousecode, "gs.gs_init" => $init);
        return M("Goodstored")->alias('gs')->field('*,spu.spu_code,sku.sku_code,gs.sku_count,gs.sup_code')
            ->join("JOIN wms_goodsbatch gb ON gb.gb_code = gs.gb_code AND gs.spu_code = '{$spCode}'")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code AND gs.spu_code = '{$spCode}'")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code AND rec.rec_ecode = '{$oCode}'")
            ->join("JOIN wms_spu spu ON spu.spu_code = gs.spu_code AND gb.spu_code = '{$spCode}'")
            ->where($condition)->order('gs.gs_code asc')->fetchSql(false)->find();
    }

    public function queryBySkuCodeAndRecEcodeAndSkInit($code, $ecode, $skInit)
    {
        $condition = array("gs.war_code" => $this->warehousecode, "gb.sku_count" => $skInit, "gs.sku_init" => $skInit);
        return M("Goodstored")->alias('gs')->field('*,spu.spu_code,sku.sku_code,gs.sku_count,gb.rec_code,gs.sup_code')
            ->join("JOIN wms_goodsbatch gb ON gb.gb_code = gs.gb_code AND gs.sku_code = '{$code}'")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code AND rec.rec_ecode = '{$ecode}'")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code AND gb.sku_code = '{$code}'")
            ->join("JOIN wms_spu spu ON spu.spu_code = gs.spu_code")
            ->where($condition)->order('gs.gs_code asc')->fetchSql(false)->find();
    }

    /**
     * @param $code
     * @param $init
     * @param $count
     * @param $skcount
     * @return mixed
     * 退货修改库存信息(旧版老接口，已废弃)
     */
    public function updateInitAndCountAndSkuCountByCode($code, $init, $count, $skcount)
    {
        $condition = array("war_code" => $this->warehousecode, "gs_code" => $code);
        return M("Goodstored")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(),
                "sku_count" => $skcount, "gs_init" => $init, "gs_count" => $count));
    }

    /**
     * @param $code
     * @return mixed
     * 20190322新增，根据批次查找库存信息
     */
    public function queryByGbCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "gb_code" => $code);
        return M("Goodstored")->where($condition)->fetchSql(false)->find();
    }

    /**
     * @param $code
     * @param $bprice
     * @return mixed
     * 20190410新增修改入仓单批次货品价格
     */
    public function updateBpriceByCode($code, $bprice)
    {
        $condition = array("war_code" => $this->warehousecode, "gs_code" => $code);
        return M("Goodstored")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(),
                "gb_bprice" => $bprice));
    }
}