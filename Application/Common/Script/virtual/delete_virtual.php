<?php
/**
 * Created by PhpStorm.
 * User: lilingna
 * Date: 2019/4/16
 * Time: 11:14
 * 删除多余快进快出数据
 */
define('IS_MASTER', false);
ini_set('memory_limit', '256M');
define('APP_DIR', dirname(__FILE__) . '/../../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';

$recCode = "RE40416090012500";
$gbData = getGbData($recCode);
$sql = array();
foreach ($gbData as $gbDatum) {
    $spuCode = $gbDatum['spu_code'];
    $count = $gbDatum['gb_count'];
    $warCode = $gbDatum['war_code'];

    $sql[] = updateGoodsSql($warCode, $spuCode, $count);
    $gbCode = $gbDatum['gb_code'];
    $sql[] =deleteGsSql($gbCode);
}

$sql[] =deleteRecSql($recCode);
$sql[] =deleteGbSql($recCode);
$invCode = queryInvCode($recCode);
$sql[] =deleteIgoSql($invCode);
$sql[] =deleteIgsSql($invCode);
$sql[] =deleteInvSql($invCode);
file_put_contents("20190416iwms.sql", implode(";\n", $sql));
exit();

function getGbData($recCode)
{
    return M("goodsbatch")->where(array("rec_code" => $recCode))->limit(1000)->select();
}

function updateGoodsSql($warCode, $spuCode, $count)
{
    return "UPDATE `wms_goods` SET `goods_init`=`goods_init`-$count where `spu_code`='{$spuCode}' and `war_code`='{$warCode}'";
}

function deleteGsSql($gbCode)
{
    return M("goodstored")->where(array("gb_code" => $gbCode))->fetchSql(true)->delete();
}

function deleteRecSql($recCode)
{
    return M("receipt")->where(array("rec_code" => $recCode))->fetchSql(true)->delete();
}

function deleteGbSql($recCode)
{
    return M("goodsbatch")->where(array("rec_code" => $recCode))->fetchSql(true)->delete();
}

function queryInvCode($recCode)
{
    return M("invoice")->where(array("inv_mark" => $recCode))->getField("inv_code");
}

function deleteIgoSql($invCode)
{
    return M("igoods")->where(array("inv_code" => $invCode))->fetchSql(true)->delete();
}

function deleteIgsSql($invCode)
{
    return M("igoodsent")->where(array("inv_code" => $invCode))->fetchSql(true)->delete();
}

function deleteInvSql($invCode)
{
    return M("invoice")->where(array("inv_code" => $invCode))->fetchSql(true)->delete();
}