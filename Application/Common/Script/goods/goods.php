<?php
/**
 * 导出库存数据
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
//----------------------------------------------------------------------------------------------------------------------
//$filePath = "spudata.20181226.xlsx";
//$exportFilePath = "spudata.20181226.result.xlsx";
//$excelReader = PHPExcel_IOFactory::createReader('Excel2007');
//$excelFile = $excelReader->load($filePath);

//$skucodes = ["SK0000504","SK0000658","SK0000696","SK0000530","SK0000610","SK0000057","SK0000182","SK0000317","SK0000079","SK0000670","SK0000043","SK0000187","SK0000140","SK0000058","SK0000025","SK0000010","SK0000049","SK0000090","SK0000077","SK0000136","SK0000096","SK0000048","SK0000083","SK0000308","SK0000131","SK0000555","SK0000659","SK0001003","SK0000557","SK0000552","SK0000630","SK0000291","SK0000556","SK0000618","SK0000634","SK0000686","SK0000625","SK0000534","SK0000553","SK0000626","SK0000540","SK0000602","SK0000559","SK0000290","SK0000052","SK0000158","SK0000305","SK0000668","SK0000229","SK0000181","SK0000018","SK0000665","SK0000097","SK0000026","SK0000094","SK0000132","SK0000112","SK0000098","SK0000080","SK0000249","SK0000020","SK0000224","SK0000311","SK0000231","SK0000327","SK0000282","SK0000669","SK0000313","SK0000024","SK0000335","SK0000309","SK0000529","SK0000598","SK0000657","SK0000615","SK0000639","SK0000201","SK0000165","SK0000166","SK0000606","SK0000535","SK0000691","SK0000118","SK0000193","SK0000274","SK0000253","SK0000656","SK0000307","SK0000073","SK0000078","SK0000297","SK0000171","SK0000522","SK0000230","SK0000640","SK0000677","SK0000682","SK0000533","SK0000550","SK0000617","SK0000575","SK0000121","SK0000549","SK0000649","SK0000180","SK0000125","SK0000269","SK0000648","SK0000183"];
//$skucodes=["PD31112083551449","PD31218150246407","PD31109184910629","PD31109182352770","PD31109185950104","PD31112083309877","PD31112083010805","PD31109155437394","PD31109175939296","PD31109195840917","PD31109183442296","PD31112083759525","PD31109182436677","PD31112102324711","PD31109191611694","PD31109171108811","PD31109181842245","PD31112082901881","PD31109181321642","PD31109174915215","PD31109190457433","PD31109170239836","PD31109184739977","PD31109185207427","PD31109155401860","PD31109184829797","PD31109175417201","PD31109162647170","PD31109162836274","PD31109154827581","PD31109185823690","PD31109183549607","PD31109180539669","PD31109175707340","PD31109175058965","PD31109154930996","PD31112104817397","PD31112082824296","PD31109161946272","PD31109162135126","PD31112102216722","SP000255","PD31109190343590"];
//$skucodes=["SP000732","PD31218150246407","PD31109184910629","PD31112083010805","PD31109155437394","PD31109175939296","PD31109183442296","PD311091702398
//36","PD31109184739977","PD31109181745437","PD31109175417201","PD31109162647170","PD31109162836274","PD31109154827581","PD31109185125681","PD31109180539669","PD3110
//9175707340","PD31109193545304","PD31112175055154","PD31109162135126","PD31112083309877"];
//$goodsSkuDict = array();

//$arr = ["SK0000010","SK0000018","SK0000020","SK0000020","SK0000023","SK0000024","SK0000025","SK0000025","SK0000026","SK0000026","SK0000043","SK0000043","SK0000048","SK0000049","SK0000049","SK0000052","SK0000054","SK0000057","SK0000058","SK0000064","SK0000073","SK0000073","SK0000077","SK0000077","SK0000077","SK0000078","SK0000078","SK0000079","SK0000080","SK0000083","SK0000090","SK0000094","SK0000096","SK0000096","SK0000097","SK0000097","SK0000097","SK0000098","SK0000112","SK0000112","SK0000113","SK0000118","SK0000121","SK0000125","SK0000131","SK0000131","SK0000131","SK0000132","SK0000136","SK0000140","SK0000140","SK0000140","SK0000141","SK0000150","SK0000152","SK0000158","SK0000158","SK0000158","SK0000165","SK0000166","SK0000171","SK0000171","SK0000180","SK0000181","SK0000181","SK0000182","SK0000182","SK0000183","SK0000187","SK0000187","SK0000187","SK0000193","SK0000201","SK0000224","SK0000229","SK0000230","SK0000230","SK0000231","SK0000231","SK0000231","SK0000249","SK0000253","SK0000269","SK0000274","SK0000274","SK0000282","SK0000290","SK0000290","SK0000291","SK0000293","SK0000295","SK0000297","SK0000299","SK0000305","SK0000305","SK0000305","SK0000307","SK0000307","SK0000307","SK0000307","SK0000308","SK0000309","SK0000311","SK0000311","SK0000313","SK0000315","SK0000315","SK0000315","SK0000316","SK0000316","SK0000317","SK0000317","SK0000322","SK0000327","SK0000335","SK0000504","SK0000522","SK0000522","SK0000529","SK0000529","SK0000530","SK0000530","SK0000530","SK0000530","SK0000530","SK0000530","SK0000531","SK0000532","SK0000533","SK0000534","SK0000535","SK0000540","SK0000540","SK0000540","SK0000540","SK0000540","SK0000540","SK0000540","SK0000540","SK0000545","SK0000549","SK0000550","SK0000552","SK0000552","SK0000552","SK0000552","SK0000552","SK0000553","SK0000553","SK0000555","SK0000555","SK0000555","SK0000555","SK0000555","SK0000555","SK0000555","SK0000555","SK0000555","SK0000556","SK0000556","SK0000556","SK0000556","SK0000556","SK0000556","SK0000557","SK0000557","SK0000557","SK0000559","SK0000559","SK0000559","SK0000559","SK0000563","SK0000569","SK0000575","SK0000575","SK0000598","SK0000598","SK0000602","SK0000602","SK0000606","SK0000606","SK0000606","SK0000610","SK0000610","SK0000610","SK0000610","SK0000610","SK0000610","SK0000610","SK0000610","SK0000611","SK0000615","SK0000615","SK0000617","SK0000617","SK0000618","SK0000618","SK0000618","SK0000625","SK0000625","SK0000625","SK0000625","SK0000626","SK0000630","SK0000630","SK0000634","SK0000639","SK0000639","SK0000640","SK0000640","SK0000648","SK0000648","SK0000648","SK0000649","SK0000656","SK0000657","SK0000657","SK0000657","SK0000658","SK0000658","SK0000659","SK0000659","SK0000659","SK0000659","SK0000662","SK0000662","SK0000665","SK0000665","SK0000668","SK0000668","SK0000669","SK0000669","SK0000670","SK0000670","SK0000670","SK0000677","SK0000677","SK0000677","SK0000682","SK0000686","SK0000686","SK0000686","SK0000687","SK0000688","SK0000691","SK0000696","SK0000696","SK0000696","SK0000696","SK0000696","SK0000696","SK0000696","SK0000696","SK0000696","SK0000696","SK0000696","SK0000696","SK0001003","SK0001003"];
//$arr = ["SK0000026","SK0000077","SK0000231","SK0000307","SK0000311","SK0000315","SK0000317","SK0000555","SK0000610","SK0000615","SK0000618","SK0000625","SK0000634"];
// $arr = ["SK0000010","SK0000026","SK0000026","SK0000064","SK0000077","SK0000077","SK0000077","SK0000098","SK0000231","SK0000231","SK0000307","SK0000307","SK0000307","SK0000307","SK0000308","SK0000309","SK0000311","SK0000311","SK0000315","SK0000315","SK0000315","SK0000317","SK0000317","SK0000522","SK0000522","SK0000550","SK0000556","SK0000556","SK0000598","SK0000665","SK0000665","SK0000668","SK0000668","SK0000670","SK0000670","SK0000670"];
// $arr = array_unique($arr);
//echo count($arr);
//echo implode(",",$arr);exit;
//$list = M("sku")->limit(10000)->fetchSql(false)->select();
//$skucodes = array_column($list, "spu_code");
//$skucodes = array("SK0000077","SK0000530","SK0000529","SK0000307","SK0000064","SK0000098","SK0000669","SK0000618","SK0000545","SK0000540","SK0000311");



