<?php
/*=======================================================================
 // File:        JPGRAPH_TABLE.PHP
 // Description: Classes to create basic tables of data
 // Created:     2006-01-25
 // Ver:         $Id: jpgraph_table.php 1514 2009-07-07 11:15:58Z ljp $
 //
 // Copyright (c) Asial Corporation. All rights reserved.
 //========================================================================
 */

// Style of grid lines in table
DEFINE('TGRID_SINGLE',1);
DEFINE('TGRID_DOUBLE',2);
DEFINE('TGRID_DOUBLE2',3);

// Type of constrain for image constrain
DEFINE('TIMG_WIDTH',1);
DEFINE('TIMG_HEIGHT',2);

//---------------------------------------------------------------------
// CLASS GTextTableCell
// Description:
// Internal class that represents each cell in the table
//---------------------------------------------------------------------
class GTextTableCell {
    public $iColSpan=1,$iRowSpan=1;
    public $iMarginLeft=5,$iMarginRight=5,$iMarginTop=5,$iMarginBottom=5;
    public $iVal=NULL;
    private $iBGColor='', $iFontColor='black';
    private $iFF=FF_FONT1,$iFS=FS_NORMAL,$iFSize=10;
    private $iRow=0, $iCol=0;
    private $iVertAlign = 'bottom', $iHorAlign = 'left';
    private $iMerged=FALSE,$iPRow=NULL,$iPCol=NULL;
    private $iTable=NULL;
    private $iGridColor=array('darkgray','darkgray','darkgray','darkgray');
    private $iGridWeight=array(1,1,0,0); // left,top,bottom,right;
    private $iGridStyle=array(TGRID_SINGLE,TGRID_SINGLE,TGRID_SINGLE,TGRID_SINGLE); // left,top,bottom,right;
    private $iNumberFormat=null;
    private $iIcon=null, $iIconConstrain=array();
    private $iCSIMtarget = '',$iCSIMwintarget = '', $iCSIMalt = '', $iCSIMArea = '';

    function __construct($aVal='',$aRow=0,$aCol=0) {
        $this->iVal = new Text($aVal);
        $this->iRow = $aRow;
        $this->iCol = $aCol;
        $this->iPRow = $aRow; // Initialiy each cell is its own parent
        $this->iPCol = $aCol;
        $this->iIconConstrain = array(-1,-1);
    }

    function Init($aTable) {
        $this->iTable = $aTable;
    }

    function SetCSIMTarget($aTarget,$aAlt='',$aWinTarget='') {
        $this->iCSIMtarget = $aTarget;
        $this->iCSIMwintarget = $aWinTarget;
        $this->iCSIMalt = $aAlt;
    }

    function GetCSIMArea() {
        if( $this->iCSIMtarget !== '' )
        return $this->iCSIMArea;
        else
        return '';
    }

    function SetImageConstrain($aType,$aVal) {
        if( !in_array($aType,array(TIMG_WIDTH, TIMG_HEIGHT)) ) {
            JpGraphError::RaiseL(27015);
        }
        $this->iIconConstrain = array($aType,$aVal);
    }

    function SetCountryFlag($aFlag,$aScale=1.0,$aMix=100,$aStdSize=3) {
        $this->iIcon = new IconPlot();
        $this->iIcon->SetCountryFlag($aFlag,0,0,$aScale,$aMix,$aStdSize);
    }

    function SetImage($aFile,$aScale=1.0,$aMix=100) {
        $this->iIcon = new IconPlot($aFile,0,0,$aScale,$aMix);
    }

    function SetImageFromString($aStr,$aScale=1.0,$aMix=100) {
        $this->iIcon = new IconPlot("",0,0,$aScale,$aMix);
        $this->iIcon->CreateFromString($aStr);
    }

    function SetRowColSpan($aRowSpan,$aColSpan) {
        $this->iRowSpan = $aRowSpan;
        $this->iColSpan = $aColSpan;
        $this->iMerged = true;
    }

    function SetMerged($aPRow,$aPCol,$aFlg=true) {
        $this->iMerged = $aFlg;
        $this->iPRow=$aPRow;
        $this->iPCol=$aPCol;
    }

    function IsMerged() {
        return $this->iMerged;
    }

    function SetNumberFormat($aF) {
        $this->iNumberFormat = $aF;
    }

    function Set($aTxt) {
        $this->iVal->Set($aTxt);
    }

    function SetFont($aFF,$aFS,$aFSize) {
        $this->iFF = $aFF;
        $this->iFS = $aFS;
        $this->iFSize = $aFSize;
        $this->iVal->SetFont($aFF,$aFS,$aFSize);
    }

    function SetFillColor($aColor) {
        $this->iBGColor=$aColor;
    }

    function SetFontColor($aColor) {
        $this->iFontColor=$aColor;
    }

    function SetGridColor($aLeft,$aTop=null,$aBottom=null,$aRight=null) {
        if( $aLeft !== null ) $this->iGridColor[0] = $aLeft;
        if( $aTop !== null ) $this->iGridColor[1] = $aTop;
        if( $aBottom !== null ) $this->iGridColor[2] = $aBottom;
        if( $aRight !== null )$this->iGridColor[3] = $aRight;
    }

    function SetGridStyle($aLeft,$aTop=null,$aBottom=null,$aRight=null) {
        if( $aLeft !== null ) $this->iGridStyle[0] = $aLeft;
        if( $aTop !== null ) $this->iGridStyle[1] = $aTop;
        if( $aBottom !== null ) $this->iGridStyle[2] = $aBottom;
        if( $aRight !== null )$this->iGridStyle[3] = $aRight;
    }

    function SetGridWeight($aLeft=null,$aTop=null,$aBottom=null,$aRight=null) {
        $weight_arr = array($aLeft, $aTop, $aBottom, $aRight);
        for ($i = 0; $i < count($weight_arr); $i++) {
            if ($weight_arr[$i] === "") {
                $weight_arr[$i] = 0;
            }
        }
        if( $aLeft !== null ) $this->iGridWeight[0] = $weight_arr[0];
        if( $aTop !== null ) $this->iGridWeight[1] = $weight_arr[1];
        if( $aBottom !== null ) $this->iGridWeight[2] = $weight_arr[2];
        if( $aRight !== null ) $this->iGridWeight[3] = $weight_arr[3];
    }

    function SetMargin($aLeft,$aRight,$aTop,$aBottom) {
        $this->iMarginLeft=$aLeft;
        $this->iMarginRight=$aRight;
        $this->iMarginTop=$aTop;
        $this->iMarginBottom=$aBottom;
    }

