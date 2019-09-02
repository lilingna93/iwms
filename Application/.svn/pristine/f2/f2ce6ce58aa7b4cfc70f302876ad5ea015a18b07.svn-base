<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2019/5/9
 * Time: 11:21
 * 检测入仓单是否存在重复数据并修复
 */
define('IS_MASTER', false);
ini_set('memory_limit', '256M');
define('APP_DIR', dirname(__FILE__) . '/../../../../');
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';

$recCodeArr = array(
    "WA100024" => array(
        "RE40519110052312"
    )
);
$deleteGbCode = array();
$deleteGsCode = array();
$deleteIgoCode = array();
$spuData = array();
$err = array();
$sql = array();
foreach ($recCodeArr as $warCode => $recCodeData) {
    foreach ($recCodeData as $recCode) {
        $repeatDataArr = getRepeatDataArr($recCode);
        foreach ($repeatDataArr as $spuCode => $repeatData) {
            $warcode = $repeatData[0]["warcode"];
            $warcodeTwo = $repeatData[1]["warcode"];
            $gbcount = $repeatData[0]["gbcount"];
            $gbcode = $repeatData[0]["gbcode"];
            $gscode = $repeatData[0]["gscode"];
            $gbcountTwo = $repeatData[1]["gbcount"];
            $gscount = $repeatData[0]["gscount"];
            $gscountTwo = $repeatData[1]["gscount"];
            if ($gbcount == $gbcountTwo && $gscount == $gscountTwo) {
                $deleteGbCode[] = $gbcode;
                $sql[] = deleteGbCode($gbcode);
                if ($gscount == 0) {
                    $deleteGsCode[] = $gscode;
                    $sql[] = deleteGsCode($gscode);
                    $igoCode = getIgoCodeByIgsTable($gscode);
                    if (count($igoCode) == 1) {
                        $igoCode = $igoCode[0];
                        $deleteIgoCode[] = $igoCode;
                        $sql[] = deleteIgoodsentByIgoCode($igoCode, $gscode);
                    } else {
                        $err['igs'][] = $gscode;
                    }
                    $init = $gbcount;
                    $count = 0;
                    $warcode = $warCode;
                    $spucode = $spuCode;
                    $sql[]=updateGoodsCount($init, $count, $warcode, $spucode);
                } else {
                    $init = $gbcount;
                    $count = $gbcount;
                    $warcode = $warCode;
                    $spucode = $spuCode;
                    $sql[]=updateGoodsCount($init, $count, $warcode, $spucode);
                }
            } else {
                $err['gsgb'][] = $spuCode;
            }

        }

    }
}
$json = array(
    "deleteGbCode" => $deleteGbCode,
    "deleteGsCode" => $deleteGsCode,
    "deleteIgoCode" => $deleteIgoCode,
    "err" => $err,
    "sql" => $sql,
);

