<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2018/8/31
 * Time: 10:10
 */
define('IS_MASTER', false);
ini_set('memory_limit', '1000M');
define('APP_DIR', dirname(__FILE__) . '/../../../');
//define('APP_DIR', '/home/dev/venus-mini/');
//define('APP_DIR', '/home/iwms/app/');//正式站目录为/home/iwms/app/
define('APP_DEBUG', true);
define('APP_MODE', 'cli');
define('APP_PATH', APP_DIR . './Application/');
define('RUNTIME_PATH', APP_DIR . './Runtime_script/'); // 系统运行时目录
require APP_DIR . './ThinkPHP/ThinkPHP.php';
//在命令行中输入 chcp 65001 回车, 控制台会切换到新的代码页,新页面输出可为中文
$time = venus_script_begin("开始检测库存");

use Wms\Dao\GoodsDao;
use Wms\Dao\GoodsbatchDao;
use Wms\Dao\GoodstoredDao;
use Wms\Dao\IgoodsentDao;
use Wms\Dao\IgoodsDao;
use Wms\Dao\WarehouseDao;

$warModel = WarehouseDao::getInstance();
$warDataList = $warModel->queryClientShowList();
$warCodeList = array();
foreach ($warDataList as $warData) {
    $warCodeList[$warData['war_code']] = $warData['war_name']."|".$warData['war_code'];
}

$errorGoodsArr = array();
foreach ($warCodeList as $iwmsWarCode => $iwmsWarName) {
    $goodsModel = GoodsDao::getInstance($iwmsWarCode);
    $goodstroedModel = GoodstoredDao::getInstance($iwmsWarCode);
    $goodsbatchModel = GoodsbatchDao::getInstance($iwmsWarCode);
    $iGoodsModel = IgoodsDao::getInstance($iwmsWarCode);
    $iGoodsentModel = IgoodsentDao::getInstance($iwmsWarCode);
    $goodsDataList = queryGoodsbatchAndGoodstoredToCheckGoods($iwmsWarCode,0, 100000);
//    var_dump($goodsDataList);exit();
    if (!empty($goodsDataList)) {
//        echo json_encode($goodsDataList);exit();
        $goodsDataArr = array();

        foreach ($goodsDataList as $goodsData) {
            if (ceil(round(bcmod(($goodsData['goods_init'] * 100), ($goodsData['spu_cunit'] * 100)))) > 0 ||
                ceil(round(bcmod(($goodsData['goods_count'] * 100), ($goodsData['spu_cunit'] * 100)))) > 0 ||
                ceil(round(bcmod(($goodsData['gb_count'] * 100), ($goodsData['spu_cunit'] * 100)))) > 0 ||
                ceil(round(bcmod(($goodsData['gs_init'] * 100), ($goodsData['spu_cunit'] * 100)))) > 0 ||
                ceil(round(bcmod(($goodsData['gs_count'] * 100), ($goodsData['spu_cunit'] * 100)))) > 0) {
                if (!in_array("numUnit", $errorGoodsArr[$iwmsWarName][$goodsData['spu_code']])) {
                    $errorGoodsArr[$iwmsWarName][$goodsData['spu_code']][] = "numUnit";
                }
            }

            if ($goodsData['gb_count'] != $goodsData['gs_init']) {
                if (!in_array("goodsbatch_goodstored", $errorGoodsArr[$iwmsWarName][$goodsData['spu_code']])) {
                    $errorGoodsArr[$iwmsWarName][$goodsData['spu_code']][] = "goodsbatch_goodstored";
                }
            } else {
                if ($goodsData['sku_count'] != bcdiv($goodsData['goods_count'], $goodsData['spu_count'], 2)) {
                    if (!in_array("goods", $errorGoodsArr[$iwmsWarName][$goodsData['spu_code']])) {
                        $errorGoodsArr[$iwmsWarName][$goodsData['spu_code']][] = "goods";
                    }
                } else {
                    $goodsDataArr[$goodsData['war_code']][$goodsData['spu_code']]['goods_init'] = $goodsData['goods_init'];
                    $goodsDataArr[$goodsData['war_code']][$goodsData['spu_code']]['goods_count'] = $goodsData['goods_count'];
                    $goodsDataArr[$goodsData['war_code']][$goodsData['spu_code']]['gs_goods_init'] = floatval(bcadd($goodsDataArr[$goodsData['war_code']][$goodsData['spu_code']]['gs_goods_init'], $goodsData['gs_init'], 2));
                    $goodsDataArr[$goodsData['war_code']][$goodsData['spu_code']]['gs_goods_count'] = floatval(bcadd($goodsDataArr[$goodsData['war_code']][$goodsData['spu_code']]['gs_goods_count'], $goodsData['gs_count'], 2));
                }
            }

        }
        unset($goodsData);
        foreach ($goodsDataArr as $warCode => $goodsData) {
            foreach ($goodsData as $spuCode => $goodsDatum) {
                $goodsInit = floatval($goodsDatum['goods_init']);
                $gsGoodsInit = floatval($goodsDatum['gs_goods_init']);
                $goodsCount = floatval($goodsDatum['goods_count']);
                $gsGoodsCount = floatval($goodsDatum['gs_goods_count']);
                if ($goodsInit != $gsGoodsInit || $goodsCount != $gsGoodsCount) {
                    if (!in_array("goods_goodstored", $errorGoodsArr[$iwmsWarName][$spuCode])) {
                        $errorGoodsArr[$iwmsWarName][$spuCode][] = "goods_goodstored";
                    }
                }

            }
        }
    }

    unset($goodsDataList);
    unset($goodsDataArr);
    $igoodsDataList = queryGoodstoredAndIgoodsentToCheckGoods($iwmsWarCode,0, 100000);
//echo json_encode($igoodsDataList);
//exit();
    if (!empty($igoodsDataList)) {
        $igoodsDataArr = array();
        foreach ($igoodsDataList as $igoodsData) {
//            if (ceil(round(($goodsData['igs_count'] * 100) % ($goodsData['spu_cunit'] * 100))) > 0) {
//                if (!in_array("numUnit", $errorGoodsArr[$iwmsWarName][$igoodsData['spu_code']])) {
//                    $errorGoodsArr[$iwmsWarName][$igoodsData['spu_code']][] = "numUnit";
//                }
//            }
            if ($igoodsData['gs_init'] == $igoodsData['gs_count'] + $igoodsData['igs_count']) {
                $igoodsDataArr[$igoodsData['war_code']][$igoodsData['spu_code']]['goods_init'] = $igoodsData['goods_init'];
                $igoodsDataArr[$igoodsData['war_code']][$igoodsData['spu_code']]['goods_count'] = $igoodsData['goods_count'];
                $igoodsDataArr[$igoodsData['war_code']][$igoodsData['spu_code']]['igs_goods_count'] += $igoodsData['igs_count'];
            } else {
                if (!in_array("goodstored_igoodsent", $errorGoodsArr[$iwmsWarName][$igoodsData['spu_code']])) {
                    $errorGoodsArr[$iwmsWarName][$igoodsData['spu_code']][] = "goodstored_igoodsent";
                }
            }
        }
        unset($igoodsData);
        foreach ($igoodsDataArr as $warCode => $igoodsData) {

            foreach ($igoodsData as $spuCode => $igoodsDatum) {
                $goodsInit = floatval($igoodsDatum['goods_init']);
                $goodsCount = floatval($igoodsDatum['goods_count']);
                $igsGoodsCount = floatval($igoodsDatum['igs_goods_count']);
                $goodsInitNew = floatval(bcadd($goodsCount, $igsGoodsCount, 2));
                if ($goodsInit != $goodsInitNew) {
                    if (!in_array("goods_igoodsent", $errorGoodsArr[$iwmsWarName][$spuCode])) {
                        $errorGoodsArr[$iwmsWarName][$spuCode][] = "goods_igoodsent";
                    }
                }
                unset($goodsInit);
                unset($goodsCount);
                unset($igsGoodsCount);
            }
        }
        unset($igoodsDataList);
    }
}
echo json_encode($errorGoodsArr);
exit();
if (!empty($errorGoodsArr)) {
    $title = "[NOTICE]IWMS库存错误货品提醒(测试站)";
//    $title = "[NOTICE]IWMS库存错误货品提醒(正式站)";
    $content = "<h3>以下货品数据有问题</h3>";
    foreach ($errorGoodsArr as $iwmsName => $errorGoods) {
        $spuArr = array_keys($errorGoods);
        $content = $iwmsName . ":<br><div style='border: 1px solid #000000'>以下货品数据有问题：<br>";
        if (!empty($spuArr)) {
            foreach ($spuArr as $spuCode) {
                $types = join(",<br>", $errorGoods[$spuCode]);
                $content .= $spuCode . "发生以下异常:" . "{$types}" . "<br>";
            }
        }
        $content .= "</div>";
    }
    if (sendMailer($title, $content)) {
        echo "(发送成功)";
    } else {
        echo "(发送失败)";
    }
}