    function GetWidth($aImg) {
        if( $this->iIcon !== null ) {
            if( $this->iIconConstrain[0] == TIMG_WIDTH ) {
            	$this->iIcon->SetScale(1);
            	$tmp = $this->iIcon->GetWidthHeight();
                $this->iIcon->SetScale($this->iIconConstrain[1]/$tmp[0]);
            }
            elseif( $this->iIconConstrain[0] == TIMG_HEIGHT ) {
            	$this->iIcon->SetScale(1);
            	$tmp = $this->iIcon->GetWidthHeight();
                $this->iIcon->SetScale($this->iIconConstrain[1]/$tmp[1]);
            }
            $tmp = $this->iIcon->GetWidthHeight();
            $iwidth = $tmp[0];
        }
        else {
            $iwidth=0;
        }
        if( $this->iTable->iCells[$this->iPRow][$this->iPCol]->iVal->dir == 0 ) {
            $pwidth = $this->iTable->iCells[$this->iPRow][$this->iPCol]->iVal->GetWidth($aImg);
        }
        elseif( $this->iTable->iCells[$this->iPRow][$this->iPCol]->iVal->dir == 90 ) {
            $pwidth = $this->iTable->iCells[$this->iPRow][$this->iPCol]->iVal->GetFontHeight($aImg)+2;
        }
        else {
            $pwidth = $this->iTable->iCells[$this->iPRow][$this->iPCol]->iVal->GetWidth($aImg)+2;
        }

        $pcolspan = $this->iTable->iCells[$this->iPRow][$this->iPCol]->iColSpan;
        return round(max($iwidth,$pwidth)/$pcolspan) + $this->iMarginLeft + $this->iMarginRight;
    }

    function GetHeight($aImg) {
        if( $this->iIcon !== null ) {
            if( $this->iIconConstrain[0] == TIMG_WIDTH ) {
            	$this->iIcon->SetScale(1);
            	$tmp = $this->iIcon->GetWidthHeight();
            	$this->iIcon->SetScale($this->iIconConstrain[1]/$tmp[0]);
            }
            elseif( $this->iIconConstrain[0] == TIMG_HEIGHT ) {
            	$this->iIcon->SetScale(1);
            	$tmp = $this->iIcon->GetWidthHeight();
                $this->iIcon->SetScale($this->iIconConstrain[1]/$tmp[1]);
            }
            $tmp = $this->iIcon->GetWidthHeight();
            $iheight =  $tmp[1];
        }
        else {
            $iheight = 0;
        }
        if( $this->iTable->iCells[$this->iPRow][$this->iPCol]->iVal->dir == 0 ) {
            $pheight = $this->iTable->iCells[$this->iPRow][$this->iPCol]->iVal->GetHeight($aImg);
        }
        else {
            $pheight = $this->iTable->iCells[$this->iPRow][$this->iPCol]->iVal->GetHeight($aImg)+1;
        }
        $prowspan = $this->iTable->iCells[$this->iPRow][$this->iPCol]->iRowSpan;
        return round(max($iheight,$pheight)/$prowspan) + $this->iMarginTop + $this->iMarginBottom;
    }

    function SetAlign($aHorAlign='left',$aVertAlign='bottom') {
        $aHorAlign = strtolower($aHorAlign);
        $aVertAlign = strtolower($aVertAlign);
        $chk = array('left','right','center','bottom','top','middle');
        if( !in_array($aHorAlign,$chk) || !in_array($aVertAlign,$chk) ) {
            JpGraphError::RaiseL(27011,$aHorAlign,$aVertAlign);
        }
        $this->iVertAlign = $aVertAlign;
        $this->iHorAlign = $aHorAlign;
    }

    function AdjustMarginsForGrid() {
        if( $this->iCol > 0 ) {
            switch( $this->iGridStyle[0] ) {
                case TGRID_SINGLE:  $wf=1;  break;
                case TGRID_DOUBLE:  $wf=3;  break;
                case TGRID_DOUBLE2: $wf=4;  break;
            }
            $this->iMarginLeft += $this->iGridWeight[0]*$wf;
        }
        if( $this->iRow > 0 ) {
            switch( $this->iGridStyle[1] ) {
                case TGRID_SINGLE:  $wf=1;  break;
                case TGRID_DOUBLE:  $wf=3;  break;
                case TGRID_DOUBLE2: $wf=4;  break;
            }
            $this->iMarginTop += $this->iGridWeight[1]*$wf;
        }
        if( $this->iRow+$this->iRowSpan-1 < $this->iTable->iSize[0]-1 ) {
            switch( $this->iGridStyle[2] ) {
                case TGRID_SINGLE: $wf=1; break;
                case TGRID_DOUBLE: $wf=3; break;
                case TGRID_DOUBLE2: $wf=4; break;
            }
            $this->iMarginBottom += $this->iGridWeight[2]*$wf;
        }
        if( $this->iCol+$this->iColSpan-1 < $this->iTable->iSize[1]-1 ) {
            switch( $this->iGridStyle[3] ) {
                case TGRID_SINGLE: $wf=1; break;
                case TGRID_DOUBLE: $wf=3; break;
                case TGRID_DOUBLE2: $wf=4; break;
            }
            $this->iMarginRight += $this->iGridWeight[3]*$wf;
        }
    }

    function StrokeVGrid($aImg,$aX,$aY,$aWidth,$aHeight,$aDir=1) {
        // Left or right grid line
        // For the right we increase the X-pos and for the right we decrease it. This is
        // determined by the direction argument.
        $idx = $aDir==1 ? 0 : 3;

        // We don't stroke the grid lines that are on the edge of the table since this is
        // the place of the border.
        if( ( ($this->iCol > 0 && $idx==0) || ($this->iCol+$this->iColSpan-1 < $this->iTable->iSize[1]-1 && $idx==3) )
        && $this->iGridWeight[$idx] > 0 ) {
            $x = $aDir==1 ? $aX : $aX + $aWidth-1;
            $y = $aY+$aHeight-1;
            $aImg->SetColor($this->iGridColor[$idx]);
            switch( $this->iGridStyle[$idx] ) {
                case TGRID_SINGLE:
                    for( $i=0; $i < $this->iGridWeight[$idx]; ++$i )
                    $aImg->Line($x+$i*$aDir,$aY, $x+$i*$aDir,$y);
                    break;

                case TGRID_DOUBLE:
                    for( $i=0; $i < $this->iGridWeight[$idx]; ++$i )
                    $aImg->Line($x+$i*$aDir,$aY, $x+$i*$aDir,$y);
                    $x += $this->iGridWeight[$idx]*2;
                    for( $i=0; $i < $this->iGridWeight[$idx]; ++$i )
                    $aImg->Line($x+$i*$aDir,$aY, $x+$i*$aDir,$y);
                    break;

                case TGRID_DOUBLE2:
                    for( $i=0; $i < $this->iGridWeight[$idx]*2; ++$i )
                    $aImg->Line($x+$i*$aDir,$aY,$x+$i*$aDir,$y);
                    $x += $this->iGridWeight[$idx]*3;
                    for( $i=0; $i < $this->iGridWeight[$idx]; ++$i )
                    $aImg->Line($x+$i*$aDir,$aY, $x+$i*$aDir,$y);
                    break;
            }
        }
    }

