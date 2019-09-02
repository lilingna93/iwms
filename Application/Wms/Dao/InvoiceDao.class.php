<?php

namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * 出仓单
 * Class InvoiceDao
 * @package Wms\Dao
 */
class InvoiceDao extends BaseDao implements BaseDaoInterface
{

    //添加数据[status,ecode,receiver,address,postal,worcode]
    /**
     * @param $item
     * @return bool
     */
    public function insert($item)
    {
        $code = venus_unique_code("IN");
        $ctime = $item["ctime"];
        $data = array(
            "inv_code" => $code,
            "inv_ctime" => empty($ctime) ? venus_current_datetime() : $ctime,
            "room" => $item["room"],
            "return_mark"=> $item["returnmark"],
            "inv_status" => $item["status"],
            "inv_ecode" => $item["ecode"],
            "inv_receiver" => $item["receiver"],
            "inv_phone" => $item["phone"],
            "inv_address" => $item["address"],
            "inv_postal" => $item["postal"],
            "inv_type" => $item["type"],
            "inv_mark" => $item["mark"],
            "trace_code" => $item["tracecode"],
            "wor_code" => $item["worcode"],
            "war_code" => $this->warehousecode,
            "timestamp" => venus_current_datetime(),
        );
        return M("Invoice")->add($data) ? $code : false;
    }

    //查询

    /**
     * @param $code
     * @return mixed
     */
    public function queryByCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_code" => $code);
        return M("Invoice")->where($condition)->find();
    }

    /**
     * @param $ecode
     * @return mixed
     */
    public function queryByEcode($ecode)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_ecode" => $ecode);
        return M("Invoice")->where($condition)->find();
    }
    //查询

    /**
     * @param $condition
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryListByCondition($condition, $page = 0, $count = 100)
    {
        $condition = $this->conditionFilter($condition);
        return M("Invoice")->alias('inv')->field('inv.*,wor_rname')
            ->join("LEFT JOIN wms_worker wor ON wor.wor_code = inv.wor_code")
            ->where($condition)->order('inv.id desc')->limit("{$page},{$count}")->fetchSql(false)->select();
        //return M("Invoice")->alias('inv')->where($condition)->order("id desc")->limit("{$page},{$count}")->fetchSql(false)->select();
    }
    //总数

    /**
     * @param $condition
     * @return mixed
     */
    public function queryCountByCondition($condition)
    {
        $condition = $this->conditionFilter($condition);
        return M("Invoice")->alias('inv')->where($condition)->order("id desc")->fetchSql(false)->count();
    }
    //更新状态

    /**
     * @param $code
     * @param $status
     * @return mixed
     */
    public function updateStatusByCode($code, $status)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_code" => $code);
        return M("Invoice")->where($condition)->fetchSql(false)
            ->save(array("timestamp" => venus_current_datetime(), "inv_status" => $status));
    }

    //更新状态和创建时间
    public function updateStatusAndCtimeByCode($code, $status)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_code" => $code);
        return M("Invoice")->where($condition)->fetchSql(false)
            ->save(array("inv_ctime" => venus_current_datetime(), "timestamp" => venus_current_datetime(), "inv_status" => $status));
    }

    //删除订单
    public function deleteByCode($code)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_code" => $code);
        return M("Invoice")->where($condition)->fetchSql(false)->delete();
    }
    //查询条件过滤[worcode,ctime,sctime,ectime,status]

    /**
     * @param $cond
     * @return array
     */
    private function conditionFilter($cond)
    {
        $condition = array("inv.war_code" => $this->warehousecode);
        if (isset($cond["worcode"])) {
            $condition["wor_code"] = $cond["worcode"];
        }
        if (isset($cond["ctime"])) {
            $condition["inv_ctime"] = $cond["ctime"];
        }
        if (isset($cond["sctime"]) && isset($cond["ectime"])) {
            $condition["inv_ctime"] = array(array('EGT', $cond["sctime"]), array('LT', $cond["ectime"]), 'AND');
        } else if (isset($cond["sctime"])) {
            $condition["inv_ctime"] = array("EGT", $cond["sctime"]);
        } else if (isset($cond["ectime"])) {
            $condition["inv_ctime"] = array("LT", $cond["ectime"]);
        }
        if (isset($cond["type"])) {
            $condition["inv_type"] = $cond["type"];
        }
        if (isset($cond["status"])) {
            $condition["inv_status"] = $cond["status"];
        }
        if (isset($cond["code"])) {
            $condition["inv_code"] = $cond["code"];
        }
        return $condition;
    }

    /**
     * @param $ecode
     * @return mixed
     */
    public function queryByMark($mark)
    {
        $condition = array("war_code" => $this->warehousecode, "inv_mark" => $mark);
        return M("Invoice")->where($condition)->find();
    }
}