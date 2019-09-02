<?php
namespace Wms\Dao;

use Common\Common\BaseDao;
use Common\Common\BaseDaoInterface;

/**
 * 仓库数据
 * Class WarehouseDao
 * @package Wms\Dao
 */
class WarehouseDao extends BaseDao implements BaseDaoInterface {

    /**
     * WarehouseDao constructor.
     */
    function __construct() {
    }

    /**
     * @return mixed
     */
    public function query() {
        $condition = array("war_code" => $this->warehousecode);
        return M("Warehouse")->where($condition)->find();
    }

    /**
     * @param int $count
     * @return mixed
     */
    public function queryClientList($count = 2000) {
        return M("Warehouse")->where(array("war_is_external"=>1))->limit(0,$count)->select();
    }

    public function queryClientByCode($code) {
        return M("Warehouse")->where(array("war_code"=>$code))->find();
    }

    public function queryClientShowList($count = 2000) {
        return M("Warehouse")->where(array("war_is_show"=>1))->limit(0,$count)->select();
    }

}