    function StrokeHGrid($aImg,$aX,$aY,$aWidth,$aHeight,$aDir=1) {
        // Top or bottom grid line
        // For the left we increase the X-pos and for the right we decrease it. This is
        // determined by the direction argument.
        $idx = $aDir==1 ? 1 : 2;

        // We don't stroke the grid lines that are on the edge of the table since this is
        // the place of the border.
        if( ( ($this->iRow > 0 && $idx==1) || ($this->iRow+$this->iRowSpan-1 < $this->iTable->iSize[0]-1 && $idx==2) )
        && $this->iGridWeight[$idx] > 0) {
            $y = $aDir==1 ? $aY : $aY+$aHeight-1;
            $x = $aX+$aWidth-1;
            $aImg->SetColor($this->iGridColor[$idx]);
            switch( $this->iGridStyle[$idx] ) {
                case TGRID_SINGLE:
                    for( $i=0; $i < $this->iGridWeight[$idx]; ++$i )
                    $aImg->Line($aX,$y+$i, $x,$y+$i);
                    break;

                case TGRID_DOUBLE:
                    for( $i=0; $i < $this->iGridWeight[$idx]; ++$i )
                    $aImg->Line($aX,$y+$i, $x,$y+$i);
                    $y += $this->iGridWeight[$idx]*2;
                    for( $i=0; $i < $this->iGridWeight[$idx]; ++$i )
                    $aImg->Line($aX,$y+$i, $x,$y+$i);
                    break;

                case TGRID_DOUBLE2:
                    for( $i=0; $i < $this->iGridWeight[$idx]*2; ++$i )
                    $aImg->Line($aX,$y+$i, $x,$y+$i);
                    $y += $this->iGridWeight[$idx]*3;
                    for( $i=0; $i < $this->iGridWeight[$idx]; ++$i )
                    $aImg->Line($aX,$y+$i, $x,$y+$i);
                    break;
            }
        }
    }

    function Stroke($aImg,$aX,$aY,$aWidth,$aHeight) {
        // If this is a merged cell we only stroke if it is the parent cell.
        // The parent cell holds the merged cell block
        if( $this->iMerged && ($this->iRow != $this->iPRow || $this->iCol != $this->iPCol) ) {
            return;
        }

        if( $this->iBGColor != '' ) {
            $aImg->SetColor($this->iBGColor);
            $aImg->FilledRectangle($aX,$aY,$aX+$aWidth-1,$aY+$aHeight-1);
        }

        $coords = $aX.','.$aY.','.($aX+$aWidth-1).','.$aY.','.($aX+$aWidth-1).','.($aY+$aHeight-1).','.$aX.','.($aY+$aHeight-1);
        if( ! empty($this->iCSIMtarget) ) {
            $this->iCSIMArea = '<area shape="poly" coords="'.$coords.'" href="'.$this->iCSIMtarget.'"';
            if( ! empty($this->iCSIMwintarget) ) {
                $this->iCSIMArea .= " target=\"".$this->iCSIMwintarget."\"";
            }
            if( ! empty($this->iCSIMalt) ) {
                $this->iCSIMArea .= ' alt="'.$this->iCSIMalt.'" title="'.$this->iCSIMalt."\" ";
            }
            $this->iCSIMArea .= " />\n";
        }

        $this->StrokeVGrid($aImg,$aX,$aY,$aWidth,$aHeight);
        $this->StrokeVGrid($aImg,$aX,$aY,$aWidth,$aHeight,-1);
        $this->StrokeHGrid($aImg,$aX,$aY,$aWidth,$aHeight);
        $this->StrokeHGrid($aImg,$aX,$aY,$aWidth,$aHeight,-1);

        if( $this->iIcon !== null ) {
            switch( $this->iHorAlign ) {
                case 'left':
                    $x = $aX+$this->iMarginLeft;
                    $hanchor='left';
                    break;
                case 'center':
                case 'middle':
                    $x = $aX+$this->iMarginLeft+round(($aWidth-$this->iMarginLeft-$this->iMarginRight)/2);
                    $hanchor='center';
                    break;
                case 'right':
                    $x = $aX+$aWidth-$this->iMarginRight-1;
                    $hanchor='right';
                    break;
                default:
                    JpGraphError::RaiseL(27012,$this->iHorAlign);
            }

            switch( $this->iVertAlign ) {
                case 'top':
                    $y = $aY+$this->iMarginTop;
                    $vanchor='top';
                    break;
                case 'center':
                case 'middle':
                    $y = $aY+$this->iMarginTop+round(($aHeight-$this->iMarginTop-$this->iMarginBottom)/2);
                    $vanchor='center';
                    break;
                case 'bottom':
                    $y = $aY+$aHeight-1-$this->iMarginBottom;
                    $vanchor='bottom';
                    break;
                default:
                    JpGraphError::RaiseL(27012,$this->iVertAlign);
            }
            $this->iIcon->SetAnchor($hanchor,$vanchor);
            $this->iIcon->_Stroke($aImg,$x,$y);
        }
        $this->iVal->SetColor($this->iFontColor);
        $this->iVal->SetFont($this->iFF,$this->iFS,$this->iFSize);
        switch( $this->iHorAlign ) {
            case 'left':
                $x = $aX+$this->iMarginLeft;
                break;
            case 'center':
            case 'middle':
                $x = $aX+$this->iMarginLeft+round(($aWidth-$this->iMarginLeft-$this->iMarginRight)/2);
                break;
            case 'right':
                $x = $aX+$aWidth-$this->iMarginRight-1;
                break;
            default:
                JpGraphError::RaiseL(27012,$this->iHorAlign);
        }
        // A workaround for the shortcomings in the TTF font handling in GD
        // The anchor position for rotated text (=90) is to "short" so we add
        // an offset based on the actual font size
        if( $this->iVal->dir != 0 && $this->iVal->font_family >= 10 ) {
            $aY += 4 + round($this->iVal->font_size*0.8);
        }
        switch( $this->iVertAlign ) {
            case 'top':
                $y = $aY+$this->iMarginTop;
                break;
            case 'center':
            case 'middle':
                $y = $aY+$this->iMarginTop+round(($aHeight-$this->iMarginTop-$this->iMarginBottom)/2);
                //$y -= round($this->iVal->GetFontHeight($aImg)/2);
                $y -= round($this->iVal->GetHeight($aImg)/2);
                break;
            case 'bottom':
                //$y = $aY+$aHeight-1-$this->iMarginBottom-$this->iVal->GetFontHeight($aImg);
                $y = $aY+$aHeight-$this->iMarginBottom-$this->iVal->GetHeight($aImg);
                break;
            default:
                JpGraphError::RaiseL(27012,$this->iVertAlign);
        }
        $this->iVal->SetAlign($this->iHorAlign,'top');
        if( $this->iNumberFormat !== null && is_numeric($this->iVal->t) ) {
            $this->iVal->t = sprintf($this->iNumberFormat,$this->iVal->t);
        }
        $this->iVal->Stroke($aImg,$x,$y);
    }
}

