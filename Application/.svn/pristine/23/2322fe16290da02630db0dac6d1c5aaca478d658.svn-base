<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * 库存数据
 * Class OrdergoodsDao
 * @package Wms\Dao
 */
class OrdergoodsDao extends BaseDao implements BaseDaoInterface
{

    function __construct()
    {
    }

    //添加数据[init,count,skucode,spucode,spucount,sprice,bprice,supcode,warcode,ucode]
    public function insert($item)
    {
        $code = venus_unique_code("G");
        $data = array(
            "goods_code" => $code,
            "goods_count" => $item["count"],//当前货品中，含有的spu的数量
            "goods_status" => 0,//是否已经收货
            "sku_code" => $item["skucode"],//spu编号
            "sku_init" => $item["skuinit"],//当前货品中下单时的sku的数量
            "sku_count" => $item["skuinit"],//当前货品中收件时sku的数量

            "spu_code" => $item["spucode"],//spu编号
            "spu_count" => $item["spucount"],//1个sku规格中含有的spu数量

            "spu_sprice" => $item["sprice"],    //spu的销售价
            "spu_bprice" => $item["bprice"],    //spu的采购价

            "pro_percent" => $item["ppercent"], //spu需要增加的客户利润

            "order_code" => $item["ocode"],//订单编号

            "supplier_code" => $item["supcode"],
            "war_code" => $item["warcode"],
            "user_code" => $item["ucode"],
        );
        return M("Ordergoods")->add($data) ? $code : false;
    }

    //查询
    public function queryByCode($code)
    {
        $condition = array("goods_code" => $code);
        return M("Ordergoods")->alias('goods')->field('*')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = goods.sku_code AND goods.goods_code = '{$code}'")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code AND goods.goods_code = '{$code}'")
            ->where($condition)
            ->find();
    }

    //查询
    public function queryListByOrderCode($ocode, $page = 0, $count = 100)
    {
        $condition = array("order_code" => $ocode);
        return M("Ordergoods")->alias('goods')->field('*')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = goods.sku_code AND goods.order_code ='{$ocode}'")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code AND goods.order_code ='{$ocode}'")
            ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code AND goods.order_code ='{$ocode}'")
            ->where($condition)->order('goods.goods_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    //查询
    public function queryCountByOrderCode($ocode)
    {
        $condition = array("order_code" => $ocode);
        return M("Ordergoods")->alias('goods')->field('*')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = goods.sku_code AND goods.order_code ='{$ocode}'")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code AND goods.order_code ='{$ocode}'")
            ->join("LEFT JOIN wms_supplier sup ON sup.sup_code = spu.sup_code AND goods.order_code ='{$ocode}'")
            ->where($condition)->fetchSql(false)->count();
    }

    //查询
    public function queryListByCondition($cond, $page = 0, $count = 100)
    {
        $condition = array();
        $joinconds = array();
        if (isset($cond["ocode"])) {
            array_push($joinconds, "goods.order_code = " . $cond["ocode"]);
        }
        if (isset($cond["ocodes"])) {
            $condition["order_code"] = array("IN", $cond["ocodes"]);
        }
        $joinconds = empty($joinconds) ? "" : " AND " . implode(" AND ", $joinconds);
        return M("Ordergoods")->alias('goods')->field('*')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = goods.sku_code {$joinconds}")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code {$joinconds}")
            ->where($condition)->order('goods.goods_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    //总数
    public function queryCountByCondition($cond)
    {
        $condition = array();
        $joinconds = array();
        if (isset($cond["ocode"])) {
            array_push($joinconds, "goods.order_code = " . $cond["ocode"]);
        }
        $joinconds = empty($joinconds) ? "" : " AND " . implode(" AND ", $joinconds);
        return M("Ordergoods")->alias('goods')->field('*')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = goods.sku_code {$joinconds}")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code {$joinconds}")
            ->where($condition)->fetchSql(false)->count();
    }

    //更新数量
    public function updateCountByCode($code, $skucount, $goodscount)
    {
        $condition = array("goods_code" => $code);
        return M("Ordergoods")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(),
                "sku_count" => $skucount, "goods_count" => $goodscount, "goods_status" => 1));
    }

    //更新数量
    public function updateStatusByCode($code, $status)
    {
        $condition = array("goods_code" => $code);
        return M("Ordergoods")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(), "goods_status" => $status));
    }

    //删除货品
    public function removeByCode($code, $ocode)
    {
        $condition = array("goods_code" => $code, "order_code" => $ocode);
        return M("Ordergoods")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(),
                "order_code" => "-{$ocode}"));
    }

    //批量修改成本价 spucode spubprice 订单编号列表
    public function updateBpriceByOrderCodeAndSpuCodeAndSpuBprice($spuCode, $spuBprice, $orderCodeList)
    {
        $condition = array("spu_code" => $spuCode);
        $condition['order_code'] = array("in", $orderCodeList);
        return M("Ordergoods")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(), "spu_bprice" => $spuBprice));
    }
}