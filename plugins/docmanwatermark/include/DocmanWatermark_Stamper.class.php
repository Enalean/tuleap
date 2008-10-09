<?php

/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */


class DocmanWatermark_Stamper {
    
    var $group_id;
    var $lib_path;
    var $path;
    var $headers;
    var $item;
    var $user;
    var $pdf;
    
    /**
     *  constructor of DocmanWatermark_Stamper class
     *  @param String path: the PDF item path
     *  @param array('mime_type','file_name') headers: the PDF item properties
     *  @param int group_id: the project id
     *  @param int item: the PDF item id
     *  @param int user: the user id
     *  @return void
     */
    
    public function __construct($lib_path, $path,$headers,$group_id,$item, $user) {
        $this->item     = $item;
        $this->user     = $user; 
        $this->group_id = $group_id;
        $this->path     = $path;
        $this->lib_path = $lib_path;
        $this->headers  = $headers;
    }
    
    /**
     *  method to load the pdf document in zend_framework
     *  @param  void
     *  @return void
     */
    
    public function load() {
        require_once($this->lib_path.'/Pdf.php');
        $this->pdf = Zend_Pdf::load($this->path);
    }
   
    /**
     *  method to render the content of the pdf file to the browser
     *  @param  void
     *  @return void
     */
     
    public function render() {
        header('Content-Type: '. $this->headers['mime_type']);
        header('Content-Disposition: filename="'. $this->headers['file_name'] .'"');
        echo $this->pdf->render();
    }
    
    /**
     *  method to check if the current item is a pdf using the item mime type
     *  @param  void
     *  @return boolean (true when pdf will be watermarked, false when it will be not watermarked)
     */

    public function check() {
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataFactory.class.php');
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php');
        require_once('DocmanWatermark_MetadataFactory.class.php');
        require_once('DocmanWatermark_MetadataValueFactory.class.php');
        $dwmdf  = new DocmanWatermark_MetadataFactory();
        $id     = $dwmdf->getMetadataIdFromGroupId($this->group_id);
        // the watermark is disabled (the field id = 0) no stamping
        if ($id == 0) {
            return false;
        }
        $name   = $dwmdf->getMetadataNameFromId($id);
        $mdf    = new Docman_MetadataFactory($this->group_id);
        $md     = $mdf->findByName($name);
        $mdlvef = new Docman_MetadataListOfValuesElementFactory();
        $values = $mdlvef->getLoveValuesForItem($this->item,$md[0]);
        $dwmdvf = new DocmanWatermark_MetadataValueFactory(); 
        if (($this->headers['mime_type'] == 'application/pdf') && ($dwmdvf->isWatermarked($values[0]->getId()))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  method to stam each pdf page (add a banner with timestamp user real name and  confidentiality level)
     *  @param  void
     *  @return void
     */
    public function stamp() {
        require_once($this->lib_path.'/Pdf.php');
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataFactory.class.php');
        require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php');
        require_once('DocmanWatermark_MetadataFactory.class.php');
        $dwmdf  = new DocmanWatermark_MetadataFactory();
        $id     = $dwmdf->getMetadataIdFromGroupId($this->group_id);
        // when the watermark is disabled (the field id = 0) no stamping
        if ($id != 0) {
            $name   = $dwmdf->getMetadataNameFromId($id);
            $mdf    = new Docman_MetadataFactory($this->group_id);
            $md     = $mdf->findByName($name);
            $mdlvef = new Docman_MetadataListOfValuesElementFactory();
            $values = $mdlvef->getLoveValuesForItem($this->item,$md[0]);
            foreach ($this->pdf->pages as $page) {
                $width  = $page->getWidth();
                $height = $page->getHeight();
                $style = new Zend_Pdf_Style();
                $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD), 10);
                $style->setFillColor(new Zend_Pdf_Color_Rgb(1, 0, 0));
                $style->setLineColor(new Zend_Pdf_Color_Rgb(1, 0, 0));
                $page->setStyle($style);
                $page->drawRectangle(10, 10, 40, $height-10,SHAPE_DRAW_STROKE);
                $page->rotate(20, 20, 1.57);
                $page->drawText("Downloaded on :".date("d M Y H:i", time())." by (".$this->user->getRealName().") ".$values[0]->getName()." ".
                                "Downloaded on :".date("d M Y H:i", time())." by (".$this->user->getRealName().") ".$values[0]->getName(), 10, 10);
            }      
        }
    }
}
?>