//---------------------------------------------------------------------
// CLASS GTextTable
// Description:
// Graphic text table
//---------------------------------------------------------------------
class GTextTable {
    public $iCells = array(), $iSize=array(0,0); // Need to be public since they are used by the cell
    private $iWidth=0, $iHeight=0;
    private $iColWidth=NULL,$iRowHeight=NULL;
    private $iImg=NULL;
    private $iXPos=0, $iYPos=0;
    private $iScaleXPos=null,$iScaleYPos=null;
    private $iBGColor='';
    private $iBorderColor='black',$iBorderWeight=1;
    private $iInit=false;
    private $iYAnchor='top',$iXAnchor='left';
    /*-----------------------------------------------------------------
     * First and second phase constructors
     *-----------------------------------------------------------------
     */
    function __construct() {
        // Empty
    }

    function Init($aRows=0,$aCols=0,$aFillText='') {
        $this->iSize[0] = $aRows;
        $this->iSize[1] = $aCols;
        for($i=0; $i < $this->iSize[0]; ++$i) {
            for($j=0; $j < $this->iSize[1]; ++$j) {
                $this->iCells[$i][$j] = new GTextTableCell($aFillText,$i,$j);
                $this->iCells[$i][$j]->Init($this);
            }
        }
        $this->iInit=true;
    }

    /*-----------------------------------------------------------------
     * Outer border of table
     *-----------------------------------------------------------------
     */
    function SetBorder($aWeight=1,$aColor='black') {
        $this->iBorderColor=$aColor;
        $this->iBorderWeight = $aWeight;
    }


    /*-----------------------------------------------------------------
     * Position in graph of table
     *-----------------------------------------------------------------
     */
    function SetPos($aX,$aY) {
        $this->iXPos = $aX;
        $this->iYPos = $aY;
    }

    function SetScalePos($aX,$aY) {
        $this->iScaleXPos = $aX;
        $this->iScaleYPos = $aY;
    }

    function SetAnchorPos($aXAnchor,$aYAnchor='top') {
        $this->iXAnchor = $aXAnchor;
        $this->iYAnchor = $aYAnchor;
    }

    /*-----------------------------------------------------------------
     * Setup country flag in a cell
     *-----------------------------------------------------------------
     */
    function SetCellCountryFlag($aRow,$aCol,$aFlag,$aScale=1.0,$aMix=100,$aStdSize=3) {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->SetCountryFlag($aFlag,$aScale,$aMix,$aStdSize);

    }

    /*-----------------------------------------------------------------
     * Setup image in a cell
     *-----------------------------------------------------------------
     */
    function SetCellImage($aRow,$aCol,$aFile,$aScale=1.0,$aMix=100) {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->SetImage($aFile,$aScale,$aMix);
    }

    function SetRowImage($aRow,$aFile,$aScale=1.0,$aMix=100) {
        $this->_chkR($aRow);
        for($j=0; $j < $this->iSize[1]; ++$j) {
            $this->iCells[$aRow][$j]->SetImage($aFile,$aScale,$aMix);
        }
    }

    function SetColImage($aCol,$aFile,$aScale=1.0,$aMix=100) {
        $this->_chkC($aCol);
        for($j=0; $j < $this->iSize[0]; ++$j) {
            $this->iCells[$j][$aCol]->SetImage($aFile,$aScale,$aMix);
        }
    }

    function SetImage($aFileR1,$aScaleC1=null,$aMixR2=null,$aC2=null,$aFile=null,$aScale=1.0,$aMix=100) {
        if( $aScaleC1 !== null && $aMixR2!==null && $aC2!==null && $aFile!==null ) {
            $this->_chkR($aArgR1);  $this->_chkC($aC1);
            $this->_chkR($aR2);  $this->_chkC($aC2);
        }
        else {
            if( $aScaleC1 !== null ) $aScale = $aScaleC1;
            if( $aMixR2 !== null ) $aMix = $aMixR2;
            $aFile = $aFileR1;
            $aMixR2 = $this->iSize[0]-1; $aFileR1 = 0;
            $aC2 = $this->iSize[1]-1; $aScaleC1 = 0;
        }
        for($i=$aArgR1; $i <= $aR2; ++$i) {
            for($j=$aC1; $j <= $aC2; ++$j) {
                $this->iCells[$i][$j]->SetImage($aFile,$aScale,$aMix);
            }
        }
    }

    function SetCellImageConstrain($aRow,$aCol,$aType,$aVal) {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->SetImageConstrain($aType,$aVal);
    }

    /*-----------------------------------------------------------------
     * Generate a HTML version of the table
     *-----------------------------------------------------------------
     */
    function toString() {
        $t = '<table border=1 cellspacing=0 cellpadding=0>';
        for($i=0; $i < $this->iSize[0]; ++$i) {
            $t .= '<tr>';
            for($j=0; $j < $this->iSize[1]; ++$j) {
                $t .= '<td>';
                if( $this->iCells[$i][$j]->iMerged )
                $t .= 'M ';
                $t .= 'val='.$this->iCells[$i][$j]->iVal->t;
                $t .= ' (cs='.$this->iCells[$i][$j]->iColSpan.
        ', rs='.$this->iCells[$i][$j]->iRowSpan.')';
                $t .= '</td>';
            }
            $t .= '</tr>';
        }
        $t .= '</table>';
        return $t;
    }

