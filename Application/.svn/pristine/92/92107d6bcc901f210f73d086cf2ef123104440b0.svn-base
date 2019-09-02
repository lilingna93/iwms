<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2018/7/24
 * Time: 15:12
 */

namespace Common\Service;


use Wms\Dao\TaskDao;

class TaskService
{
    //保存类实例的静态成员变量
    private static $_instance;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * @param $warCode仓库编号
     * @param $ocode使用的编号，例如入仓单编号
     * @param $type工单类型
     * @param $status工单状态
     * @param $worCode工人编号
     * @return mixed
     * 创建工单
     */
    public function task_create($warCode, $ocode, $type, $status)
    {
        $taskCond["type"] = $type;
        $taskData = array("code" => $ocode);
        $taskCond["data"] = json_encode($taskData);
        $taskCond["status"] = $status;
        $taskCond["ocode"] = $ocode;
        $taskCond["worcode"] = '';
        return TaskDao::getInstance($warCode)->insert($taskCond);
    }

    /**
     * @param $warcode仓库编号
     * @param $ocode使用的编号，例如入仓单编号
     * @param $status工单状态
     * @return mixed
     * 修改工单状态
     */
    public function update_task_status_by_data($warCode, $ocode, $status)
    {
        $taskModel = TaskDao::getInstance($warCode);
        $taskCode = $taskModel->queryByOCode($ocode)['task_code'];
        return $taskModel->updateStatusAndFinishTimeByCode($taskCode, $status);
    }

    /**
     * @param $warCode仓库编号
     * @param $clause查询的条件
     * @param $page
     * @param $pSize
     * @return mixed
     *搜索工单列表
     */
    public function query_task_list_by_search($warCode, $clause, $page, $pSize)
    {
        $taskModel = TaskDao::getInstance($warCode);
        return $taskModel->queryListByCondition($clause, $page, $pSize);
    }

    /**
     * @param $warCode仓库编号
     * @param $clause查询的条件
     * @return mixed
     * 统计搜索工单
     */
    public function query_task_count_by_search($warCode, $clause)
    {
        $taskModel = TaskDao::getInstance($warCode);
        return $taskModel->queryCountByCondition($clause);
    }

    /**
     * @param $warCode
     * @param $tCode
     * @param $status
     * @return mixed
     * 取消工单
     */
    public function task_cancel_by_taskcode($warCode, $tCode, $status)
    {
        $taskModel = TaskDao::getInstance($warCode);
        return $taskModel->updateStatusAndWorCodeByCode($tCode, $status, '');
    }

    private function __clone()
    {

    }
}