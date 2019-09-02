<?php
namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * 工单数据
 * Class TaskDao
 * @package Wms\Dao
 */
class TaskDao extends BaseDao implements BaseDaoInterface {


    //添加数据[fname,data,type,worcode]
    /**
     * @param $item
     * @return bool
     */
    public function insert($item) {
        $code = venus_unique_code("TA");
        $data = array(
            "task_code" => $code,
            "task_ctime" => venus_current_datetime(),
            "task_ftime" => '',
            "task_type" => $item["type"],
            "task_data" => $item["data"],
            "task_status" => $item["status"],
            "task_ocode" => $item["ocode"],
            "wor_code" => $item["worcode"],
            "war_code" => $this->warehousecode,
        );
        return M("Task")->add($data) ? $code : false;
    }

    //查询
    /**
     * @param $code
     * @return mixed
     */
    public function queryByCode($code) {
        return M("Task")->where(array("war_code" => $this->warehousecode, "task_code" => $code))->find();
    }

    /**
     * @param $ocode
     * @return mixed
     */
    public function queryByOCode($ocode) {
        return M("Task")->where(array("war_code" => $this->warehousecode, "task_ocode" => $ocode))->find();
    }

    //查询
    /**
     * @param $cond
     * @param int $page
     * @param int $count
     * @return mixed
     */
    public function queryListByCondition($cond, $page = 0, $count = 100) {
        $condition = $this->conditionFilter($cond);
        return M("Task")->where($condition)->limit("{$page},{$count}")
            ->order("task_ctime desc")->fetchSql(false)->select();
    }

    //总数
    /**
     * @param $cond
     * @return mixed
     */
    public function queryCountByCondition($cond) {
        $condition = $this->conditionFilter($cond);
        return M("Task")->where($condition)
            ->order("task_ctime desc")->fetchSql(false)->count();
    }

    //更新状态并完成
    /**
     * @param $code
     * @param $status
     * @return mixed
     */
    public function updateStatusAndFinishTimeByCode($code, $status) {
        $condition = array("war_code" => $this->warehousecode, "task_code" => $code);
        return M("Task")->where($condition)->fetchSql(false)
            ->save(array("task_status" => $status, "task_ftime" => venus_current_datetime(),
                "timestamp" => venus_current_datetime()));
    }

    //更新状态
    /**
     * @param $code
     * @param $status
     * @return mixed
     */
    public function updateStatusByCode($code, $status) {
        $condition = array("war_code" => $this->warehousecode, "task_code" => $code);
        return M("Task")->where($condition)->fetchSql(false)
            ->save(array("task_status" => $status,
                "timestamp" => venus_current_datetime()));
    }

    public function updateStatusAndWorCodeByCode($code, $status,$worcode) {
        $condition = array("war_code" => $this->warehousecode, "task_code" => $code);
        return M("Task")->where($condition)->fetchSql(false)
            ->save(array("task_status" => $status, "wor_code" => $worcode,
                "timestamp" => venus_current_datetime()));
    }

    private function conditionFilter($cond) {
        $condition = array("war_code" => $this->warehousecode);
        if (isset($cond["type"])) {
            $condition["task_type"] = $cond["type"];
        }
        if (isset($cond["status"])) {
            $condition["task_status"] = $cond["status"];
        }
        if (isset($cond["worcode"])) {
            $condition["wor_code"] = $cond["worcode"];
        }
        if (isset($cond["ocode"])) {
            $condition["task_ocode"] = $cond["ocode"];
        }
        return $condition;
    }


}