    /*-----------------------------------------------------------------
     * Specify data for table
     *-----------------------------------------------------------------
     */
    function Set($aArg1,$aArg2=NULL,$aArg3=NULL) {
        if( $aArg2===NULL && $aArg3===NULL ) {
            if( is_array($aArg1) ) {
                if( is_array($aArg1[0]) ) {
                    $m = count($aArg1);
                    // Find the longest row
                    $n=0;
                    for($i=0; $i < $m; ++$i)
                    $n = max(count($aArg1[$i]),$n);
                    for($i=0; $i < $m; ++$i) {
                        for($j=0; $j < $n; ++$j) {
                            if( isset($aArg1[$i][$j]) ){
                                $this->_setcell($i,$j,(string)$aArg1[$i][$j]);
                            }
                            else {
                                $this->_setcell($i,$j);
                            }
                        }
                    }
                    $this->iSize[0] = $m;
                    $this->iSize[1] = $n;
                    $this->iInit=true;
                }
                else {
                    JpGraphError::RaiseL(27001);
                    //('Illegal argument to GTextTable::Set(). Array must be 2 dimensional');
                }
            }
            else {
                JpGraphError::RaiseL(27002);
                //('Illegal argument to GTextTable::Set()');
            }
        }
        else {
            // Must be in the form (row,col,val)
            $this->_chkR($aArg1);
            $this->_chkC($aArg2);
            $this->_setcell($aArg1,$aArg2,(string)$aArg3);
        }
    }

    /*---------------------------------------------------------------------
     * Cell margin setting
     *---------------------------------------------------------------------
     */
    function SetPadding($aArgR1,$aC1=null,$aR2=null,$aC2=null,$aPad=null) {
        if( $aC1 !== null && $aR2!==null && $aC2!==null && $aPad!==null ) {
            $this->_chkR($aArgR1);  $this->_chkC($aC1);
            $this->_chkR($aR2);  $this->_chkC($aC2);
        }
        else {
            $aPad = $aArgR1;
            $aR2 = $this->iSize[0]-1; $aArgR1 = 0;
            $aC2 = $this->iSize[1]-1; $aC1 = 0;
        }
        for($i=$aArgR1; $i <= $aR2; ++$i) {
            for($j=$aC1; $j <= $aC2; ++$j) {
                $this->iCells[$i][$j]->SetMargin($aPad,$aPad,$aPad,$aPad);
            }
        }
    }

    function SetRowPadding($aRow,$aPad) {
        $this->_chkR($aRow);
        for($j=0; $j < $this->iSize[1]; ++$j) {
            $this->iCells[$aRow][$j]->SetMargin($aPad,$aPad,$aPad,$aPad);
        }
    }

    function SetColPadding($aCol,$aPad) {
        $this->_chkC($aCol);
        for($j=0; $j < $this->iSize[0]; ++$j) {
            $this->iCells[$j][$aCol]->SetMargin($aPad,$aPad,$aPad,$aPad);
        }
    }

    function SetCellPadding($aRow,$aCol,$aPad) {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->SetMargin($aPad,$aPad,$aPad,$aPad);
    }


    /*---------------------------------------------------------------------
     * Cell text orientation setting
     *---------------------------------------------------------------------
     */
    function SetTextOrientation($aArgR1,$aC1=null,$aR2=null,$aC2=null,$aO=null) {
        if( $aC1 !== null && $aR2!==null && $aC2!==null && $aPad!==null ) {
            $this->_chkR($aArgR1);  $this->_chkC($aC1);
            $this->_chkR($aR2);  $this->_chkC($aC2);
        }
        else {
            $aO = $aArgR1;
            $aR2 = $this->iSize[0]-1; $aArgR1 = 0;
            $aC2 = $this->iSize[1]-1; $aC1 = 0;
        }
        for($i=$aArgR1; $i <= $aR2; ++$i) {
            for($j=$aC1; $j <= $aC2; ++$j) {
                $this->iCells[$i][$j]->iVal->SetOrientation($aO);
            }
        }
    }

    function SetRowTextOrientation($aRow,$aO) {
        $this->_chkR($aRow);
        for($j=0; $j < $this->iSize[1]; ++$j) {
            $this->iCells[$aRow][$j]->iVal->SetOrientation($aO);
        }
    }

    function SetColTextOrientation($aCol,$aO) {
        $this->_chkC($aCol);
        for($j=0; $j < $this->iSize[0]; ++$j) {
            $this->iCells[$j][$aCol]->iVal->SetOrientation($aO);
        }
    }

    function SetCellTextOrientation($aRow,$aCol,$aO) {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->iVal->SetOrientation($aO);
    }




    /*---------------------------------------------------------------------
     * Font color setting
     *---------------------------------------------------------------------
     */

    function SetColor($aArgR1,$aC1=null,$aR2=null,$aC2=null,$aArg=null) {
        if( $aC1 !== null && $aR2!==null && $aC2!==null && $aArg!==null ) {
            $this->_chkR($aArgR1);  $this->_chkC($aC1);
            $this->_chkR($aR2);  $this->_chkC($aC2);
        }
        else {
            $aArg = $aArgR1;
            $aR2 = $this->iSize[0]-1; $aArgR1 = 0;
            $aC2 = $this->iSize[1]-1; $aC1 = 0;
        }
        for($i=$aArgR1; $i <= $aR2; ++$i) {
            for($j=$aC1; $j <= $aC2; ++$j) {
                $this->iCells[$i][$j]->SetFontColor($aArg);
            }
        }
    }

    function SetRowColor($aRow,$aColor) {
        $this->_chkR($aRow);
        for($j=0; $j < $this->iSize[1]; ++$j) {
            $this->iCells[$aRow][$j]->SetFontColor($aColor);
        }
    }

    function SetColColor($aCol,$aColor) {
        $this->_chkC($aCol);
        for($i=0; $i < $this->iSize[0]; ++$i) {
            $this->iCells[$i][$aCol]->SetFontColor($aColor);
        }
    }

    function SetCellColor($aRow,$aCol,$aColor) {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->SetFontColor($aColor);
    }

    /*---------------------------------------------------------------------
     * Fill color settings
     *---------------------------------------------------------------------
     */

    function SetFillColor($aArgR1,$aC1=null,$aR2=null,$aC2=null,$aArg=null) {
        if( $aC1 !== null && $aR2!==null && $aC2!==null && $aArg!==null ) {
            $this->_chkR($aArgR1);  $this->_chkC($aC1);
            $this->_chkR($aR2);  $this->_chkC($aC2);
            for($i=$aArgR1; $i <= $aR2; ++$i) {
                for($j=$aC1; $j <= $aC2; ++$j) {
                    $this->iCells[$i][$j]->SetFillColor($aArg);
                }
            }
        }
        else {
            $this->iBGColor = $aArgR1;
        }
    }

