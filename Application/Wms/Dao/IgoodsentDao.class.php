<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * 发货批次数据
 * Class IgoodsentDao
 * @package Wms\Dao
 */
class IgoodsentDao extends BaseDao implements BaseDaoInterface
{


    /**
     * IgoodsentDao constructor.
     */
    function __construct()
    {

    }
    //添加数据[count,bprice,spucode,gscode,invcode]

    /**
     * @param $item
     * @return bool
     */
    public function insert($item)
    {
        $code = venus_unique_code("GS");
        $data = array(
            "igs_code" => $code,
            "igs_count" => $item["count"],  //不通批次货架货品的出仓货品spu数量
            "igs_bprice" => $item["bprice"], //不通批次货架货品的出仓货品spu采购价格，即成本价
            "igs_ctime" => venus_current_datetime(),//产生时间
            "spu_code" => $item["spucode"],//spu编号
            "gs_code" => $item["gscode"], //所属货品批次货架中货品的货品编号
            "igo_code" => $item["igocode"],//所属出仓货品清单中的货品编号
            "sku_code" => $item["skucode"],//sku编号，货品出仓实际规格数据信息
            "sku_count" => $item["skucount"],//sku数量，即货品出仓按规格计算的货品数量
            "inv_code" => $item["invcode"], //所属出仓编号
            "war_code" => $this->warehousecode,//所属仓库编号
        );
        return M("Igoodsent")->add($data) ? $code : false;
    }
    //查询

    /**
     * @param $code
     * @return mixed
     */
    public function queryByCode($code)
    {
        $condition = array("igs.war_code" => $this->warehousecode, "igs_code" => $code);
        return M("Igoodsent")->alias('igs')->field('*,spu.spu_code')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = igs.sku_code")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('igs.id desc')->fetchSql(false)->find();
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
        $condition = $this->conditionFilter($cond);
        if (isset($cond["spucode"])) {
            $condition['igs.spu_code'] = $cond["spucode"];
        }
        return M("Igoodsent")->alias('igs')->field('*,spu.spu_code')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = igs.sku_code")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('igs.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }
    //总数

    /**
     * @param $cond
     * @return mixed
     */
    public function queryCountByCondition($cond)
    {
        $condition = array("igs.war_code" => $this->warehousecode);
        return M("Igoodsent")->alias('igs')->field('*,spu.spu_code')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = igs.sku_code")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('igs.id desc')->fetchSql(false)->count();
    }


    //根据出仓单号，查询多条货品批次数据

    /**
     * @param $invcode
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryListByInvCode($invcode, $page = 0, $count = 100)
    {
        $condition = array("igs.war_code" => $this->warehousecode, "inv_code" => $invcode);
        return M("Igoodsent")->alias('igs')->field('*,spu.spu_code')
            ->join("LEFT JOIN wms_sku sku ON sku.sku_code = igs.sku_code")
            ->join("LEFT JOIN wms_spu spu ON spu.spu_code = sku.spu_code")
            ->where($condition)->order('igs.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    //根据出仓单号，查询多条货品批次数量

    /**
     * @param $invcode
     * @return mixed
     */
    public function queryCountByInvCode($invcode)
    {
        $condition = array("igs.war_code" => $this->warehousecode, "inv_code" => $invcode);
        return M("Igoodsent")->alias('igs')
            ->where($condition)->fetchSql(false)->count();
    }

    public function queryPrevMonth($cond, $page = 0, $count = 100)
    {
        $condition = $this->conditionFilter($cond);
        if (isset($cond["spucode"])) {
            $joincond = ' AND igs.spu_code = "' . $cond["spucode"] . '"';
        }

        return M("Igoodsent")->alias('igs')->field('igs.igs_count igs_count,igs.igs_bprice igs_bprice,igs.spu_code spu_code,
        spu.spu_name spu_name,spu.spu_unit spu_unit,spu.spu_type spu_type')
            ->join("JOIN wms_spu spu ON spu.spu_code = igs.spu_code {$joincond}")
            ->order('igs_code desc')->limit("{$page},{$count}")
            ->where($condition)->fetchSql(false)->select();
    }

    private function conditionFilter($cond)
    {
        $condition = array("igs.war_code" => $this->warehousecode);
        if (isset($cond["sctime"]) && isset($cond["ectime"])) {
            $condition["igs_ctime"] = array(array('EGT', $cond["sctime"]), array('LT', $cond["ectime"]), 'AND');
        } else if (isset($cond["sctime"])) {
            $condition["igs_ctime"] = array("EGT", $cond["sctime"]);
        } else if (isset($cond["ectime"])) {
            $condition["igs_ctime"] = array("LT", $cond["ectime"]);
        }
        if (isset($cond['invcodes'])) {
            $condition["inv_code"] = array("in", $cond['invcodes']);
        }

        if (isset($cond['sputype'])) {
            $condition["spu_type"] = $cond['sputype'];
        }
        if (isset($cond['spusubtype'])) {
            $condition["spu_subtype"] = $cond['spusubtype'];
        }
        if (isset($cond['igocode'])) {
            $condition["igo_code"] = $cond['igocode'];
        }

        if (isset($cond['gscode'])) {
            $condition["gs_code"] = $cond['gscode'];
        }
        return $condition;
    }

    public function deleteByCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "igs_code" => $code);
        return M("Igoodsent")->where($condition)->fetchSql(false)
            ->delete();
    }

