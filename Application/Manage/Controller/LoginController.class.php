<?php
namespace Manage\Controller;
use Common\Service\PassportService;
use Think\Controller;


class loginController extends Controller {

    public function index() {
        $workerData = PassportService::loginUser();
        if(!empty($workerData)){
            $this->redirect("https://".C("WMS_HOST")."/manage/index");
            return;
        }
        $this->assign('config', array(
            "appname"=>(IS_MASTER?"供应链仓库系统":"项目组仓库系统"),
            "host"=>C("WMS_HOST")
        ));
        $this->display();
    }



}
