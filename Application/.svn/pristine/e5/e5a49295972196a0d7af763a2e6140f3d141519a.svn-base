<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2019/6/11
 * Time: 10:23
 */
define('IS_MASTER', false);
ini_set('memory_limit', '256M');
define('APP_DIR', dirname(__FILE__) . '/../../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';
vendor("PHPExcel");
echo venus_current_datetime() . PHP_EOL;

$skucodeArr = array(
    "WA100005" => array(
        "PD40326083614760" => array("init" => 40, "count" => 0),
        "PD40326083816286" => array("init" => 20, "count" => 0),
        "PD40326083920189" => array("init" => 1, "count" => 0),
    ),
    "WA100020" => array(
        "SP000939" => array("init" => 1, "count" => 1),
        "SP000719" => array("init" => 6.8, "count" => 6.8),
        "SP001040" => array("init" => 1, "count" => 1),
        "SP000898" => array("init" => 10, "count" => 10),
        "SP000777" => array("init" => 5.5, "count" => 5.5),
        "SP000557" => array("init" => 24, "count" => 24),
        "SP001022" => array("init" => 2, "count" => 2),
        "SP001034" => array("init" => 2, "count" => 2),
        "SP000150" => array("init" => 2, "count" => 2),
        "SP000156" => array("init" => 2, "count" => 2),
        "SP000714" => array("init" => 2, "count" => 2),
        "SP000775" => array("init" => 20.3, "count" => 20.3),
        "SP000978" => array("init" => 20, "count" => 20),
        "SP000823" => array("init" => 5, "count" => 5),
        "SP000335" => array("init" => 15, "count" => 15),
        "SP001249" => array("init" => 10, "count" => 10),

    ),
    "WA100015" => array(
        "SP001013" => array("init" => 10, "count" => 10),
        "SP000669" => array("init" => 100, "count" => 100),
    ),
    "WA100022" => array(
        "SP001356" => array("init" => 5, "count" => 0),
        "SP001120" => array("init" => 14.4, "count" => 0),
    ),
);
foreach ($skucodeArr as $warCode => $spucodes) {
    foreach ($spucodes as $spucode => $countData) {
        $count=$countData['count'];
        $init=$countData['init'];
        $goodsData=query_goods($warCode, $spucode);
        if(empty($goodsData)){
            $sql[] = insert_goods($warCode, $init, $count, $spucode);
        }else{
            if($count!=$goodsData['goods_count']){
                $sql[] = update_goods_count($warCode,$spucode ,$count);
            }
            if($init!=$goodsData['goods_init']){
                $sql[] = update_goods_init($warCode,$spucode ,$count);
            }
        }

    }
}
file_put_contents("goods20190410.sql", implode(";" . PHP_EOL, $sql));
exit();
function query_goods($warCode, $spuCode)
{
    return M("goods")->where(array("war_code" => $warCode, "spu_code" => $spuCode))->find();
}

function insert_goods($warCode, $init, $count, $spuCode)
{
    $code = venus_unique_code("G");
    $data = array(
        "goods_code" => $code,
        "goods_init" => $init,
        "goods_count" => $count,
        "spu_code" => $spuCode,
        "war_code" => $warCode,
    );
    return M("Goods")->fetchSql(true)->add($data);
}

function update_goods_init($warCode, $spuCode, $init)
{
    return M("Goods")->where(array("war_code" => $warCode, "spu_code" => $spuCode))->fetchSql(true)->setInc("goods_init", $init);
}

function update_goods_count($warCode, $spuCode, $count)
{
    return M("Goods")->where(array("war_code" => $warCode, "spu_code" => $spuCode))->fetchSql(true)->setInc("goods_count", $count);
}