    function SetRowFillColor($aRow,$aColor) {
        $this->_chkR($aRow);
        for($j=0; $j < $this->iSize[1]; ++$j) {
            $this->iCells[$aRow][$j]->SetFillColor($aColor);
        }
    }

    function SetColFillColor($aCol,$aColor) {
        $this->_chkC($aCol);
        for($i=0; $i < $this->iSize[0]; ++$i) {
            $this->iCells[$i][$aCol]->SetFillColor($aColor);
        }
    }

    function SetCellFillColor($aRow,$aCol,$aColor) {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->SetFillColor($aColor);
    }

    /*---------------------------------------------------------------------
     * Font family setting
     *---------------------------------------------------------------------
     */
    function SetFont() {
        $numargs = func_num_args();
        if( $numargs == 2 || $numargs == 3 ) {
            $aFF = func_get_arg(0);
            $aFS = func_get_arg(1);
            if( $numargs == 3 )
            $aFSize=func_get_arg(2);
            else
            $aFSize=10;
            $aR2 = $this->iSize[0]-1; $aR1 = 0;
            $aC2 = $this->iSize[1]-1; $aC1 = 0;

        }
        elseif($numargs == 6 || $numargs == 7 ) {
            $aR1 = func_get_arg(0); $aC1 = func_get_arg(1);
            $aR2 = func_get_arg(2); $aC2 = func_get_arg(3);
            $aFF = func_get_arg(4); $aFS = func_get_arg(5);
            if( $numargs == 7 )
            $aFSize=func_get_arg(6);
            else
            $aFSize=10;
        }
        else {
            JpGraphError::RaiseL(27003);
            //('Wrong number of arguments to GTextTable::SetColor()');
        }
        $this->_chkR($aR1);  $this->_chkC($aC1);
        $this->_chkR($aR2);  $this->_chkC($aC2);
        for($i=$aR1; $i <= $aR2; ++$i) {
            for($j=$aC1; $j <= $aC2; ++$j) {
                $this->iCells[$i][$j]->SetFont($aFF,$aFS,$aFSize);
            }
        }
    }

    function SetRowFont($aRow,$aFF,$aFS,$aFSize=10) {
        $this->_chkR($aRow);
        for($j=0; $j < $this->iSize[1]; ++$j) {
            $this->iCells[$aRow][$j]->SetFont($aFF,$aFS,$aFSize);
        }
    }

    function SetColFont($aCol,$aFF,$aFS,$aFSize=10) {
        $this->_chkC($aCol);
        for($i=0; $i < $this->iSize[0]; ++$i) {
            $this->iCells[$i][$aCol]->SetFont($aFF,$aFS,$aFSize);
        }
    }

    function SetCellFont($aRow,$aCol,$aFF,$aFS,$aFSize=10) {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->SetFont($aFF,$aFS,$aFSize);
    }

    /*---------------------------------------------------------------------
     * Cell align settings
     *---------------------------------------------------------------------
     */

    function SetAlign($aR1HAlign=null,$aC1VAlign=null,$aR2=null,$aC2=null,$aHArg=null,$aVArg='center') {
        if( $aC1VAlign !== null && $aR2!==null && $aC2!==null && $aHArg!==null ) {
            $this->_chkR($aR1HAlign);  $this->_chkC($aC1VAlign);
            $this->_chkR($aR2);  $this->_chkC($aC2);
        }
        else {
            if( $aR1HAlign === null ) {
                JpGraphError::RaiseL(27010);
            }
            if( $aC1VAlign === null ) {
                $aC1VAlign = 'center';
            }
            $aHArg = $aR1HAlign;
            $aVArg = $aC1VAlign === null ? 'center' : $aC1VAlign ;
            $aR2 = $this->iSize[0]-1; $aR1HAlign = 0;
            $aC2 = $this->iSize[1]-1; $aC1VAlign = 0;
        }
        for($i=$aR1HAlign; $i <= $aR2; ++$i) {
            for($j=$aC1VAlign; $j <= $aC2; ++$j) {
                $this->iCells[$i][$j]->SetAlign($aHArg,$aVArg);
            }
        }
    }

    function SetCellAlign($aRow,$aCol,$aHorAlign,$aVertAlign='bottom') {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->SetAlign($aHorAlign,$aVertAlign);
    }

    function SetRowAlign($aRow,$aHorAlign,$aVertAlign='bottom') {
        $this->_chkR($aRow);
        for($j=0; $j < $this->iSize[1]; ++$j) {
            $this->iCells[$aRow][$j]->SetAlign($aHorAlign,$aVertAlign);
        }
    }

    function SetColAlign($aCol,$aHorAlign,$aVertAlign='bottom') {
        $this->_chkC($aCol);
        for($i=0; $i < $this->iSize[0]; ++$i) {
            $this->iCells[$i][$aCol]->SetAlign($aHorAlign,$aVertAlign);
        }
    }

    /*---------------------------------------------------------------------
     * Cell number format
     *---------------------------------------------------------------------
     */

    function SetNumberFormat($aArgR1,$aC1=null,$aR2=null,$aC2=null,$aArg=null) {
        if( $aC1 !== null && $aR2!==null && $aC2!==null && $aArg!==null ) {
            $this->_chkR($aArgR1);  $this->_chkC($aC1);
            $this->_chkR($aR2);  $this->_chkC($aC2);
        }
        else {
            $aArg = $aArgR1;
            $aR2 = $this->iSize[0]-1; $aArgR1 = 0;
            $aC2 = $this->iSize[1]-1; $aC1 = 0;
        }
        if( !is_string($aArg) ) {
            JpGraphError::RaiseL(27013); // argument must be a string
        }
        for($i=$aArgR1; $i <= $aR2; ++$i) {
            for($j=$aC1; $j <= $aC2; ++$j) {
                $this->iCells[$i][$j]->SetNumberFormat($aArg);
            }
        }
    }

    function SetRowNumberFormat($aRow,$aF) {
        $this->_chkR($aRow);
        if( !is_string($aF) ) {
            JpGraphError::RaiseL(27013); // argument must be a string
        }
        for($j=0; $j < $this->iSize[1]; ++$j) {
            $this->iCells[$aRow][$j]->SetNumberFormat($aF);
        }
    }

    function SetColNumberFormat($aCol,$aF) {
        $this->_chkC($aCol);
        if( !is_string($aF) ) {
            JpGraphError::RaiseL(27013); // argument must be a string
        }
        for($i=0; $i < $this->iSize[0]; ++$i) {
            $this->iCells[$i][$aCol]->SetNumberFormat($aF);
        }
    }