venus_script_finish($time);
exit();

function queryGoodsbatchAndGoodstoredToCheckGoods($iwmsWarCode, $page = 0, $count = 100000)
{
    return M("Goods")->alias('goods')->field('goods.goods_code,goods.goods_init goods_init,goods.goods_count goods_count,
        gb.gb_code gb_code,gb.gb_count gb_count,gs.gs_code gs_code,gs.gs_init gs_init,gs.gs_count gs_count,
        goods.war_code war_code,goods.spu_code spu_code')
        ->join("LEFT JOIN wms_goodsbatch gb ON gb.spu_code = goods.spu_code")
        ->join("LEFT JOIN wms_goodstored gs ON gs.gb_code = gb.gb_code")
        ->where(array("gb.war_code" => $iwmsWarCode,
            "gs.war_code" => $iwmsWarCode, "goods.war_code" => $iwmsWarCode,
            "gb.gb_count" => array("gt", 0)))
        ->order('goods.id desc')->limit("{$page},{$count}")
        ->fetchSql(false)->select();
}

function queryGoodstoredAndIgoodsentToCheckGoods($iwmsWarCode, $page = 0, $count = 100000)
{
    return M("Goods")->alias('goods')->field('goods.goods_init goods_init,goods.goods_count goods_count,
        goods.war_code war_code,goods.spu_code spu_code,sum(igs.igs_count) igs_count,gs.gs_init,
        gs.gs_count')
        ->join("LEFT JOIN wms_goodstored gs ON gs.spu_code = goods.spu_code")
        ->join("LEFT JOIN wms_igoodsent igs ON igs.gs_code = gs.gs_code")
        ->where(array("gs.war_code" => $iwmsWarCode, "igs.war_code" => $iwmsWarCode,
            "goods.war_code" => $iwmsWarCode, "igs.igs_count" => array("gt", 0)))
        ->order('goods.id desc')->limit("{$page},{$count}")
        ->Group("gs.gs_code")->fetchSql(false)->select();
}