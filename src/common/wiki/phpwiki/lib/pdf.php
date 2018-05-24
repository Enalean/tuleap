<?php // -*-php-*-
rcs_id('$Id: pdf.php,v 1.7 2004/09/22 13:46:26 rurban Exp $');
/*
 Copyright (C) 2003 Olivier PLATHEY
 Copyright (C) 200? Don Sebà
 Copyright (C) 2004 Reini Urban

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */ 
/*
 * Credits:
 * PDF functions taken from FPDF http://www.fpdf.org
 * Edited for PHPWebthings by Don Sebà
 *   Feel free to edit , enhance the module, and please share it at http://www.phpdbform.com
 *   Keep PHPWT COOL submit your modules/themes/mods, it will help to improve ! :)
 * Changes for PhpWiki by Reini Urban
 */

require_once('lib/fpdf.php');

// http://phpwiki.sourceforge.net/phpwiki/PhpWikiToDocBookAndPDF
// htmldoc or ghostscript + html2ps or docbook (dbdoclet, xsltproc, fop)
// http://www.easysw.com/htmldoc
//define("USE_EXTERNAL_HTML2PDF", "htmldoc --quiet --format pdf14 --jpeg --webpage --no-toc --no-title %s");

class PDF extends FPDF {
    var $B = 0;
    var $I = 0;
    var $U = 0;
    var $HREF = '';

    function PDF ($orientation='P', $unit='mm', $format='A4') {
        $this->FPDF($orientation,$unit,$format);
	//$this->SetCompression(false);
    }

    // Simple HTML to PDF converter
    function ConvertFromHTML($html) {
        $html = str_replace("\n",' ',$html);
        $a = preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
        foreach($a as $i=>$e) {
            if ($i % 2 == 0) {
                //Text
                if($this->HREF)
                    $this->PutLink($this->HREF,$e);
                else
                    $this->Write(5,$e);
            } else {
                //Tag
                if ($e{0} == '/')
                    $this->CloseTag(strtoupper(substr($e,1)));
                else {
                    //Attributes
                    $a2 = explode(' ',$e);
                    $tag = strtoupper(array_shift($a2));
                    $attr = array();
                    foreach ($a2 as $v)
                        if (preg_match('/^([^=]*)=["\']?([^"\']*)["\']?$/D',$v,$a3))
                            $attr[strtoupper($a3[1])]=$a3[2];
                    $this->OpenTag($tag,$attr);
                }
            }
        }
    }

    function Header() {
        $this->SetY(-15);
        $this->SetFont('Arial','',9);
	//URL - space from side - space from top - width
	if (!DEBUG) {
          $imgurl = $GLOBALS['WikiTheme']->_findFile("images/logo.png"); // header and wikilogo
          if ($imgurl)
            $this->Image($imgurl,3,3);
        }
        //Line break
        //$this->Ln(30);
    }

    function Footer() {
        //global $cfg, $config, $lang;
        //1.5cm below top
        $this->SetY(-15);
        //Arial italic 8
        $this->SetFont('arial','I',8);
    }

    function OpenTag($tag,$attr) {
        if($tag=='B' or $tag=='I' or $tag=='U')
            $this->SetStyle($tag,true);
        if($tag=='A')
            $this->HREF=$attr['HREF'];
        if($tag=='BR')
            $this->Ln(5);
    }

    function CloseTag($tag) {
        if($tag=='B' or $tag=='I' or $tag=='U')
            $this->SetStyle($tag,false);
        if($tag=='A')
            $this->HREF='';
    }
    
    //Wijzig stijl en selecteer lettertype
    function SetStyle($tag,$enable) {
        $this->$tag+=($enable ? 1 : -1);
        $style='';
        foreach(array('B','I','U') as $s)
            if($this->$s > 0)
                $style .= $s;
        $this->SetFont('',$style);
    }

    function PutLink($URL,$txt) {
        // hyperlink as simple underlined text
        $this->SetTextColor(0,0,255);
        $this->SetStyle('U',true);
        $this->Write(5,$txt,$URL);
        $this->SetStyle('U',false);
        $this->SetTextColor(0);
    }
}

function ConvertAndDisplayPdf (&$request) {
    if (empty($request->_is_buffering_output))
        $request->buffer_output(false/*'nocompress'*/);
    $pagename = $request->getArg('pagename');
    $dest = $request->getArg('dest');
    //TODO: inline cached content: /getimg.php? => image.png
    // Disable CACHE

    include_once("lib/display.php");
    displayPage($request);
    $html = ob_get_contents();

    // use fpdf:
    if ($GLOBALS['LANG'] == 'ja') {
        include_once("lib/fpdf/japanese.php");
        $pdf = new PDF_Japanese;
    } elseif ($GLOBALS['LANG'] == 'zh') {
        include_once("lib/fpdf/chinese.php");
        $pdf = new PDF_Chinese;
    } else {
        $pdf = new PDF;
    }
    $pdf->Open();
    $pdf->AddPage();
    $pdf->ConvertFromHTML($html);
    $request->discardOutput();
    $request->buffer_output(false/*'nocompress'*/);
    $pdf->Output($pagename.".pdf", $dest ? $dest : 'I');
    if (!empty($errormsg)) {
        $request->discardOutput();
    }
}

// $Log: pdf.php,v $
// Revision 1.7  2004/09/22 13:46:26  rurban
// centralize upload paths.
// major WikiPluginCached feature enhancement:
//   support _STATIC pages in uploads/ instead of dynamic getimg.php? subrequests.
//   mainly for debugging, cache problems and action=pdf
//
// Revision 1.6  2004/09/20 13:40:19  rurban
// define all config.ini settings, only the supported will be taken from -default.
// support USE_EXTERNAL_HTML2PDF renderer (htmldoc tested)
//
// Revision 1.5  2004/09/17 14:19:02  rurban
// default pdf dest: browser
//
// Revision 1.4  2004/06/14 11:31:37  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.3  2004/05/15 19:49:09  rurban
// moved action_pdf to lib/pdf.php
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