    public function deleteByIgoCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "igo_code" => $code);
        return M("Igoodsent")->where($condition)->fetchSql(false)
            ->delete();
    }

    public function updateCountAndSkuCountByCode($code, $count, $skucount)
    {
        $condition = array("igs.war_code" => $this->warehousecode, "igs_code" => $code);
        return M("Igoodsent")->alias('igs')
            ->where($condition)->fetchSql(false)->save(array("igs_count" => $count, "sku_count" => $skucount));
    }

    //20190410新增修改入仓单价格时顺便将该批次中已经出仓的货品价格
    public function updateBpriceByCode($code, $bprice)
    {
        $condition = array("igs.war_code" => $this->warehousecode, "igs_code" => $code);
        return M("Igoodsent")->alias('igs')
            ->where($condition)->fetchSql(false)->save(array("igs_bprice" => $bprice));
    }

    /**
     * @param $cond
     * @param int $page
     * @param int $count
     * @return mixed
     * 20190610新增
     */
    public function queryListByGbCode($cond, $page = 0, $count = 100)
    {
        $condition = $this->conditionFilter($cond);
        if (isset($cond["gbcode"])) {
            $condition['gb.gb_code'] = $cond["gbcode"];
        }
        if (isset($cond["invtype"])) {
            $condition['inv.inv_type'] = $cond["invtype"];
        }
        if (isset($cond["invsctime"]) && isset($cond["invectime"])) {
            $condition["inv_ctime"] = array(array('EGT', $cond["invsctime"]), array('LT', $cond["invectime"]), 'AND');
        } else if (isset($cond["invsctime"])) {
            $condition["inv_ctime"] = array("EGT", $cond["invsctime"]);
        } else if (isset($cond["invectime"])) {
            $condition["inv_ctime"] = array("LT", $cond["invectime"]);
        }
        if (isset($cond["recsctime"]) && isset($cond["recectime"])) {
            $condition["rec_ctime"] = array(array('EGT', $cond["recsctime"]), array('LT', $cond["recectime"]), 'AND');
        } else if (isset($cond["recsctime"])) {
            $condition["rec_ctime"] = array("EGT", $cond["recsctime"]);
        } else if (isset($cond["recectime"])) {
            $condition["rec_ctime"] = array("LT", $cond["recectime"]);
        }
        return M("Igoodsent")->alias('igs')->field('*,gs.sup_code')
            ->join("LEFT JOIN wms_goodstored gs on gs.gs_code=igs.gs_code")
            ->join("LEFT JOIN wms_goodsbatch gb on gb.gb_code=gs.gb_code")
            ->join("LEFT JOIN wms_receipt rec on rec.rec_code=gb.rec_code")
            ->join("JOIN wms_invoice inv ON inv.inv_code = igs.inv_code")
            ->where($condition)->order('igs.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    /**
     * @param $cond
     * @param int $page
     * @param int $count
     * @return mixed
     * 20190610新增
     */
    public function queryCountByGbCode($cond)
    {
        $condition = $this->conditionFilter($cond);
        if (isset($cond["gbcode"])) {
            $condition['gb.gb_code'] = $cond["gbcode"];
        }
        if (isset($cond["invtype"])) {
            $condition['inv.inv_type'] = $cond["invtype"];
        }
        if (isset($cond["invsctime"]) && isset($cond["invectime"])) {
            $condition["inv_ctime"] = array(array('EGT', $cond["invsctime"]), array('LT', $cond["invectime"]), 'AND');
        } else if (isset($cond["invsctime"])) {
            $condition["inv_ctime"] = array("EGT", $cond["invsctime"]);
        } else if (isset($cond["invectime"])) {
            $condition["inv_ctime"] = array("LT", $cond["invectime"]);
        }
        if (isset($cond["recsctime"]) && isset($cond["recectime"])) {
            $condition["rec_ctime"] = array(array('EGT', $cond["recsctime"]), array('LT', $cond["recectime"]), 'AND');
        } else if (isset($cond["recsctime"])) {
            $condition["rec_ctime"] = array("EGT", $cond["recsctime"]);
        } else if (isset($cond["recectime"])) {
            $condition["rec_ctime"] = array("LT", $cond["recectime"]);
        }
        return M("Igoodsent")->alias('igs')->field('*,gs.sup_code')
            ->join("LEFT JOIN wms_goodstored gs on gs.gs_code=igs.gs_code")
            ->join("LEFT JOIN wms_goodsbatch gb on gb.gb_code=gs.gb_code")
            ->join("LEFT JOIN wms_receipt rec on rec.rec_code=gb.rec_code")
            ->join("JOIN wms_invoice inv ON inv.inv_code = igs.inv_code")
            ->where($condition)->fetchSql(false)->count();
    }
}