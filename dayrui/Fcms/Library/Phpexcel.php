<?php namespace Phpcmf\Library;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * http://www.tianruixinxi.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


/**
 * PHPExcel
 */

class Phpexcel
{

    public $PHPExcel;

    public function __construct()
    {
        require_once FCPATH.'ThirdParty/PHPExcel.php';
        $this->PHPExcel = new \PHPExcel();
    }

    public function get_phpexcel() {
        return $this->PHPExcel;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////


    public function down_excel($head, $field, $list) {

        $objPHPExcel = $this->PHPExcel;

        require_once FCPATH.'ThirdParty/PHPExcel/Writer/Excel5.php';//用于其他低版本xls


        // 设置文档属性
        $objPHPExcel->getProperties()->setCreator($head['author']) // 创建者
            ->setLastModifiedBy($head['author']) //被修改的
            ->setTitle($head['title'])
            ->setSubject($head['title'])
            ->setDescription($head['title'])
            ->setKeywords($head['title'])
            ->setCategory($head['title']);




        // 设置字段标题
        $index = 1;
        $end_key = '';
        $obj = $objPHPExcel->setActiveSheetIndex(0);
        foreach ($field as $key => $t) {
            $obj->setCellValue($key.$index, $t['name']);
            $end_key = $key;

            $head['head_bg_color'] && $objPHPExcel->getActiveSheet()->getStyle($key.'1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($head['head_bg_color']);
            $objPHPExcel->getActiveSheet()->getStyle($key.'1')->getFont()->setBold(true);
            $head['head_font_size'] && $objPHPExcel->getActiveSheet()->getStyle($key.'1')->getFont()->setSize($head['head_font_size']);

            // 宽度
            if ($t['width']) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($key)->setWidth($t['width']); 
            } else {
                $objPHPExcel->getActiveSheet()->getColumnDimension($key)->setAutoSize(true);  
            }

			// 第一行居中
			$objPHPExcel->getActiveSheet()->getStyle($key.'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            // 第一行居中对齐
			$objPHPExcel->getActiveSheet()->getStyle($key.'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }


        // 设置显示内容
        foreach ($list as $t) {
            $index++;
            $obj = $objPHPExcel->setActiveSheetIndex(0);
            foreach ($field as $key => $tt) {
                $value = isset($t[$tt['field']]) && $t[$tt['field']] ? $t[$tt['field']] : '未知';
                $text = 1;
                if ($tt['type'] == 'image') {
                    // 设置图片
                    $img = dr_catcher_data($value);
                    if ($img && file_put_contents(WRITEPATH.'attach/excel-'.md5($value).'.png', $img)) {
                        $objDrawing = new \PHPExcel_Worksheet_Drawing();
                        $objDrawing->setName($tt['name']);
                        $objDrawing->setDescription($value);
                        $objDrawing->setPath(WRITEPATH.'attach/excel-'.md5($value).'.png');
                        $objDrawing->setHeight($tt['image_height']);
                        $objDrawing->setCoordinates($key.$index);
                        $objDrawing->setOffsetX($tt['image_x']);
                        $objDrawing->setOffsetY($tt['image_y']);
                        $objDrawing->setRotation(0);
                        $objDrawing->getShadow()->setVisible(true);
                        $objDrawing->getShadow()->setDirection(45);
                        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                        $text = 0;
                    }
                }
                if ($text) {
                    $objPHPExcel->getActiveSheet()->getStyle($key.$index)->getAlignment()->setWrapText(true);
                    $obj->setCellValue($key.$index, $value);
                }

                // 高度
                if ($tt['height']) {
                    $objPHPExcel->getActiveSheet()->getRowDimension($index)->setRowHeight($tt['height']);
                }
				
				// 是否局左
				if ($tt['align'] == 'left') {
					$objPHPExcel->getActiveSheet()->getStyle($key.$index)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
				} else {
					$objPHPExcel->getActiveSheet()->getStyle($key.$index)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				}
				// 是否居中对齐
				if ($tt['center'] == 'true') {
					$objPHPExcel->getActiveSheet()->getStyle($key.$index)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
				}
            }  

        }


        //$objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:'.$end_key.$index)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //$objPHPExcel -> getActiveSheet() -> getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex(0)) -> setAutoSize(true); 
        // 头部字段标题属性

        if ($head['head_height']) {
            $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight($head['head_height']);
        }
            
     
        // 表格颜色
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => $head['border_color'] ? $head['border_color'] : '000'),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$end_key.$index)->applyFromArray($styleArray);


        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle($head['title']);


        // 将活动表索引设置为第一张表
        $objPHPExcel->setActiveSheetIndex(0);


        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$head['title'].'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

}
