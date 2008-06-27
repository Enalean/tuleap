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

require_once("Zend/Pdf.php");
require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataFactory.class.php');
require_once(dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php');

class Docman_watermark_Stamper {
    
    var $group_id;
    var $path;
    var $mime_type;
    var $headers;
    var $item;
    var $user;
    var $pdf;
    
    public function Docman_watermark_Stamper($path,$headers,$group_id,$item, $user) {
        $this->item     = $item;
        $this->user     = $user; 
        $this->group_id = $group_id;
        $this->path     = $path;
        $this->headers  = $headers;
    }
    
    public function load() {
        $this->pdf = Zend_Pdf::load($this->path);
    }
   
    public function render() {
        header('Content-Type: '. $this->headers['mime_type']);
        header('Content-Disposition: filename="'. $this->headers['file_name'] .'"');
        echo $this->pdf->render();
    }

    public function check() {
        if ($this->headers['mime_type'] == 'application/pdf') {
            return true;
        } else {
            return false;
        }
    }

    
    public function stamp() {
        $mdf    = new Docman_MetadataFactory($this->group_id);
        $md     = $mdf->findByName('Confidentiality');
        $mdlvef = new Docman_MetadataListOfValuesElementFactory();
        $values = $mdlvef->getLoveValuesForItem($this->item,$md[0]);
        foreach ($this->pdf->pages as $index => $page) {
            $width  = $page->getWidth();
            $height = $page->getHeight();
            $color  = new Zend_Pdf_Color_Html('blue');
            $stylen = new Zend_Pdf_Style();
            $stylen->setFillColor($color);
            $stylen->setLineColor($color);
            $stylen->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD), 32);
            $page->setStyle($stylen);
            $page->rotate(50,150,45);
            $page->drawRectangle(40, 40, 390, 145,SHAPE_DRAW_STROKE);
            $page->drawText($this->user->getRealName(), 40, 125);
            $page->drawText(date("Y-m-d H:I:s", time()), 40, 95);
            $page->drawText($values[0]->getName(), 40, 65);
        }
    }
}
?>
