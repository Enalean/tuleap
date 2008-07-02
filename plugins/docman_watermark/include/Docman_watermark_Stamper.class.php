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

define('FPDF_FONTPATH','/prj/codex/maalejm/tun00396/tun00396_xerox_devtrunk/usr/share/zendframework/fpdf/font/');
require_once('fpdf/fpdf.php');
require_once('fpdf/fpdi.php');
require_once('fpdf/rotation.php');

class Docman_watermark_Stamper extends PDF_Rotate {
    
    var $group_id;
    var $path;
    
    var $mime_type;
    var $headers;
    var $item;
    var $user;
    
    public function Docman_watermark_Stamper($path,$headers,$group_id,$item, $user) {
        $this->item     = $item;
        $this->user     = $user; 
        $this->group_id = $group_id;
        $this->path     = $path;
        $this->headers  = $headers;
        parent::PDF_Rotate();
    }
    
   function RotatedText($x,$y,$txt,$angle) {
        $this->Rotate($angle,$x,$y);
        $this->Text($x,$y,$txt);
        $this->Rotate(0);
    }
    
    public function render() {
        header('Content-Type: '. $this->headers['mime_type']);
        header('Content-Disposition: filename="'. $this->headers['file_name'] .'"');
        $this->output();
    }

    public function check() {
        if ($this->headers['mime_type'] == 'application/pdf') {
            return true;
        } else {
            return false;
        }
    }

    public function createTemplate() {
        
        return 'Template.pdf';
    }


    public function stamp() {
        $pagecount = $this->setSourceFile($this->path);
        for($i=1;$i<=$pagecount;$i++) {
            $tplidx = $this->importPage($i, '/MediaBox');
            $this->addPage();
            $size = $this->getTemplateSize($tplidx);
            $this->useTemplate($tplidx, 0, 0, $size['h'], $size['w']);
        }
        $this->close();
    }
    
    function Header() {
        $this->SetFont('Arial','B',50);
        $this->SetTextColor(255,192,203);
        $this->RotatedText(30,190,'W a t e r m a r k   d e m o',45);
    }
}
?>
