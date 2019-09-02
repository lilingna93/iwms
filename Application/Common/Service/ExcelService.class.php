<?php
/**
 * Created by PhpStorm.
 * User: lingn
 * Date: 2018/7/18
 * Time: 16:34
 */

namespace Common\Service;

use Think\Upload;

class ExcelService
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
     * @param $name  上传文件时，填写的name
     * @return mixed|string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * 导入Excel到MySQL数据库
     */
    public function upload($name)
    {
        if (is_uploaded_file($_FILES['file']['tmp_name'])) {
            header("Content-Type:text/html;charset=utf-8");
            $config = array(
                'exts' => array('xlsx'),
                'maxSize' => 3145728,
                'rootPath' => "./Public/",
                'savePath' => 'Uploads/',
                'subName' => array('date', 'Ymd'),
            );
            $upload = new Upload($config);
            if (!$info = $upload->upload()) {
                venus_throw_exception(0, $upload->getError());
                return $upload->getError();
            }
            $file_name = $upload->rootPath . $info[$name]['savepath'] . $info[$name]['savename'];
            $extension = strtolower(pathinfo($info[$name]['name'], PATHINFO_EXTENSION));//判断导入表格后缀格式
            if ($extension == 'xlsx') {
                return $this->getExcel($file_name);
            } else {
                venus_throw_exception(21);
            }
        } else {
            venus_throw_exception(22);
        }
    }

    /**
     * @param $filePath
     * @return array
     * 利用shell导入excel
     */
    public function uploadByShell($filePath)
    {
        vendor("PHPExcel.PHPExcel");
        vendor("PHPExcel.Reader.Excel2007");

        $data = array();
        $objReader = new \PHPExcel_Reader_Excel2007();
        //载入文件
        $PHPExcel = $objReader->load($filePath);
        //获取工作表的数目
        $sheetCount = $PHPExcel->getSheetCount();
        for ($i = 0; $i < $sheetCount; $i++) {
            //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
            $currentSheet = $PHPExcel->getSheet($i);
            //获取工作表名
            $titleSheet = $currentSheet->getTitle();
            //获取总列数
            $allColumn = $currentSheet->getHighestColumn();
            //获取总行数
            $allRow = $currentSheet->getHighestRow();
            $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI'];
            $hightNum = array_search($allColumn, $letters);
            $dataList = array();
            //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
            for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
                $itemData = array();
                //从哪列开始，A表示第一列
                for ($currentColumn = 0; $currentColumn <= $hightNum; $currentColumn++) {
                    $currentColumnCell = $letters[$currentColumn];
                    //数据坐标
                    $address = $currentColumnCell . $currentRow;
                    //读取到的数据，保存到数组$arr中
                    //[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
                    $itemData[$currentColumnCell] = $currentSheet->getCell($address)->getValue();
                    if ($itemData[$currentColumnCell] instanceof \PHPExcel_RichText)     //富文本转换字符串
                        $itemData[$currentColumnCell] = $itemData[$currentColumnCell]->__toString();
                    if ($currentColumn >= $hightNum) continue;
                }
                array_push($dataList, $itemData);
                if ($currentRow == $allRow) break;
            }
            $data[$titleSheet] = $dataList;
        }
        return $data;

    }

    /**
     * @param $filename 导入文件
     * @return mixed  excel文件数组
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public
    function getExcel($filename)
    {
        vendor("PHPExcel.PHPExcel");
        vendor("PHPExcel.Reader.Excel2007");
        $data = array();
        $objReader = new \PHPExcel_Reader_Excel2007();
        //载入文件
        $PHPExcel = $objReader->load($filename);
        //获取工作表的数目
        $sheetCount = $PHPExcel->getSheetCount();
        for ($i = 0; $i < $sheetCount; $i++) {
            //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
            $currentSheet = $PHPExcel->getSheet($i);
            //获取工作表名
            $titleSheet = $currentSheet->getTitle();
            //获取总列数
            $allColumn = $currentSheet->getHighestColumn();
            //获取总行数
            $allRow = $currentSheet->getHighestRow();
            $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI'];
            $hightNum = array_search($allColumn, $letters);
            $dataList = array();
            //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
            for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
                $itemData = array();
                //从哪列开始，A表示第一列
                for ($currentColumn = 0; $currentColumn <= $hightNum; $currentColumn++) {
                    $currentColumnCell = $letters[$currentColumn];
                    //数据坐标
                    $address = $currentColumnCell . $currentRow;
                    //读取到的数据，保存到数组$arr中
                    //[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
                    $itemData[$currentColumnCell] = $currentSheet->getCell($address)->getValue();
                    if ($itemData[$currentColumnCell] instanceof \PHPExcel_RichText)     //富文本转换字符串
                        $itemData[$currentColumnCell] = $itemData[$currentColumnCell]->__toString();
                    if ($currentColumn >= $hightNum) continue;
                }
                array_push($dataList, $itemData);
                if ($currentRow == $allRow) break;
            }
            $data[$titleSheet] = $dataList;
        }
        return $data;
    }

    public
    function exportExcel($data, $fileHeader, $typeName, $template = "")
    {
        if (!empty($template)) {
            if ($template = 1) {
                return $this->exportExcelByTemplateBySetCell($data, $typeName);
            } else {
                return $this->exportExcelByTemplate($data, $typeName);
            }

        } else {
            return $this->exportExcelByHeader($data, $fileHeader, $typeName);
        }
    }

    /**
     * 导出到excel
     * @param $data 导出的数据
     * @param $fileHeader   第一行标题
     * @param $typeName
     * @return string
     */
    public
    function exportExcelByHeader($data, $fileHeader, $typeName)
    {
        $saveDir = C("FILE_SAVE_PATH") . $typeName;
        $fileName = md5(json_encode($data)) . ".xlsx";
        $filePath = $saveDir . "/" . $fileName;
        if (file_exists($filePath)) {
            return $fileName;
        } else {
            vendor('PHPExcel');
            if (!empty($fileHeader)) {
                $lettersLength = count($fileHeader);
            }
            $objPHPExcel = new \PHPExcel();
            foreach ($data as $sheetName => $list) {
                if (empty($fileHeader)) {
                    $line = 1;
                } else {
                    $line = 2;
                }
                //创建新的工作表
                $sheet = $objPHPExcel->createSheet();
                //设置工作表名
                $sheet->setTitle($sheetName);
                foreach ($list as $index => $arr) {
                    if (!empty($fileHeader)) {
                        $lettersLength = count($fileHeader);
                        $letters = array();
                        for ($letter = 0; $letter < $lettersLength; $letter++) {
                            $letters[] = $this->getLettersCell($letter);
                        }
                        //设置第一行标题
                        foreach ($fileHeader as $k => $title) {
                            //设置单元格内容
                            $sheet->setCellValue("$letters[$k]1", $title);
                        }
                    }
                    $lettersLength = count($arr);
                    $letters = array();
                    for ($letter = 0; $letter < $lettersLength; $letter++) {
                        $letters[] = $this->getLettersCell($letter);
                    }
                    //输出数据
                    foreach ($arr as $i => $value) {
                        $sheet->setCellValue("$letters[$i]$line", $value);
                    }
                    $line++;
                }
            }

            //移除多余的工作表
            $objPHPExcel->removeSheetByIndex(0);
            //设置保存文件名字

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            if (!file_exists($saveDir)) {
                mkdir("$saveDir");
            }

            $objWriter->save($saveDir . "/" . $fileName);

            return $fileName;
        }
    }


    /**
     * @param $data导入数据
     * @param $typeName类型
     * @return string
     * 按照模板导出excel
     */
    public
    function exportExcelByTemplate($data, $typeName)
    {

        $template = C("FILE_TPLS") . $typeName . ".xlsx";
        $saveDir = C("FILE_SAVE_PATH") . $typeName;

        $fileName = md5(json_encode($data)) . ".xlsx";
        if (file_exists($saveDir . "/" . $fileName)) {
            return $fileName;
        } else {
            vendor('PHPExcel.class');
            vendor('PHPExcel.IOFactory');
            vendor('PHPExcel.Writer.Excel2007');
            vendor("PHPExcel.Reader.Excel2007");
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objPHPExcel = $objReader->load($template);    //加载excel文件,设置模板

            $templateSheet = $objPHPExcel->getSheet(0);

            foreach ($data as $sheetName => $list) {

                $excelSheet = $templateSheet->copy();

                $excelSheet->setTitle($sheetName);
                //创建新的工作表
                $sheet = $objPHPExcel->addSheet($excelSheet);
                foreach ($list as $cell => $value) {
                    $sheet->setCellValue($cell, $value);
                }
            }

            //移除多余的工作表
            $objPHPExcel->removeSheetByIndex(0);
            //设置保存文件名字

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            if (!file_exists($saveDir)) {
                mkdir("$saveDir");
            }
            $objWriter->save($saveDir . "/" . $fileName);
            return $fileName;
        }
    }

    public
    function exportExcelByTemplateBySetCell($data, $typeName)
    {

        $template = C("FILE_TPLS") . $typeName . ".xlsx";
        $saveDir = C("FILE_SAVE_PATH") . $typeName;

        $fileName = md5(json_encode($data)) . ".xlsx";
        if (file_exists($saveDir . "/" . $fileName)) {
            return $fileName;
        } else {
            vendor('PHPExcel.class');
            vendor('PHPExcel.IOFactory');
            vendor('PHPExcel.Writer.Excel2007');
            vendor("PHPExcel.Reader.Excel2007");
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objPHPExcel = $objReader->load($template);    //加载excel文件,设置模板

            $templateSheet = $objPHPExcel->getSheet(0);


            foreach ($data as $sheetName => $list) {

                $line = 1;
                $excelSheet = $templateSheet->copy();

                $excelSheet->setTitle($sheetName);
                //创建新的工作表
                $sheet = $objPHPExcel->addSheet($excelSheet);
                foreach ($list as $index => $arr) {
                    $lettersLength = count($arr);
                    $letters = array();
                    for ($letter = 0; $letter < $lettersLength; $letter++) {
                        $letters[] = $this->getLettersCell($letter);
                    }
                    //输出数据
                    foreach ($arr as $i => $value) {
                        $sheet->setCellValue("$letters[$i]$line", $value);
                    }
                    $line++;
                }
            }

            //移除多余的工作表
            $objPHPExcel->removeSheetByIndex(0);
            //设置保存文件名字

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            if (!file_exists($saveDir)) {
                mkdir("$saveDir");
            }
            $objWriter->save($saveDir . "/" . $fileName);
            return $fileName;
        }

    }

    /**
     * @param $dir放置文件夹
     * @param $fileName文件名字
     * @param $saveFile需要保存为
     * 下载excel
     */
    public function outPut($dir, $fileName, $saveFile)
    {
        $file_name = iconv("utf-8", "gb2312", $fileName);
        $file_sub_path = C("FILE_SAVE_PATH") . $dir;
        $file_path = $file_sub_path . "/" . $file_name;

        if (!file_exists($file_path)) {
            venus_throw_exception(20);
            return;
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $saveFile . '"');
        header("Content-Disposition:attachment;filename=" . $saveFile);
        echo file_get_contents($file_path);

    }

    /**
     * @param $type
     * @param $fileName
     * @return string
     * 打包zip
     */
    public function outPutZip($type, $fileName, $saveName)
    {
        $fileDataArrList = json_decode(urldecode($fileName), true);
        foreach ($fileDataArrList as $fileData) {
            foreach ($fileData as $fileKey => $fileDatum) {
                $fileDataArr[$fileKey] = $fileDatum;
            }
        }
        unset($fileDataArrList);
        $zip = new \ZipArchive();
        $dir = C("FILE_SAVE_PATH") . $type . "/";
        $zipName = md5($fileName) . ".zip";
        $fileZip = C("FILE_ZIP_SAVE_PATH") . $zipName;
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }
        if (!file_exists($fileZip)) {
            touch($fileZip);
            chmod($fileZip, 0777);
            if ($zip->open($fileZip, \ZipArchive::OVERWRITE) === TRUE) {

                foreach ($fileDataArr as $sname => $fname) {
                    if (!empty($fname)) {
                        $file = $dir . $fname;
                        if (file_exists($file)) {
                            $zip->addFile($file, $sname . ".xlsx");
                        }
                    } else {
                        continue;
                    }

                }
            }

            $zip->close(); //关闭处理的zip文件

        }
        $fsize = filesize($fileZip);
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: Binary");
        header("Content-Length: " . $fsize);
        header("Content-Disposition: attachment; filename=\"" . $saveName . "\"");
        echo file_get_contents($fileZip);
        exit;
    }

    private
    function __clone()
    {

    }

    private function getLettersCell($letter)
    {
        $y = $letter / 26;
        if ($y >= 1) {
            $y = intval($y);
            return chr($y + 64) . chr($letter - $y * 26 + 65);
        } else {
            return chr($letter + 65);
        }
    }
}