file_put_contents("201905201353iwms.sql", implode(";\n", $sql));
exit();
$spuArr = array(
    array("SP000861", "1", "1",),
    array("SP000794", "10", "10",),
    array("SP000864", "5", "5",),
    array("SP000893", "70", "70",),
    array("SP000954", "10", "10",),
    array("SP000953", "1", "1",),
    array("SP000812", "12", "12",),
    array("SP000888", "25", "25",),
    array("SP000795", "2", "2",),
    array("SP000802", "60", "60",),
    array("SP000698", "13", "13",),
    array("SP000854", "33", "33",),
    array("SP000878", "2", "2",),
    array("SP000183", "2", "0",),
    array("SP001024", "3", "3",),
    array("SP000890", "5", "5",),
    array("SP000904", "21", "21",),
    array("SP000881", "6", "6",),
    array("SP000950", "7", "7",),
    array("SP000083", "3", "0",),
    array("SP001067", "3", "3",),
    array("SP000700", "15", "15",),
    array("SP000846", "15", "15",),
    array("SP000701", "18", "18",),
    array("SP000885", "10", "10",),
    array("SP000858", "60", "60",),
    array("SP000845", "15", "15",),
    array("SP000962", "1", "1",),
    array("SP000936", "20", "20",),
    array("SP001347", "81", "81",),
    array("SP000800", "2", "2",),
    array("SP000738", "2", "2",),
    array("SP000806", "10", "10",),
    array("SP000884", "45", "45",),
    array("SP001079", "2", "2",),
    array("SP000961", "48", "48",),
    array("SP000831", "2", "2",),
    array("SP001078", "120", "120",),
    array("SP000952", "3", "3",),
    array("SP000870", "2", "2",),
    array("SP000749", "1", "1",),
    array("SP001107", "20", "20",),
    array("SP000301", "50", "0",),
    array("SP001175", "2", "2",),
    array("SP001076", "3", "3",),
    array("SP000838", "2", "2",),
    array("SP000850", "1", "1",),
    array("SP000840", "75", "75",),
    array("SP000805", "1", "1",),
    array("SP001034", "4", "4",),
    array("SP000943", "13", "13",),
    array("SP000879", "20", "20",),
    array("SP000917", "4", "4",),
    array("SP001351", "70", "70",),
    array("SP000855", "12", "12",),
    array("SP000634", "5", "0",),
    array("SP000606", "10", "0",),
    array("SP000611", "10", "0",),
    array("SP000543", "121", "0",),
    array("SP000696", "240", "0",),

);
$uptSql=array();
foreach ($spuArr as $item) {
    $init = $item[1];
    $count = $item[2];
    $warcode = "WA100002";
    $spucode = $item[0];
    $uptSql[]=updateGoodsCount($init, $count, $warcode, $spucode);
}
file_put_contents("201905091551iwms.sql", implode(";\n", $uptSql));
exit();
function getRepeatDataArr($recCode)
{
    $gbDataArr = M("goodsbatch")
        ->alias("gb")
        ->field("gb.gb_code,gb.spu_code,gb.gb_count,gs.gs_count,gb.war_code,gs.gs_code")
        ->join("left join `wms_goodstored` gs on gs.gb_code=gb.gb_code")
        ->where(array("rec_code" => $recCode))->order("gb_code desc")->select();
    $spuData = array();
    foreach ($gbDataArr as $gbData) {
        $warCode = $gbData['war_code'];
        $gbCode = $gbData['gb_code'];
        $spuCode = $gbData['spu_code'];
        $gbCount = $gbData['gb_count'];
        $gsCount = $gbData['gs_count'];
        $gsCode = $gbData['gs_code'];
        $spuData[$spuCode][] = array(
            "gbcode" => $gbCode,
            "gscode" => $gsCode,
            "warcode" => $warCode,
            "gbcount" => $gbCount,
            "gscount" => $gsCount
        );
    }
    return $spuData;
}


function deleteGbCode($gbCode)
{
    return M("goodsbatch")->where(array("gb_code" => $gbCode))->fetchSql(true)->delete();
}

function deleteGsCode($gsCode)
{
    return M("goodstored")->where(array("gs_code" => $gsCode))->fetchSql(true)->delete();
}


function getIgoCodeByIgsTable($gsCode)
{
    $igsData = M("igoodsent")
        ->where(array("gs_code" => $gsCode))->select();
    return array_column($igsData, "igo_code");
}

function deleteIgoodsentByIgoCode($igoCode, $gsCode)
{
    return M("igoodsent")->where(array("igo_code" => $igoCode, "gs_code" => $gsCode))->fetchSql(true)->delete();
}

function updateGoodsCount($init, $count, $warcode, $spucode)
{
    return "UPDATE `wms_goods` SET `goods_init`=`goods_init`-{$init},`goods_count`=`goods_count`-{$count} WHERE `spu_code`='{$spucode}' AND `war_code`='{$warcode}'";
}