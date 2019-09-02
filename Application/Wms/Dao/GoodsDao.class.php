<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * 库存数据
 * Class GoodsDao
 * @package Wms\Dao
 */
class GoodsDao extends BaseDao implements BaseDaoInterface
{


    /**
     * GoodsDao constructor.
     */
    function __construct()
    {

    }

    /**
     * 添加数据[init,count,spucode]
     * @param $item
     * @return bool
     */
    public function insert($item)
    {
        $code = venus_unique_code("G");
        $data = array(
            "goods_code" => $code,
            "goods_init" => $item["init"],
            "goods_count" => $item["count"],
            "spu_code" => $item["spucode"],
            "war_code" => $this->warehousecode,
        );
        return M("Goods")->add($data) ? $code : false;
    }

    /**
     * 查询
     * @param $code
     * @return mixed
     */
    public function queryByCode($code)
    {
        $condition = array("goods.war_code" => $this->warehousecode, "goods_code" => $code);
        return M("Goods")->alias('goods')->field('*,spu.spu_code,sku.sku_code')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code")
            ->join("LEFT JOIN wms_sku sku ON sku.spu_code = goods.spu_code")
            ->where($condition)->fetchSql(false)->find();
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
        $condition = array("goods.war_code" => $this->warehousecode);
		
		if (isset($cond["gcount"]) && $cond["gcount"] == 3) {
            $condition["goods_count"] = array("EQ", 0);
        } else {
            $condition["goods_count"] = array("GT", 0);
        }
		
        $joinconds = array();
        if (isset($cond["%name%"])) {
            $spuname = preg_replace("/'/i", "", $cond["%name%"]);
            $joinconds[] = ("spu_name like '%{$spuname}%'");
        }
        if (isset($cond["type"])) {
            $joinconds[] = "spu_type = '" . $cond["type"] . "'";
        }
        if (isset($cond["subtype"])) {
            $joinconds[] = "spu_subtype = '" . $cond["subtype"] . "'";
        }
        $joinconds = empty($joinconds) ? "" : " AND " . implode(" AND ", $joinconds);


        return M("Goods")->alias('goods')->field('*,spu.spu_code,sku.sku_code')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code {$joinconds}")
            ->join("JOIN wms_sku sku ON sku.spu_code = spu.spu_code")
            ->where($condition)->order('goods.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    //总数

    /**
     * @param $cond
     * @return mixed
     */
    public function queryCountByCondition($cond)
    {
        $condition = array("goods.war_code" => $this->warehousecode);
		
		if (isset($cond["gcount"]) && $cond["gcount"] == 3) {
            $condition["goods_count"] = array("EQ", 0);
        } else {
            $condition["goods_count"] = array("GT", 0);
        }
		
        $joinconds = array();
        if (isset($cond["%name%"])) {
            $spuname = preg_replace("/'/i", "", $cond["%name%"]);
            $joinconds[] = ("spu_name like '%{$spuname}%'");
        }
        if (isset($cond["type"])) {
            $joinconds[] = "spu_type = '" . $cond["type"] . "'";
        }
        if (isset($cond["subtype"])) {
            $joinconds[] = "spu_subtype = '" . $cond["subtype"] . "'";
        }
        $joinconds = empty($joinconds) ? "" : " AND " . implode(" AND ", $joinconds);
        return M("Goods")->alias('goods')->field('*,spu.spu_code,sku.sku_code')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code {$joinconds}")
            ->join("JOIN wms_sku sku ON sku.spu_code = spu.spu_code")
            ->where($condition)->fetchSql(false)->count();
    }
    //查询

    /**
     * @param $cond
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryAllListByCondition($cond, $page = 0, $count = 100)
    {
        $condition = array("goods.war_code" => $this->warehousecode);
        $joinconds = array();
        if (isset($cond["%name%"])) {
            $spuname = preg_replace("/'/i", "", $cond["%name%"]);
            $joinconds[] = ("spu_name like '%{$spuname}%'");
        }
        if (isset($cond["type"])) {
            $joinconds[] = "spu_type = '" . $cond["type"] . "'";
        }
        if (isset($cond["subtype"])) {
            $joinconds[] = "spu_subtype = '" . $cond["subtype"] . "'";
        }
        $joinconds = empty($joinconds) ? "" : " AND " . implode(" AND ", $joinconds);
        return M("Goods")->alias('goods')->field('*,spu.spu_code,sku.sku_code')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code {$joinconds}")
            ->join("JOIN wms_sku sku ON sku.spu_code = spu.spu_code")
            ->where($condition)->order('goods.id desc')->limit("{$page},{$count}")
            ->fetchSql(false)->select();
    }


    public function queryAllCountByCondition($cond)
    {
        $condition = array("goods.war_code" => $this->warehousecode);
        $joinconds = array();
        if (isset($cond["%name%"])) {
            $spuname = preg_replace("/'/i", "", $cond["%name%"]);
            $joinconds[] = ("spu_name like '%{$spuname}%'");
        }
        if (isset($cond["type"])) {
            $joinconds[] = "spu_type = '" . $cond["type"] . "'";
        }
        if (isset($cond["subtype"])) {
            $joinconds[] = "spu_subtype = '" . $cond["subtype"] . "'";
        }
        $joinconds = empty($joinconds) ? "" : " AND " . implode(" AND ", $joinconds);
        return M("Goods")->alias('goods')->field('*,spu.spu_code,sku.sku_code')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code {$joinconds}")
            ->join("JOIN wms_sku sku ON sku.spu_code = spu.spu_code")
            ->where($condition)->fetchSql(false)->count();
    }

    /**
     * @param $code
     * @param $curcount
     * @param $newcount
     * @return mixed
     */
    public function updateCountByCode($code, $curcount, $newcount)
    {
        $condition = array("war_code" => $this->warehousecode, "goods_code" => $code, "goods_count" => $curcount);
        return M("Goods")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(),
                "goods_count" => $newcount));
    }

