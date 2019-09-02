<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;


/**
 * 货品批次数据
 * Class GoodsbatchDao
 * @package Wms\Dao
 */
class GoodsbatchDao extends BaseDao implements BaseDaoInterface
{
    /**
     * @var string
     */
    private $dbname = "";

    /**
     * GoodsbatchDao constructor.
     */
    function __construct()
    {

    }

    /**
     * 添加数据[status,count,bprice,spucode,reccode]
     * @param $item
     * @return bool
     */
    public function insert($item)
    {
        $code = venus_unique_code("GB");
        $data = array(
            "gb_code" => $code,
            "gb_ctime" => venus_current_datetime(),
            "gb_status" => $item["status"],
            "gb_count" => $item["count"],  //spu的数量，该货品的实际数量，比如多少瓶
            "gb_bprice" => $item["bprice"], //spu的采购价格
            "spu_code" => $item["spucode"],//spu编码
            "sku_code" => $item["skucode"],//sku编码，该商品采购时的规格信息
            "sku_count" => $item["skucount"],//sku的数量，该商品采购时的采购数量，比如多少箱
            "rec_code" => $item["reccode"],//所属入仓单编码
            "sup_code" => $item["supcode"],//货品供货商编号
            "war_code" => $this->warehousecode,
        );
        return M("Goodsbatch")->add($data) ? $code : false;
    }

    /**
     * 根据货品批次号，查询一条货品批次数据
     * @param $code
     * @return mixed
     */
    public function queryByCode($code)
    {
        $condition = array("gb.war_code" => $this->warehousecode, "gb_code" => $code);
        return M("Goodsbatch")->alias('gb')->field('*,spu.spu_code,sku.sku_code,gb.sup_code')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = gb.spu_code")
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = gb.sku_code")
            ->where($condition)->order('gb.gb_code desc')->fetchSql(false)->find();
    }

    /**
     * 根据入仓单号，查询多条货品批次数据
     * @param $reccode
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryListByRecCode($reccode, $page = 0, $count = 100)
    {
        $condition = array("gb.war_code" => $this->warehousecode, "rec_code" => $reccode);
        return M("Goodsbatch")->alias('gb')->field('*,spu.spu_code,sku.sku_code,gb.sup_code')
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = gb.spu_code")
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = gb.sku_code")
            ->where($condition)->order('gb.gb_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    /**
     * 根据入仓单号，查询所办函的批次货品数据
     * @param $reccode
     * @return mixed
     */
    public function queryCountByRecCode($reccode)
    {
        $condition = array("gb.war_code" => $this->warehousecode, "rec_code" => $reccode);
        return M("Goodsbatch")->alias('gb')
            ->where($condition)->fetchSql(false)->count();
    }

    /**
     * 根据条件，查询所办函的批次货品数据
     * @param $condition
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryListByCondition($condition, $page = 0, $count = 100)
    {
        $joincond = "";
        if (isset($condition["spucode"])) {
            $joincond = ' AND gb.spu_code = "' . $condition["spucode"] . '"';
        }
        if (isset($condition["supcode"])) {
            $joincond = ' AND gb.sup_code = "' . $condition["supcode"] . '"';
        }
        $condition = $this->conditionFilter($condition);
        return M("Goodsbatch")->alias('gb')->field('*,spu.spu_code,sku.sku_code,gb.sup_code')
            ->join("JOIN wms_spu spu ON spu.spu_code = gb.spu_code {$joincond}")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code")
            ->where($condition)->order('gb.gb_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    /**
     * 根据条件，查询数据总数
     * @param $condition
     * @return mixed
     */
    public function queryCountByCondition($condition)
    {
        $joincond = "";
        if (isset($condition["spucode"])) {
            $joincond = ' AND gb.spu_code = "' . $condition["spucode"] . '"';
        }
        if (isset($condition["supcode"])) {
            $joincond = ' AND gb.sup_code = "' . $condition["supcode"] . '"';
        }
        $condition = $this->conditionFilter($condition);
        return M("Goodsbatch")->alias('gb')->field('*,spu.spu_code,sku.sku_code,gb.sup_code')
            ->join("JOIN wms_spu spu ON spu.spu_code = gb.spu_code {$joincond}")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code")
            ->where($condition)->order('gb.gb_code desc')->fetchSql(false)->count();
    }