    function SetCellNumberFormat($aRow,$aCol,$aF) {
        $this->_chkR($aRow); $this->_chkC($aCol);
        if( !is_string($aF) ) {
            JpGraphError::RaiseL(27013); // argument must be a string
        }
        $this->iCells[$aRow][$aCol]->SetNumberFormat($aF);
    }

    /*---------------------------------------------------------------------
     * Set row and column min size
     *---------------------------------------------------------------------
     */

    function SetMinColWidth($aColWidth,$aWidth=null) {
        // If there is only one argument this means that all
        // columns get set to the same width
        if( $aWidth===null ) {
            for($i=0; $i < $this->iSize[1]; ++$i) {
                $this->iColWidth[$i]  = $aColWidth;
            }
        }
        else {
            $this->_chkC($aColWidth);
            $this->iColWidth[$aColWidth]  = $aWidth;
        }
    }

    function SetMinRowHeight($aRowHeight,$aHeight=null) {
        // If there is only one argument this means that all
        // rows get set to the same height
        if( $aHeight===null ) {
            for($i=0; $i < $this->iSize[0]; ++$i) {
                $this->iRowHeight[$i]  = $aRowHeight;
            }
        }
        else {
            $this->_chkR($aRowHeight);
            $this->iRowHeight[$aRowHeight]  = $aHeight;
        }
    }

    /*---------------------------------------------------------------------
     * Grid line settings
     *---------------------------------------------------------------------
     */

    function SetGrid($aWeight=1,$aColor='black',$aStyle=TGRID_SINGLE) {
        $rc = $this->iSize[0];
        $cc = $this->iSize[1];
        for($i=0; $i < $rc; ++$i) {
            for($j=0; $j < $cc; ++$j) {
                $this->iCells[$i][$j]->SetGridColor($aColor,$aColor);
                $this->iCells[$i][$j]->SetGridWeight($aWeight,$aWeight);
                $this->iCells[$i][$j]->SetGridStyle($aStyle);
            }
        }
    }

    function SetColGrid($aCol,$aWeight=1,$aColor='black',$aStyle=TGRID_SINGLE) {
        $this->_chkC($aCol);
        for($i=0; $i < $this->iSize[0]; ++$i) {
            $this->iCells[$i][$aCol]->SetGridWeight($aWeight);
            $this->iCells[$i][$aCol]->SetGridColor($aColor);
            $this->iCells[$i][$aCol]->SetGridStyle($aStyle);
        }
    }

    function SetRowGrid($aRow,$aWeight=1,$aColor='black',$aStyle=TGRID_SINGLE) {
        $this->_chkR($aRow);
        for($j=0; $j < $this->iSize[1]; ++$j) {
            $this->iCells[$aRow][$j]->SetGridWeight(NULL,$aWeight);
            $this->iCells[$aRow][$j]->SetGridColor(NULL,$aColor);
            $this->iCells[$aRow][$j]->SetGridStyle(NULL,$aStyle);
        }
    }

    /*---------------------------------------------------------------------
     * Merge cells
     *---------------------------------------------------------------------
     */

    function MergeRow($aRow,$aHAlign='center',$aVAlign='center') {
        $this->_chkR($aRow);
        $this->MergeCells($aRow,0,$aRow,$this->iSize[1]-1,$aHAlign,$aVAlign);
    }

    function MergeCol($aCol,$aHAlign='center',$aVAlign='center') {
        $this->_chkC($aCol);
        $this->MergeCells(0,$aCol,$this->iSize[0]-1,$aCol,$aHAlign,$aVAlign);
    }

    function MergeCells($aR1,$aC1,$aR2,$aC2,$aHAlign='center',$aVAlign='center') {
        if( $aR1 > $aR2 || $aC1 > $aC2 ) {
            JpGraphError::RaiseL(27004);
            //('GTextTable::MergeCells(). Specified cell range to be merged is not valid.');
        }
        $this->_chkR($aR1); $this->_chkC($aC1);
        $this->_chkR($aR2); $this->_chkC($aC2);
        $rspan = $aR2-$aR1+1;
        $cspan = $aC2-$aC1+1;
        // Setup the parent cell for this merged group
        if( $this->iCells[$aR1][$aC1]->IsMerged() ) {
            JpGraphError::RaiseL(27005,$aR1,$aC1,$aR2,$aC2);
            //("Cannot merge already merged cells in the range ($aR1,$aC1), ($aR2,$aC2)");
        }
        $this->iCells[$aR1][$aC1]->SetRowColSpan($rspan,$cspan);
        $this->iCells[$aR1][$aC1]->SetAlign($aHAlign,$aVAlign);
        for($i=$aR1; $i <= $aR2; ++$i) {
            for($j=$aC1; $j <= $aC2; ++$j) {
                if( ! ($i == $aR1 && $j == $aC1) ) {
                    if( $this->iCells[$i][$j]->IsMerged() ) {
                        JpGraphError::RaiseL(27005,$aR1,$aC1,$aR2,$aC2);
                        //("Cannot merge already merged cells in the range ($aR1,$aC1), ($aR2,$aC2)");
                    }
                    $this->iCells[$i][$j]->SetMerged($aR1,$aC1,true);
                }
            }
        }
    }


    /*---------------------------------------------------------------------
     * CSIM methods
     *---------------------------------------------------------------------
     */

    function SetCSIMTarget($aTarget,$aAlt=null,$aAutoTarget=false) {
        $m = $this->iSize[0];
        $n = $this->iSize[1];
        $csim = '';
        for($i=0; $i < $m; ++$i) {
            for($j=0; $j < $n; ++$j) {
                if( $aAutoTarget )
                $t = $aTarget."?row=$i&col=$j";
                else
                $t = $aTarget;
                $this->iCells[$i][$j]->SetCSIMTarget($t,$aAlt);
            }
        }
    }

    function SetCellCSIMTarget($aRow,$aCol,$aTarget,$aAlt=null) {
        $this->_chkR($aRow);
        $this->_chkC($aCol);
        $this->iCells[$aRow][$aCol]->SetCSIMTarget($aTarget,$aAlt);
    }

    /*---------------------------------------------------------------------
     * Private methods
     *---------------------------------------------------------------------
     */

    function GetCSIMAreas() {
        $m = $this->iSize[0];
        $n = $this->iSize[1];
        $csim = '';
        for($i=0; $i < $m; ++$i) {
            for($j=0; $j < $n; ++$j) {
                $csim .= $this->iCells[$i][$j]->GetCSIMArea();
            }
        }
        return $csim;
    }

    function _chkC($aCol) {
        if( ! $this->iInit ) {
            JpGraphError::Raise(27014); // Table not initialized
        }
        if( $aCol < 0 || $aCol >= $this->iSize[1] )
        JpGraphError::RaiseL(27006,$aCol);
        //("GTextTable:\nColumn argument ($aCol) is outside specified table size.");
    }