    //查询

    /**
     * @param $code
     * @return mixed
     */
    public function queryBySpuCode($code)
    {
        $condition = array("goods.war_code" => $this->warehousecode, "goods.spu_code" => $code);
        return M("Goods")->alias('goods')->field('*')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code")
            ->where($condition)->fetchSql(false)->find();
    }

    /**
     * @param $code
     * @return mixed
     */
    public function queryBySkuCode($code)
    {
        $condition = array("goods.war_code" => $this->warehousecode);
//        $condition = array();
        return M("Goods")->alias('goods')->field('*')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = goods.spu_code")
            ->join("JOIN wms_sku sku ON sku.spu_code = spu.spu_code AND sku.sku_code = '{$code}'")
            ->where($condition)->fetchSql(false)->find();
    }

    //根据goodscode修改库存

    /**
     * @param $code
     * @param $newinit
     * @param $newcount
     * @return mixed
     */
    public function updateCountAndInitByCode($code, $newinit, $newcount)
    {
        $condition = array("war_code" => $this->warehousecode, "goods_code" => $code);
        return M("Goods")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(),
                "goods_init" => $newinit, "goods_count" => $newcount));
    }

    public function queryGoodsbatchAndGoodstoredToCheckGoods($page = 0, $count = 100)
    {
        $condition = array("goods.war_code" => $this->warehousecode, "gb.war_code" => $this->warehousecode, "gs.war_code" => $this->warehousecode);
        return M("Goods")->alias('goods')->field('goods.goods_init goods_init,goods.goods_count goods_count,
        gb.gb_code gb_code,gb.gb_count gb_count,gs.gs_code gs_code,gs.gs_init gs_init,gs.gs_count gs_count,
        goods.war_code war_code,goods.spu_code spu_code')
            ->join("LEFT JOIN wms_goodsbatch gb ON gb.spu_code = goods.spu_code")
            ->join("LEFT JOIN wms_goodstored gs ON gs.gb_code = gb.gb_code")
            ->order('goods.id desc')->limit("{$page},{$count}")
            ->where($condition)->fetchSql(false)->select();
    }

    public function queryGoodstoredAndIgoodsentToCheckGoods($page = 0, $count = 100)
    {
        $condition = array("goods.war_code" => $this->warehousecode, "igs.war_code" => $this->warehousecode, "gs.war_code" => $this->warehousecode);
        return M("Goods")->alias('goods')->field('goods.goods_init goods_init,goods.goods_count goods_count,
        goods.war_code war_code,goods.spu_code spu_code,sum(igs.igs_count) igs_count,gs.gs_init,gs.gs_count')
            ->join("LEFT JOIN wms_goodstored gs ON gs.spu_code = goods.spu_code")
            ->join("LEFT JOIN wms_igoodsent igs ON igs.gs_code = gs.gs_code")
            ->order('goods.id desc')->limit("{$page},{$count}")
            ->where($condition)
            ->Group("gs.gs_code")->fetchSql(false)->select();
    }

    public function updateInitByCode($code, $curcount, $newinit)
    {
        $condition = array("war_code" => $this->warehousecode, "goods_code" => $code, "goods_init" => $curcount);
        return M("Goods")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(),
                "goods_init" => $newinit));
    }
}