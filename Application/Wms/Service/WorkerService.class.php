<?php

namespace Wms\Service;

use Common\Service\PassportService;
use Wms\Dao\WorkerDao;

class WorkerService
{

    public $waCode;
    function __construct()
    {
        $workerData = PassportService::getInstance()->loginUser();
        if(empty($workerData)){
            venus_throw_exception(110);
        }
        $this->waCode = $workerData["war_code"];
    }

    //1.按项目组搜索用户（仓库账户管理）
        public function worker_search()
        {
            $warCode = $_POST['data']['warCode'];//用户组编号$_POST['data']['warCode']
            $pageCurrent = $_POST['data']['pageCurrent'];//当前页码
            $pageSize = 1000;//当前页面总条数

            if (!empty($warCode)) {
                $condition['warCode'] = $warCode;
            }
            //当前页码
            if (empty($pageCurrent)) {
                $pageCurrent = 0;
            }

            $WorkerDao = WorkerDao::getInstance($this->waCode);
            $totalCount = $WorkerDao->queryCountByCondition($condition);//获取指定条件的总条数
            $pageLimit = pageLimit($totalCount, $pageCurrent);
            $results = $WorkerDao->queryListByCondition($condition, $pageLimit['page'], $pageLimit['pSize']);

            if (empty($results)) {
                $workerList = array(
                    "pageCurrent" => 0,
                    "pageSize" => 100,
                    "totalCount" => 0
                );
                $workerList["list"] = array();
            } else {
                $workerList = array(
                    "pageCurrent" => $pageCurrent,
                    "pageSize" => $pageSize,
                    "totalCount" => $totalCount
                );
                foreach ($results as $k => $val) {
                    $workerList["list"][$k] = array(
                        "woCode"    => $val['wor_code'],//用户编号
                        "woName"    => $val['wor_name'],//用户姓名
                        "realName"  => $val['wor_rname'],//真实姓名
                        "woAuth"    => $val['wor_auth'],//用户权限值
                        "woToken"   => $val['wor_token'],//第三方账户token
                        "woPhone"   => $val['wor_phone'],//手机号
                        "waCode"    => $val['war_code'],//仓库编号
                        "waName"    => $val['war_name'],//仓库名称
                        "worIsuprectime"    => $val['wor_isuprectime'],
                    );
                }
            }
            return array(true, $workerList, "");
        }

    //1.默认用户列表（仓库账户管理）
    public function worker_list()
    {
        $pageCurrent = $_POST['data']['pageCurrent'];//当前页码
        $pageSize = 1000;//当前页面总条数

        //当前页码
        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }

        $condition = array();
        $WorkerDao = WorkerDao::getInstance($this->waCode);
        $totalCount = $WorkerDao->queryCountByCondition($condition);//获取指定条件的总条数
        $pageLimit = pageLimit($totalCount, $pageCurrent);
        $results = $WorkerDao->queryListByCondition($condition, $pageLimit['page'], $pageLimit['pSize']);