    function _chkR($aRow) {
        if( ! $this->iInit ) {
            JpGraphError::Raise(27014); // Table not initialized
        }
        if( $aRow < 0 || $aRow >= $this->iSize[0] )
        JpGraphError::RaiseL(27007,$aRow);
        //("GTextTable:\nRow argument ($aRow) is outside specified table size.");
    }

    function _getScalePos() {
        if( $this->iScaleXPos === null || $this->iScaleYPos === null ) {
            return false;
        }
        return array($this->iScaleXPos, $this->iScaleYPos);
    }

    function _autoSizeTable($aImg) {
        // Get maximum column width and row height
        $m = $this->iSize[0];
        $n = $this->iSize[1];
        $w=1;$h=1;

        // Get maximum row height per row
        for($i=0; $i < $m; ++$i) {
            $h=0;
            for($j=0; $j < $n; ++$j) {
                $h = max($h,$this->iCells[$i][$j]->GetHeight($aImg));
            }
            if( isset($this->iRowHeight[$i]) ) {
                $this->iRowHeight[$i]  = max($h,$this->iRowHeight[$i]);
            }
            else
            $this->iRowHeight[$i]  = $h;
        }

        // Get maximum col width per columns
        for($j=0; $j < $n; ++$j) {
            $w=0;
            for($i=0; $i < $m; ++$i) {
                $w = max($w,$this->iCells[$i][$j]->GetWidth($aImg));
            }
            if( isset($this->iColWidth[$j]) ) {
                $this->iColWidth[$j]  = max($w,$this->iColWidth[$j]);
            }
            else
            $this->iColWidth[$j]  = $w;
        }
    }

    function _setcell($aRow,$aCol,$aVal='') {
        if( isset($this->iCells[$aRow][$aCol]) ) {
            $this->iCells[$aRow][$aCol]->Set($aVal);
        }
        else {
            $this->iCells[$aRow][$aCol] = new GTextTableCell((string)$aVal,$aRow,$aCol);
            $this->iCells[$aRow][$aCol]->Init($this);
        }
    }

    function StrokeWithScale($aImg,$aXScale,$aYScale) {
        if( is_numeric($this->iScaleXPos) && is_numeric($this->iScaleYPos) ) {
            $x = round($aXScale->Translate($this->iScaleXPos));
            $y = round($aYScale->Translate($this->iScaleYPos));
            $this->Stroke($aImg,$x,$y);
        }
        else {
            $this->Stroke($aImg);
        }
    }

    function Stroke($aImg,$aX=NULL,$aY=NULL) {
        if( $aX !== NULL && $aY !== NULL ) {
            $this->iXPos = $aX;
            $this->iYPos = $aY;
        }

        $rc = $this->iSize[0]; // row count
        $cc = $this->iSize[1]; // column count

        if( $rc == 0 || $cc == 0 ) {
            JpGraphError::RaiseL(27009);
        }

        // Adjust margins of each cell based on the weight of the grid. Each table grid line
        // is actually occupying the left side and top part of each cell.
        for($j=0; $j < $cc; ++$j) {
            $this->iCells[0][$j]->iMarginTop += $this->iBorderWeight;
        }
        for($i=0; $i < $rc; ++$i) {
            $this->iCells[$i][0]->iMarginLeft += $this->iBorderWeight;
        }
        for($i=0; $i < $rc; ++$i) {
            for($j=0; $j < $cc; ++$j) {
                $this->iCells[$i][$j]->AdjustMarginsForGrid();
            }
        }

        // adjust row and column size depending on cell content
        $this->_autoSizeTable($aImg);

        if( $this->iSize[1] != count($this->iColWidth) || $this->iSize[0] != count($this->iRowHeight) ) {
            JpGraphError::RaiseL(27008);
            //('Column and row size arrays must match the dimesnions of the table');
        }

        // Find out overall table size
        $width=0;
        for($i=0; $i < $cc; ++$i) {
            $width += $this->iColWidth[$i];
        }
        $height=0;
        for($i=0; $i < $rc; ++$i) {
            $height += $this->iRowHeight[$i];
        }

        // Adjust the X,Y position to alway be at the top left corner
        // The anchor position, i.e. how the client want to interpret the specified
        // x and y coordinate must be taken into account
        switch( strtolower($this->iXAnchor) ) {
            case 'left' :
                break;
            case 'center':
                $this->iXPos -= round($width/2);
                break;
            case 'right':
                $this->iXPos -= $width;
                break;
        }
        switch( strtolower($this->iYAnchor) ) {
            case 'top' :
                break;
            case 'center':
            case 'middle':
                $this->iYPos -= round($height/2);
                break;
            case 'bottom':
                $this->iYPos -= $height;
                break;
        }

        // Set the overall background color of the table if set
        if( $this->iBGColor !== '' ) {
            $aImg->SetColor($this->iBGColor);
            $aImg->FilledRectangle($this->iXPos,$this->iYPos,$this->iXPos+$width,$this->iYPos+$height);
        }

        // Stroke all cells
        $rpos=$this->iYPos;
        for($i=0; $i < $rc; ++$i) {
            $cpos=$this->iXPos;
            for($j=0; $j < $cc; ++$j) {
                // Calculate width and height of this cell if it is spanning
                // more than one column or row
                $cwidth=0;
                for( $k=0; $k < $this->iCells[$i][$j]->iColSpan; ++$k ) {
                    $cwidth += $this->iColWidth[$j+$k];
                }
                $cheight=0;
                for( $k=0; $k < $this->iCells[$i][$j]->iRowSpan; ++$k ) {
                    $cheight += $this->iRowHeight[$i+$k];
                }

                $this->iCells[$i][$j]->Stroke($aImg,$cpos,$rpos,$cwidth,$cheight);
                $cpos += $this->iColWidth[$j];
            }
            $rpos += $this->iRowHeight[$i];
        }

        // Stroke outer border
        $aImg->SetColor($this->iBorderColor);
        if( $this->iBorderWeight == 1 )
        $aImg->Rectangle($this->iXPos,$this->iYPos,$this->iXPos+$width,$this->iYPos+$height);
        else {
            for( $i=0; $i < $this->iBorderWeight; ++$i )
            $aImg->Rectangle($this->iXPos+$i,$this->iYPos+$i,
            $this->iXPos+$width-1+$this->iBorderWeight-$i,
            $this->iYPos+$height-1+$this->iBorderWeight-$i);
        }
    }
}

/*
 EOF
 */
?>