    /**
     * 根据货品批次单号，更新数量，采购价格，sku数量
     * @param $code
     * @param $count
     * @param $bprice
     * @param $skucount
     * @return mixed
     */
    public function updateByCode($code, $count, $bprice, $skucount)
    {
        $condition = array("war_code" => $this->warehousecode, "gb_code" => $code);
        return M("Goodsbatch")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(),
                "gb_count" => $count, "gb_bprice" => $bprice, "sku_count" => $skucount));
    }

    /**
     * 根据货品批次单号，更新货品批次状态
     * @param $code
     * @param $status
     * @return mixed
     */
    public function updateStatusByCode($code, $status)
    {
        $condition = array("war_code" => $this->warehousecode, "gb_code" => $code);
        return M("Goodsbatch")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(), "gb_status" => $status));
    }

    public function updateStatusByCodeCeshi($code, $status)
    {
        $condition = array("war_code" => $this->warehousecode, "gb_code" => $code);
        return M("Goodsbatch")->where($condition)->fetchSql(true)
            ->save(array("timestamp" => venus_current_datetime(), "gb_status" => $status));
    }

    /**
     * 根据入仓单号，更新货品批次状态
     * @param $reccode
     * @param $status
     * @return mixed
     */
    public function updateStatusByRecCode($reccode, $status)
    {
        $condition = array("war_code" => $this->warehousecode, "rec_code" => $reccode);
        return M("Goodsbatch")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(), "gb_status" => $status));
    }

    /**
     * 根据SPU编号，查询货品批次数据列表
     * @param $code
     * @return mixed
     */
    public function queryListBySpuCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "spu_code" => $code);
        return M("Goodsbatch")->where($condition)->order('gb_code desc')->fetchSql(false)->select();
    }

    public function deleteByCode($code, $reccode)
    {
        $condition = array("war_code" => $this->warehousecode, "gb_code" => $code, "rec_code" => $reccode);
        return M("Goodsbatch")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(),
                "rec_code" => "-{$reccode}"));
    }

    public function queryPrevMonth($cond, $page = 0, $count = 100)
    {
        $condition = $this->conditionFilter($cond);
        if (isset($cond["spucode"])) {
            $joincond = ' AND gb.spu_code = "' . $cond["spucode"] . '"';
        }

        return M("Goodsbatch")->alias('gb')->field('gb.gb_count gb_count,gb.gb_bprice gb_bprice,gb.spu_code spu_code,
        spu.spu_name spu_name,spu.spu_unit spu_unit,spu.spu_type spu_type,gb.sup_code sup_code')
            ->join("JOIN wms_spu spu ON spu.spu_code = gb.spu_code {$joincond}")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code")
            ->order('gb_code desc')->limit("{$page},{$count}")
            ->where($condition)->fetchSql(false)->select();
    }

    public function queryListGoodsByCondition($condition, $page = 0, $count = 100)
    {
        $joincond = "";
        if (isset($condition["spucode"])) {
            $joincond = ' AND gb.spu_code = "' . $condition["spucode"] . '"';
        }

        $condition = $this->conditionFilter($condition);
        return M("Goodsbatch")->alias('gb')->field('*,spu.spu_code,sku.sku_code,gb.sup_code')
            ->join("JOIN wms_spu spu ON spu.spu_code = gb.spu_code {$joincond}")
            ->join("JOIN wms_sku sku ON sku.sku_code = gb.sku_code")
            ->join("JOIN wms_receipt rec ON rec.rec_code = gb.rec_code")
            ->where($condition)->order('gb.gb_code desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    /**
     * @param $cond
     * @return array
     */
    private function conditionFilter($cond)
    {
        $condition = array("gb.war_code" => $this->warehousecode);
        if (isset($cond["recstatus"])) {
            $condition["rec.rec_status"] = $cond["recstatus"];
        }
        if (isset($cond["reccodes"])) {
            $condition["gb.rec_code"] = array("in", $cond["reccodes"]);
        }
//        if(isset($cond["supcode"])){
//            $condition["gb.sup_code"]=$cond["supcode"];
//        }
        if (isset($cond["sctime"]) && isset($cond["ectime"])) {
            $condition["gb_ctime"] = array(array('EGT', $cond["sctime"]), array('LT', $cond["ectime"]), 'AND');
        } else if (isset($cond["sctime"])) {
            $condition["gb_ctime"] = array("EGT", $cond["sctime"]);
        } else if (isset($cond["ectime"])) {
            $condition["gb_ctime"] = array("LT", $cond["ectime"]);
        }
        return $condition;
    }

    /**
     * @param $code
     * @param $count
     * @param $bprice
     * @param $skucount
     * @return mixed
     * 20190322新增修改数量
     */
    public function updateCountAndSkuCountByCodeAndCurrentCount($code, $currentcount, $count, $skucount)
    {
        $condition = array("war_code" => $this->warehousecode, "gb_code" => $code, "gb_count" => $currentcount);
        return M("Goodsbatch")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(),
                "gb_count" => $count, "sku_count" => $skucount));
    }

    /**
     * @param $code
     * @param $bprice
     * @return mixed
     * 20190410新增修改入仓单批次货品价格
     */
    public function updateBpriceByCode($code, $bprice)
    {
        $condition = array("war_code" => $this->warehousecode, "gb_code" => $code);
        return M("Goodsbatch")->where($condition)
            ->save(array("timestamp" => venus_current_datetime(),"gb_bprice" => $bprice));
    }
}