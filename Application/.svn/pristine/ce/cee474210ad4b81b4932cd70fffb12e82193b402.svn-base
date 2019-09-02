<?php
namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * 库存数据
 * Class OrderDao
 * @package Wms\Dao
 */
class OrderDao extends BaseDao implements BaseDaoInterface {


    function __construct() {
    }

    //添加数据[]
    public function insert($item) {
        $code = venus_unique_code("O");
        $data = array(
            "order_code"    => $code,
            "order_ctime"   => $item["ctime"],
            "order_pdate"   => $item["pdate"],
            "order_status"  => $item["status"],
            "order_mark"    => $item["mark"],
            "order_bprice"  => $item["bprice"],     //订单总内部采购价
            "order_sprice"  => $item["sprice"],     //订单总内部销售价
            "order_sprofit" => $item["sprofit"]||"0",//订单总内部销售利润额
            "order_cprofit" => $item["cprofit"]||"0",//订单总客户利润额
            "order_tprice"  => $item["tprice"]||"0",//订单总客户销售价
            "war_code" => $item["warcode"],
            "user_code" => $item["ucode"],
        );
        return M("Order")->add($data) ? $code : false;
    }

    //查询
    public function queryByCode($code) {
        $condition = array("order_code" => $code);
        return M("Order")->alias("o")->field('*,o.user_code,o.war_code')
            ->join("LEFT JOIN wms_user user ON user.user_code = o.user_code")
            ->join("LEFT JOIN wms_warehouse war ON war.war_code = o.war_code")
            ->where($condition)
            ->find();
    }

    //查询
    public function queryListByCondition($condition, $page = 0, $count = 100) {
        $condition = $this->conditionFilter($condition);
        return M("Order")->alias("o")->field('*,o.user_code,o.war_code')
            ->join("LEFT JOIN wms_user user ON user.user_code = o.user_code")
            ->join("LEFT JOIN wms_warehouse war ON war.war_code = o.war_code")
            ->where($condition)->order("order_code desc")
            ->limit("{$page},{$count}")->fetchSql(false)->select();
    }

    //总数
    public function queryCountByCondition($condition) {
        $condition = $this->conditionFilter($condition);
        return M("Order")->alias("o")->where($condition)->count();
    }

    //更新状态
    public function updateStatusByCode($code, $status) {
        $condition = array("order_code" => $code);
        return M("Order")->alias("o")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(), "order_status" => $status));
    }

    //更新备注
    public function updateMarkByCode($code, $mark) {
        $condition = array("order_code" => $code);
        return M("Order")->alias("o")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(), "order_mark" => $mark));
    }

    public function updatePriceByCode($code,$bprice,$sprice,$sprofit,$cprofit,$tprice){//内部采购价格，内部销售价格，外部销售利润，内部销售利润可以动态= 内部销售价格 - 内部采购价格
        $condition = array("order_code" => $code);
        return M("Order")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(), "order_bprice" => $bprice, "order_sprice" => $sprice,
                "order_sprofit" => $sprofit,"order_cprofit"=>$cprofit,"order_tprice"=>$tprice));
    }
    //删除订单
    public function deleteByCode($code){
        $condition = array("order_code" => $code);
        return M("Order")->where($condition)->fetchSql(false)->delete();
    }

    //查询条件过滤[ucode,warcode,pdate,ctime,sctime,ectime,status]
    private function conditionFilter($cond) {
        $condition = array();
        if (isset($cond["ucode"])) {
            $condition["user_code"] = $cond["ucode"];
        }
        if (isset($cond["warcode"])) {
            $condition["o.war_code"] = $cond["warcode"];
        }
        if (isset($cond["pdate"])) {
            $condition["order_pdate"] = $cond["pdate"];
        }
        if (isset($cond["ctime"])) {
            $condition["order_ctime"] = $cond["ctime"];
        }

        if (isset($cond["sctime"]) && isset($cond["ectime"])) {
            $condition["order_ctime"] = array(
                    array('EGT',$cond["sctime"]),
                    array('ELT',$cond["ectime"]),
                    'AND'
                );
        }else if (isset($cond["sctime"])) {
            $condition["order_ctime"] = array("EGT", $cond["sctime"]);
        }else if (isset($cond["ectime"])) {
            $condition["order_ctime"] = array("ELT", $cond["ectime"]);
        }

        if (isset($cond["status"])) {
            $condition["order_status"] = $cond["status"];
        }

        return $condition;
    }
}