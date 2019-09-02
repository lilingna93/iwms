<?php
namespace Wms\Service;

use Common\Service\PassportService;
use Wms\Dao\SupplierDao;

class SupplierService {

    public $waCode;
    function __construct()
    {
        $workerData = PassportService::getInstance()->loginUser();
        if(empty($workerData)){
            venus_throw_exception(110);
        }
        $this->waCode = $workerData["war_code"];
    }

    //1.SUP搜索供货商
    public function sup_search() {

        $suName = $_POST['data']['suName'];
        $pageCurrent = $_POST['data']['pageCurrent'];//当前页码
        $pageSize = 100;//当前页面总条数
        $suCode = substr($suName, 0, 2);

        if (!empty($suName) && $suCode !== "SU") {//供货商名称
            $condition['supname'] = $suName;
            $condition['%supname%'] = $suName;
        }
        if (!empty($suName) && $suCode == "SU") {
            $condition['supcode'] = $suName;
        }
        //当前页码
        if (empty($pageCurrent)) {
            $pageCurrent = 0;
        }
        $SupplierDao = SupplierDao::getInstance($this->waCode);
        $totalCount = $SupplierDao->queryCountByCondition($condition);//获取指定条件的总条数
        $pageLimit = pageLimit($totalCount, $pageCurrent);
        $results = $SupplierDao->queryListByCondition($condition, $pageLimit['page'], $pageLimit['pSize']);

        if (empty($results)) {
            $supList = array(
                "pageCurrent" => 0,
                "pageSize" => 100,
                "totalCount" => 0
            );
            $supList["list"] = array();
        } else {
            $supList = array(
                "pageCurrent" => $pageCurrent,
                "pageSize" => $pageSize,
                "totalCount" => $totalCount
            );
            foreach ($results as $k => $val) {
                $supList["list"][$k] = array(
                     "suCode" => $val['sup_code'],//供货商编号
                     "suName" => $val['sup_name'],//供货商名称
                     "suManager" => $val['sup_manager'],//联系人姓名
                     "suPhone" => $val['sup_phone'],//联系人电话
                     "suType" => $val['sup_type']//是否是自有供应商
                );
            }
        }
        return array(true, $supList, "");
    }

    //2.添加供货商
    public function sup_add() {

        $suName = $_POST['data']['suName'];
        $suManager = $_POST['data']['suManager'];
        $suPhone = $_POST['data']['suPhone'];
        // $suType = $_POST['data']['suType'];

        if (empty($suName)) {
            venus_throw_exception(1, "供货商名称不能为空");
            return false;
        }

        if (empty($suManager)) {
            venus_throw_exception(1, "联系人姓名不能为空");
            return false;
        }

        if (empty($suPhone)) {
            venus_throw_exception(1, "联系电话不能为空");
            return false;
        }

        $data = array(
            "name" => $suName,
            "manager" => $suManager,
            "phone" => $suPhone,
            "type" => 0,
            "mark" => ""
        );

        $supAdd = SupplierDao::getInstance($this->waCode)->insert($data);
        if ($supAdd) {
            $SupplierService = new AuthService();
            $SupplierService->updateBaseInfor();
            $data = SupplierDao::getInstance()->queryAllList();
            $success = true;
            $message = "添加供货商成功";
        } else {
            $success = false;
            $message = "添加供货商失败";
            $data = "";
        }
        return array($success, $data, $message);
    }

    //3.修改供货商
    public function sup_update() {

        $suCode = $_POST['data']['suCode'];//供货商编码
        $suName = $_POST['data']['suName'];//供货商名称
        $suManager = $_POST['data']['suManager'];//联系人姓名
        $suPhone = $_POST['data']['suPhone'];//联系电话
        // $suType = $_POST['data']['suType'];//是否是自有供应商

        if (empty($suCode)) {
			venus_throw_exception(1, "供货商编号不能为空");
            return false;
        }

        if (!empty($suName)) {
            $data['supname'] = $suName;
        }

        if (!empty($suManager)) {
            $data['supmanager'] = $suManager;
        }

        if (!empty($suPhone)) {
            $data['supphone'] = $suPhone;
        }

        // if (!empty($suType)) {
            // $data['suptype'] = $suType;
        // }

        $supUpd = SupplierDao::getInstance($this->waCode)->updateDataByCode($suCode, $data);
        if ($supUpd) {
            $SupplierService = new AuthService();
            $SupplierService->updateBaseInfor();
            $data = SupplierDao::getInstance()->queryAllList();
            $success = true;
            $message = "修改供货商成功";
        } else {
            $success = false;
            $message = "修改供货商失败";
            $data = "";
        }
        return array($success, $data, $message);
    }

    //4.删除供货商
    public function sup_delete() {

        $suCode = $_POST['data']['suCode'];//供货商编号
        $status = 0;

        if (empty($suCode)) {
			venus_throw_exception(1, "供货商编号不能为空");
            return false;
        }
        $supDel = SupplierDao::getInstance($this->waCode)->updateStatusByCode($suCode, $status);
        if ($supDel) {
            $SupplierService = new AuthService();
            $SupplierService->updateBaseInfor();
            $data = SupplierDao::getInstance()->queryAllList();
            $success = true;
            $message = "删除供货商成功";
        } else {
            $success = false;
            $message = "删除供货商失败";
            $data = "";
        }
        return array($success, $data, $message);
    }

}



