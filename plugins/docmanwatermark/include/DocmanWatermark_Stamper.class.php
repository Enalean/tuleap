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

require_once 'DocmanWatermark_MetadataFactory.class.php';
require_once 'DocmanWatermark_MetadataValueFactory.class.php';
require_once dirname(__FILE__).'/../../docman/include/Docman_MetadataFactory.class.php';
require_once dirname(__FILE__).'/../../docman/include/Docman_MetadataListOfValuesElementFactory.class.php';

class DocmanWatermark_Stamper {
    /**
     * @var String
     */
    protected $lib_path;
    
    /**
     * @var Docman_Item
     */
    protected $item;
    
    /**
     * @var PFUser
     */
    protected $user;
    
    /**
     * @var Docman_Version
     */
    protected $version;
    
    /**
     * @var Zend_Pdf_Page
     */
    protected $pdf;

    const WATERMARK_EVERYPAGE = 1; // every page of the pdf document will be watermarked
    const WATERMARK_EVERY_TWO_PAGES = 10; // Watermark every two pages.
    const WATERMARK_THIRTY_PERCENT_OF_PAGES = 30; // Watermark thirty percent of pages in pdf document.
    const WATERMARK_TEN_PERCENT_OF_PAGES = 60; // Watermark only ten percent of pages in pdf document.

    /**
     *  Constructor of DocmanWatermark_Stamper class
     *  
     *  @param String         $lib_path Zend library path
     *  @param Docman_Item    $item
     *  @param Docman_Version $version
     *  @param PFUser           $user
     *
     *  @return void
     */
    public function __construct($lib_path, $item, $version, $user) {
        $this->item     = $item;
        $this->user     = $user;
        $this->version  = $version; 
        $this->lib_path = $lib_path;
        
        spl_autoload_register(array($this, 'autoload'));
    }
    
    /**
     * Method called when a class is not defined. 
     * 
     * Used to load Zend classes on the fly
     * 
     * @param String $className
     * 
     * @return void
     */
    public function autoload($className) {
        ini_set('include_path', $this->lib_path.':'.ini_get('include_path'));
        if (strpos($className, 'Zend') === 0) {
            $path = str_replace('_', '/', $className);
            include_once $path.'.php';
        }
    }
    
    /**
     *  method to load the pdf document in zend_framework
     *  @param  void
     *  @return void
     */
    public function load() {
        $this->pdf = Zend_Pdf::load($this->version->getPath());
    }
   
    /**
     *  method to render the content of the pdf file to the browser
     *  @param  void
     *  @return void
     */
    public function render() {
        header('Content-Type: '.$this->version->getFiletype());
        header('Content-Disposition: filename="'.$this->version->getFilename() .'"');
        echo $this->pdf->render();
    }

    /**
     *  method to check if the current item is a pdf using the item mime type
     *  @param  void
     *  @return boolean (true when pdf will be watermarked, false when it will be not watermarked)
     */
    public function check() {
        if ($this->version->getFiletype() == 'application/pdf') {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Method to give adequate watermarking level of a pdf doc based on number of its pages.
    *
    * @param void
    * @return int one of class constants.
    */
    public function getWatermarkingLevelFromPdfSize() {
        $numberOfPages = count($this->pdf->pages);
        if($numberOfPages <= 320 && $numberOfPages > 0) {
            return self::WATERMARK_EVERYPAGE;
        }
        elseif($numberOfPages <= 580 && $numberOfPages > 320) {
            return self::WATERMARK_EVERY_TWO_PAGES;
        }
        elseif($numberOfPages <= 1500 && $numberOfPages > 580) {
            return self::WATERMARK_THIRTY_PERCENT_OF_PAGES;
        }
        elseif($numberOfPages > 1500) {
            return self::WATERMARK_TEN_PERCENT_OF_PAGES;
        }
    }

    /**
     *  method to stamp each pdf page (add a banner with timestamp user real name and  confidentiality level)
     *  @param  void
     *  @return void
     */
    public function stamp($values) {
        // Prepare stamp
        if ($values != null) {
            $first = true;
            foreach($values as $value) {
                if ($first) {
                    $sep = '';
                    $first = false;
                } else {
                    $sep = ', ';
                }
                $valueTxt = $sep.$value->getName();
            }
        } else {
            $valueTxt = '';
        }
        $text = "Downloaded on ".date("d M Y H:i", $_SERVER['REQUEST_TIME'])." by ".$this->user->getRealName()." ".$valueTxt;
        $stamp = $text." // ".$text;
        
        // Text and box style
        $style = new Zend_Pdf_Style();
        $style->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 10);
        $style->setFillColor(new Zend_Pdf_Color_Rgb(1, 0, 0));
        $style->setLineColor(new Zend_Pdf_Color_Rgb(1, 0, 0));   
        
        //get pdf watermarking level based on number of pages in pdf document.
        $watermarkingLevel = $this->getWatermarkingLevelFromPdfSize();

        // Stamp with adequate watermarking level
        switch ($watermarkingLevel) {
            case self::WATERMARK_EVERYPAGE:
                // Apply it on all pages
                foreach ($this->pdf->pages as $page) {
                    $this->stampOnePage($page, $style, $stamp);
                }
                break;
            case self::WATERMARK_EVERY_TWO_PAGES:
                $count = 0;
                foreach($this->pdf->pages as $page) {
                    if (($count % 2) == 0) {
                        $this->stampOnePage($page, $style, $stamp);
                    }
                    $count++;
                }
                break;
            case self::WATERMARK_THIRTY_PERCENT_OF_PAGES:
                $pagesToWatermark = $this->getPagesToWatermark(0.3, count($this->pdf->pages));
                foreach($pagesToWatermark as $pageNo) {
                    $this->stampOnePage($this->pdf->pages[$pageNo], $style, $stamp);
                }
                break;
            case self::WATERMARK_TEN_PERCENT_OF_PAGES:
            default:
                $pagesToWatermark = $this->getPagesToWatermark(0.1, count($this->pdf->pages));
                foreach($pagesToWatermark as $pageNo) {
                    $this->stampOnePage($this->pdf->pages[$pageNo], $style, $stamp);
                }
                break;
        }
    }
    
    /**
     * Apply stamp on one page with given style.
     * 
     * @param Zend_Pdf_Page  $page
     * @param Zend_Pdf_Style $style
     * @param String         $stamp
     * 
     * @return void
     */
    protected function stampOnePage($page, $style, $stamp) {
        $height = $page->getHeight();
        $page->setStyle($style);
        $page->drawRectangle(40, 40, 60, $height-40, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->rotate(20, 20, 1.57);
        $page->drawText($stamp, 50, -10);
    }

    /**
    * defines pages numbers for which watermarking will be done
    *
    * @param float $percentage. watermarking percentage.
    * @param int $pageCount Total number of pages in the pdf document.
    *
    * @return array $pagesToWatermark page numbers
    */
    function getPagesToWatermark($percentage, $pageCount) {
        $maxPage = $pageCount - 1;
        $nbPagesToWatermark = ceil($pageCount * $percentage);
        $pagesToWatermark = array();
        for($i = 0; $i < $nbPagesToWatermark; $i++) {
            $maxIter = 5;
            do {
                $maxIter--;
                $pageNo = mt_rand(0, $maxPage);
            } while(isset($pagesToWatermark[$pageNo]) && $maxIter > 0);
            $pagesToWatermark[$pageNo] = $pageNo;
        }
        return $pagesToWatermark;
    }
}
?>