        if (empty($results)) {
            $workerList = array(
                "pageCurrent" => 0,
                "pageSize" => 100,
                "totalCount" => 0
            );
            $workerList["list"] = array();
        } else {
            $workerList = array(
                "pageCurrent" => $pageCurrent,
                "pageSize" => $pageSize,
                "totalCount" => $totalCount
            );
            foreach ($results as $k => $val) {
                $workerList["list"][$k] = array(
                    "woCode"    => $val['wor_code'],//用户编号
                    "woName"    => $val['wor_name'],//用户姓名
                    "realName"  => $val['wor_rname'],//真实姓名
                    "woAuth"    => $val['wor_auth'],//用户权限值
                    "woToken"   => $val['wor_token'],//第三方账户token
                    "woPhone"   => $val['wor_phone'],//手机号
                    "waCode"    => $val['war_code'],//仓库编号
                    "waName"    => $val['war_name'],//仓库名称
                    "worIsuprectime"    => $val['wor_isuprectime'],
                );
            }
        }
        return array(true, $workerList, "");
    }

    //2.添加账户（仓库账户管理）
    public function worker_add()
    {
        $woName = $_POST['data']['woName'];
        $realName = $_POST['data']['realName'];
        $woPwd = $_POST['data']['woPwd'];
        $woToken = "";//自动生成
        $woAuth = $_POST['data']['woAuth'];
        $woPhone = $_POST['data']['woPhone'];
        $waCode = $_POST['data']['waCode'];//仓库编号
        $worIsuprectime = $_POST['data']['worIsuprectime'];


        if (empty($woName)) {
            venus_throw_exception(1, "用户名不能为空");
            return false;
        }

        if (empty($realName)) {
            venus_throw_exception(1, "真实姓名不能为空");
            return false;
        }

        if (empty($woPwd)) {
            venus_throw_exception(1, "密码不能为空");
            return false;
        }

        $data = array(
            "name"  => $woName,
            "rname" => $realName,
            "pwd"   => $woPwd,
            "token" => $woToken,
            "phone" => $woPhone,
            "auth"  => $woAuth,
            "worIsuprectime" => $worIsuprectime,
        );
        try{
            $worAdd = WorkerDao::getInstance($waCode)->insert($data);
            if ($worAdd) {
                $success = true;
                $message = "添加账户成功";
            } else {
                $success = false;
                $message = "添加账户失败";
            }
            return array($success, "", $message);
        }catch(\Exception $e){
            $success = false;
            $message = "添加账户失败";
            return array($success, "", $message);
        }



    }

    //3.修改账户（仓库账户管理）
    public function worker_update()
    {
        $woCode = $_POST['data']['woCode'];
        $woName = $_POST['data']['woName'];
        $realName = $_POST['data']['realName'];
        $woPwd = $_POST['data']['woPwd'];
        $woAuth = $_POST['data']['woAuth'];
        $woPhone = $_POST['data']['woPhone'];
        $waCode = $_POST['data']['waCode'];//仓库编号
        $worIsuprectime = $_POST['data']['worIsuprectime'];
        $woToken = $_POST['data']['woToken'];//自动生成

        if (empty($woCode)) {
            venus_throw_exception(1, "用户编号不能为空");
            return false;
        }

        $cond = array();
        if (!empty($woName)) {
            $cond['name'] = $woName;
        }
        if (!empty($realName)) {
            $cond['rname'] = $realName;
        }
        if (!empty($woPwd)) {
            $cond['pwd'] = $woPwd;
        }
        if (!empty($woAuth)) {
            $cond['auth'] = $woAuth;
        }
//        if (!empty($woToken)) {
//            $cond['token'] = $woToken;
//        }
        if (!empty($woPhone)) {
            $cond["phone"] = $woPhone;
        }
        if (!empty($waCode)) {
            $cond["warcode"] = $waCode;
        }
        if (!empty($worIsuprectime)) {
            $cond["worIsuprectime"] = $worIsuprectime;
        }

        $worUpd = WorkerDao::getInstance()->updateItemByCode($woCode, $cond);
        if ($worUpd) {
            $success = true;
            if(!empty($worIsuprectime)){
                $success = $success && WorkerDao::getInstance()->updateWmsUserByToken($woToken,$worIsuprectime);
            }
            $message = "修改账户成功";
        } else {
            $success = false;
            $message = "修改账户失败";
        }
        return array($success, "", $message);
    }

    //4.删除账户（仓库账户管理）
    public function worker_delete()
    {

        $woCode = $_POST['data']['woCode'];
        if (empty($woCode)) {
            venus_throw_exception(1, "请选择要删除的账户");
            return false;
        }
        $worDel = WorkerDao::getInstance()->removeByCode($woCode);
        if ($worDel) {
            $success = true;
            $message = "删除账户成功";
        } else {
            $success = false;
            $message = "删除账户失败";
        }
        return array($success, "", $message);
    }
}



