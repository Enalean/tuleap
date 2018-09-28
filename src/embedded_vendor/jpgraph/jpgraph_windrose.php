<?php
/*=======================================================================
 // File:        JPGRAPH_WINDROSE.PHP
 // Description: Windrose extension for JpGraph
 // Created:     2003-09-17
 // Ver:         $Id: jpgraph_windrose.php 1928 2010-01-11 19:56:51Z ljp $
 //
 // Copyright (c) Asial Corporation. All rights reserved.
 //========================================================================
 */

require_once('jpgraph_glayout_vh.inc.php');

//------------------------------------------------------------------------
// Determine how many compass directions to show
//------------------------------------------------------------------------
define('WINDROSE_TYPE4',1);
define('WINDROSE_TYPE8',2);
define('WINDROSE_TYPE16',3);
define('WINDROSE_TYPEFREE',4);

//------------------------------------------------------------------------
// How should the labels for the circular grids be aligned
//------------------------------------------------------------------------
define('LBLALIGN_CENTER',1);
define('LBLALIGN_TOP',2);

//------------------------------------------------------------------------
// How should the labels around the plot be align
//------------------------------------------------------------------------
define('LBLPOSITION_CENTER',1);
define('LBLPOSITION_EDGE',2);

//------------------------------------------------------------------------
// Interpretation of ordinal values in the data
//------------------------------------------------------------------------
define('KEYENCODING_CLOCKWISE',1);
define('KEYENCODING_ANTICLOCKWISE',2);

// Internal debug flag
define('__DEBUG',false);


//===================================================
// CLASS WindrosePlotScale
//===================================================
class WindrosePlotScale {
    private $iMax,$iDelta=5;
    private $iNumCirc=3;
    public $iMaxNum=0;
    private $iLblFmt='%.0f%%';
    public $iFontFamily=FF_VERDANA,$iFontStyle=FS_NORMAL,$iFontSize=10;
    public $iZFontFamily=FF_ARIAL,$iZFontStyle=FS_NORMAL,$iZFontSize=10;
    public $iFontColor='black',$iZFontColor='black';
    private $iFontFrameColor=false, $iFontBkgColor=false;
    private $iLblZeroTxt=null;
    private $iLblAlign=LBLALIGN_CENTER;
    public $iAngle='auto';
    private $iManualScale = false;
    private $iHideLabels = false;

    function __construct($aData) {
        $max=0;
        $totlegsum = 0;
        $maxnum=0;
        $this->iZeroSum=0;
        foreach( $aData as $idx => $legdata ) {
            $legsum = array_sum($legdata);
            $maxnum = max($maxnum,count($legdata)-1);
            $max = max($legsum-$legdata[0],$max);
            $totlegsum += $legsum;
            $this->iZeroSum += $legdata[0] ;
        }
        if( round($totlegsum) > 100 ) {
            JpGraphError::RaiseL(22001,$legsum);
            //("Total percentage for all windrose legs in a windrose plot can not exceed  100% !\n(Current max is: ".$legsum.')');
        }
        $this->iMax = $max ;
        $this->iMaxNum = $maxnum;
        $this->iNumCirc = $this->GetNumCirc();
        $this->iMaxVal = $this->iNumCirc * $this->iDelta ;
    }

    // Return number of grid circles
    function GetNumCirc() {
        // Never return less than 1 circles
        $num = ceil($this->iMax / $this->iDelta);
        return max(1,$num) ;
    }

    function SetMaxValue($aMax) {
        $this->iMax = $aMax;
        $this->iNumCirc = $this->GetNumCirc();
        $this->iMaxVal = $this->iNumCirc * $this->iDelta ;
    }

    // Set step size for circular grid
    function Set($aMax,$aDelta=null) {
        if( $aDelta==null ) {
            $this->SetMaxValue($aMax);
            return;
        }
        $this->iDelta = $aDelta;
        $this->iNumCirc = ceil($aMax/$aDelta); //$this->GetNumCirc();
        $this->iMaxVal = $this->iNumCirc * $this->iDelta ;
        $this->iMax=$aMax;
        // Remember that user has specified interval so don't
        // do autoscaling
        $this->iManualScale = true;
    }

    function AutoScale($aRadius,$aMinDist=30) {

        if( $this->iManualScale ) return;

        // Make sure distance (in pixels) between two circles
        // is never less than $aMinDist pixels
        $tst = ceil($aRadius / $this->iNumCirc) ;

        while( $tst <= $aMinDist && $this->iDelta < 100 ) {
            $this->iDelta += 5;
            $tst = ceil($aRadius / $this->GetNumCirc()) ;
        }

        if( $this->iDelta >= 100 ) {
            JpGraphError::RaiseL(22002);//('Graph is too small to have a scale. Please make the graph larger.');
        }

        // If the distance is to large try with multiples of 2 instead
        if( $tst > $aMinDist * 3 ) {
            $this->iDelta = 2;
            $tst = ceil($aRadius / $this->iNumCirc) ;

            while( $tst <= $aMinDist && $this->iDelta < 100 ) {
                $this->iDelta += 2;
                $tst = ceil($aRadius / $this->GetNumCirc()) ;
            }

            if( $this->iDelta >= 100 ) {
                JpGraphError::RaiseL(22002); //('Graph is too small to have a scale. Please make the graph larger.');
            }
        }

        $this->iNumCirc = $this->GetNumCirc();
        $this->iMaxVal = $this->iNumCirc * $this->iDelta ;
    }

    // Return max of all leg values
    function GetMax() {
        return $this->iMax;
    }

    function Hide($aFlg=true) {
        $this->iHideLabels = $aFlg;
    }

    function SetAngle($aAngle) {
        $this->iAngle = $aAngle ;
    }

    // Translate a Leg value to radius distance
    function RelTranslate($aVal,$r,$ri) {
        $tv = round($aVal/$this->iMaxVal*($r-$ri));
        return $tv ;
    }

    function SetLabelAlign($aAlign) {
        $this->iLblAlign = $aAlign ;
    }

    function SetLabelFormat($aFmt) {
        $this->iLblFmt = $aFmt ;
    }

    function SetLabelFillColor($aBkgColor,$aBorderColor=false) {

        $this->iFontBkgColor = $aBkgColor;
        if( $aBorderColor === false ) {
            $this->iFontFrameColor = $aBkgColor;
        }
        else {
            $this->iFontFrameColor = $aBorderColor;
        }
    }

    function SetFontColor($aColor) {
        $this->iFontColor = $aColor ;
        $this->iZFontColor = $aColor ;
    }

    function SetFont($aFontFamily,$aFontStyle=FS_NORMAL,$aFontSize=10) {
        $this->iFontFamily = $aFontFamily ;
        $this->iFontStyle = $aFontStyle ;
        $this->iFontSize = $aFontSize ;
        $this->SetZFont($aFontFamily,$aFontStyle,$aFontSize);
    }

    function SetZFont($aFontFamily,$aFontStyle=FS_NORMAL,$aFontSize=10) {
        $this->iZFontFamily = $aFontFamily ;
        $this->iZFontStyle = $aFontStyle ;
        $this->iZFontSize = $aFontSize ;
    }

    function SetZeroLabel($aTxt) {
        $this->iLblZeroTxt = $aTxt ;
    }

    function SetZFontColor($aColor) {
        $this->iZFontColor = $aColor ;
    }

    function StrokeLabels($aImg,$xc,$yc,$ri,$rr) {

        if( $this->iHideLabels ) return;

        // Setup some convinient vairables
        $a = $this->iAngle * M_PI/180.0;
        $n = $this->iNumCirc;
        $d = $this->iDelta;

        // Setup the font and font color
        $val = new Text();
        $val->SetFont($this->iFontFamily,$this->iFontStyle,$this->iFontSize);
        $val->SetColor($this->iFontColor);

        if( $this->iFontBkgColor !== false ) {
            $val->SetBox($this->iFontBkgColor,$this->iFontFrameColor);
        }

        // Position the labels relative to the radiant circles
        if( $this->iLblAlign == LBLALIGN_TOP ) {
            if( $a > 0 && $a <= M_PI/2 ) {
                $val->SetAlign('left','bottom');
            }
            elseif( $a > M_PI/2 && $a <= M_PI ) {
                $val->SetAlign('right','bottom');
            }
        }
        elseif( $this->iLblAlign == LBLALIGN_CENTER ) {
            $val->SetAlign('center','center');
        }

        // Stroke the labels close to each circle
        $v = $d ;
        $si = sin($a);
        $co = cos($a);
        for( $i=0; $i < $n; ++$i, $v += $d ) {
            $r = $ri + ($i+1) * $rr;
            $x = $xc + $co * $r;
            $y = $yc - $si * $r;
            $val->Set(sprintf($this->iLblFmt,$v));
            $val->Stroke($aImg,$x,$y);
        }

        // Print the text in the zero circle
        if( $this->iLblZeroTxt === null ) {
            $this->iLblZeroTxt = sprintf($this->iLblFmt,$this->iZeroSum);
        }
        else {
            $this->iLblZeroTxt = sprintf($this->iLblZeroTxt,$this->iZeroSum);
        }

        $val->Set($this->iLblZeroTxt);
        $val->SetAlign('center','center');
        $val->SetParagraphAlign('center');
        $val->SetColor($this->iZFontColor);
        $val->SetFont($this->iZFontFamily,$this->iZFontStyle,$this->iZFontSize);
        $val->Stroke($aImg,$xc,$yc);
    }
}

//===================================================
// CLASS LegendStyle
//===================================================
class LegendStyle {
    public $iLength = 40, $iMargin = 20 , $iBottomMargin=5;
    public $iCircleWeight=2,  $iCircleRadius = 18, $iCircleColor='black';
    public $iTxtFontFamily=FF_VERDANA,$iTxtFontStyle=FS_NORMAL,$iTxtFontSize=8;
    public $iLblFontFamily=FF_VERDANA,$iLblFontStyle=FS_NORMAL,$iLblFontSize=8;
    public $iCircleFontFamily=FF_VERDANA,$iCircleFontStyle=FS_NORMAL,$iCircleFontSize=8;
    public $iLblFontColor='black',$iTxtFontColor='black',$iCircleFontColor='black';
    public $iShow=true;
    public $iFormatString='%.1f';
    public $iTxtMargin=6, $iTxt='';
    public $iZCircleTxt='Calm';

    function SetFont($aFontFamily,$aFontStyle=FS_NORMAL,$aFontSize=10) {
        $this->iLblFontFamily = $aFontFamily ;
        $this->iLblFontStyle = $aFontStyle ;
        $this->iLblFontSize = $aFontSize ;
        $this->iTxtFontFamily = $aFontFamily ;
        $this->iTxtFontStyle = $aFontStyle ;
        $this->iTxtFontSize = $aFontSize ;
        $this->iCircleFontFamily = $aFontFamily ;
        $this->iCircleFontStyle = $aFontStyle ;
        $this->iCircleFontSize = $aFontSize ;
    }

    function SetLFont($aFontFamily,$aFontStyle=FS_NORMAL,$aFontSize=10) {
        $this->iLblFontFamily = $aFontFamily ;
        $this->iLblFontStyle = $aFontStyle ;
        $this->iLblFontSize = $aFontSize ;
    }

    function SetTFont($aFontFamily,$aFontStyle=FS_NORMAL,$aFontSize=10) {
        $this->iTxtFontFamily = $aFontFamily ;
        $this->iTxtFontStyle = $aFontStyle ;
        $this->iTxtFontSize = $aFontSize ;
    }

    function SetCFont($aFontFamily,$aFontStyle=FS_NORMAL,$aFontSize=10) {
        $this->iCircleFontFamily = $aFontFamily ;
        $this->iCircleFontStyle = $aFontStyle ;
        $this->iCircleFontSize = $aFontSize ;
    }


    function SetFontColor($aColor) {
        $this->iTxtFontColor = $aColor ;
        $this->iLblFontColor = $aColor ;
        $this->iCircleFontColor = $aColor ;
    }

    function SetTFontColor($aColor) {
        $this->iTxtFontColor = $aColor ;
    }

    function SetLFontColor($aColor) {
        $this->iLblFontColor = $aColor ;
    }

    function SetCFontColor($aColor) {
        $this->iCircleFontColor = $aColor ;
    }

    function SetCircleWeight($aWeight) {
        $this->iCircleWeight = $aWeight;
    }

    function SetCircleRadius($aRadius) {
        $this->iCircleRadius = $aRadius;
    }

    function SetCircleColor($aColor) {
        $this->iCircleColor = $aColor ;
    }

    function SetCircleText($aTxt) {
        $this->iZCircleTxt = $aTxt;
    }

    function SetMargin($aMarg,$aBottomMargin=5) {
        $this->iMargin=$aMarg;
        $this->iBottomMargin=$aBottomMargin;
    }

    function SetLength($aLength) {
        $this->iLength = $aLength ;
    }

    function Show($aFlg=true) {
        $this->iShow = $aFlg;
    }

    function Hide($aFlg=true) {
        $this->iShow = ! $aFlg;
    }

    function SetFormat($aFmt) {
        $this->iFormatString=$aFmt;
    }

    function SetText($aTxt) {
        $this->iTxt = $aTxt ;
    }

}

define('RANGE_OVERLAPPING',0);
define('RANGE_DISCRETE',1);

//===================================================
// CLASS WindrosePlot
//===================================================
class WindrosePlot {
    private $iAntiAlias=true;
    private $iData=array();
    public $iX=0.5,$iY=0.5;
    public $iSize=0.55;
    private $iGridColor1='gray',$iGridColor2='darkgreen';
    private $iRadialColorArray=array();
    private $iRadialWeightArray=array();
    private $iRadialStyleArray=array();
    private $iRanges = array(1,2,3,5,6,10,13.5,99.0);
    private $iRangeStyle = RANGE_OVERLAPPING ;
    public $iCenterSize=60;
    private $iType = WINDROSE_TYPE16;
    public $iFontFamily=FF_VERDANA,$iFontStyle=FS_NORMAL,$iFontSize=10;
    public $iFontColor='darkgray';
    private $iRadialGridStyle='longdashed';
    private $iAllDirectionLabels =  array('E','ENE','NE','NNE','N','NNW','NW','WNW','W','WSW','SW','SSW','S','SSE','SE','ESE');
    private $iStandardDirections = array();
    private $iCircGridWeight=3, $iRadialGridWeight=1;
    private $iLabelMargin=12;
    private $iLegweights = array(2,4,6,8,10,12,14,16,18,20);
    private $iLegColors = array('orange','black','blue','red','green','purple','navy','yellow','brown');
    private $iLabelFormatString='', $iLabels=array();
    private $iLabelPositioning = LBLPOSITION_EDGE;
    private $iColor='white';
    private $iShowBox=false, $iBoxColor='black',$iBoxWeight=1,$iBoxStyle='solid';
    private $iOrdinalEncoding=KEYENCODING_ANTICLOCKWISE;
    public $legend=null;

    function __construct($aData) {
        $this->iData = $aData;
        $this->legend = new LegendStyle();

        // Setup the scale
        $this->scale = new WindrosePlotScale($this->iData);

        // default label for free type i agle and a degree sign
        $this->iLabelFormatString = '%.1f'.SymChar::Get('degree');

        $delta = 2*M_PI/16;
        for( $i=0, $a=0; $i < 16; ++$i, $a += $delta ) {
            $this->iStandardDirections[$this->iAllDirectionLabels[$i]] = $a;
        }
    }

    // Dummy method to make window plots have the same signature as the
    // layout classes since windrose plots are "leaf" classes in the hierarchy
    function LayoutSize() {
        return 1;
    }

    function SetSize($aSize) {
        $this->iSize = $aSize;
    }

    function SetDataKeyEncoding($aEncoding) {
        $this->iOrdinalEncoding = $aEncoding;
    }

    function SetColor($aColor) {
        $this->iColor = $aColor;
    }

    function SetRadialColors($aColors) {
        $this->iRadialColorArray = $aColors;
    }

    function SetRadialWeights($aWeights) {
        $this->iRadialWeightArray = $aWeights;
    }

    function SetRadialStyles($aStyles) {
        $this->iRadialStyleArray = $aStyles;
    }

    function SetBox($aColor='black',$aWeight=1, $aStyle='solid', $aShow=true) {
        $this->iShowBox = $aShow ;
        $this->iBoxColor = $aColor ;
        $this->iBoxWeight = $aWeight ;
        $this->iBoxStyle = $aStyle;
    }

    function SetLabels($aLabels) {
        $this->iLabels = $aLabels ;
    }

    function SetLabelMargin($aMarg) {
        $this->iLabelMargin = $aMarg ;
    }

    function SetLabelFormat($aLblFormat) {
        $this->iLabelFormatString = $aLblFormat ;
    }

    function SetCompassLabels($aLabels) {
        if( count($aLabels) != 16 ) {
            JpgraphError::RaiseL(22004); //('Label specification for windrose directions must have 16 values (one for each compass direction).');
        }
        $this->iAllDirectionLabels = $aLabels ;

        $delta = 2*M_PI/16;
        for( $i=0, $a=0; $i < 16; ++$i, $a += $delta ) {
            $this->iStandardDirections[$this->iAllDirectionLabels[$i]] = $a;
        }

    }

    function SetCenterSize($aSize) {
        $this->iCenterSize = $aSize;
    }
    // Alias for SetCenterSize
    function SetZCircleSize($aSize) {
        $this->iCenterSize = $aSize;
    }

    function SetFont($aFFam,$aFStyle=FS_NORMAL,$aFSize=10) {
        $this->iFontFamily = $aFFam ;
        $this->iFontStyle = $aFStyle ;
        $this->iFontSize = $aFSize ;
    }

    function SetFontColor($aColor) {
        $this->iFontColor=$aColor;
    }

    function SetGridColor($aColor1,$aColor2) {
        $this->iGridColor1 = $aColor1;
        $this->iGridColor2 = $aColor2;
    }

    function SetGridWeight($aGrid1=1,$aGrid2=2) {
        $this->iCircGridWeight = $aGrid1 ;
        $this->iRadialGridWeight = $aGrid2 ;
    }

    function SetRadialGridStyle($aStyle) {
        $aStyle = strtolower($aStyle);
        if( !in_array($aStyle,array('solid','dotted','dashed','longdashed')) ) {
            JpGraphError::RaiseL(22005); //("Line style for radial lines must be on of ('solid','dotted','dashed','longdashed') ");
        }
        $this->iRadialGridStyle=$aStyle;
    }

    function SetRanges($aRanges) {
        $this->iRanges = $aRanges;
    }

    function SetRangeStyle($aStyle) {
        $this->iRangeStyle = $aStyle;
    }

    function SetRangeColors($aLegColors) {
        $this->iLegColors = $aLegColors;
    }

    function SetRangeWeights($aWeights) {
        $n=count($aWeights);
        for($i=0; $i< $n; ++$i ) {
            $aWeights[$i] = floor($aWeights[$i]/2);
        }
        $this->iLegweights = $aWeights;

    }

    function SetType($aType) {
        if( $aType < WINDROSE_TYPE4 || $aType > WINDROSE_TYPEFREE ) {
            JpGraphError::RaiseL(22006); //('Illegal windrose type specified.');
        }
        $this->iType = $aType;
    }

    // Alias for SetPos()
    function SetCenterPos($aX,$aY) {
        $this->iX = $aX;
        $this->iY = $aY;        
    }
    
    function SetPos($aX,$aY) {
        $this->iX = $aX;
        $this->iY = $aY;
    }

    function SetAntiAlias($aFlag) {
        $this->iAntiAlias = $aFlag ;
        if( ! $aFlag )
        $this->iCircGridWeight = 1;
    }

    function _ThickCircle($aImg,$aXC,$aYC,$aRad,$aWeight=2,$aColor) {

        $aImg->SetColor($aColor);
        $aRad *= 2 ;
        $aImg->Ellipse($aXC,$aYC,$aRad,$aRad);
        if( $aWeight > 1 ) {
            $aImg->Ellipse($aXC,$aYC,$aRad+1,$aRad+1);
            $aImg->Ellipse($aXC,$aYC,$aRad+2,$aRad+2);
            if( $aWeight > 2 ) {
                $aImg->Ellipse($aXC,$aYC,$aRad+3,$aRad+3);
                $aImg->Ellipse($aXC,$aYC,$aRad+3,$aRad+4);
                $aImg->Ellipse($aXC,$aYC,$aRad+4,$aRad+3);
            }
        }
    }

    function _StrokeWindLeg($aImg,$xc,$yc,$a,$ri,$r,$weight,$color) {

        // If less than 1 px long then we assume this has been caused by rounding problems
        // and should not be stroked
        if( $r < 1 ) return;

        $xt = $xc + cos($a)*$ri;
        $yt = $yc - sin($a)*$ri;
        $xxt = $xc + cos($a)*($ri+$r);
        $yyt = $yc - sin($a)*($ri+$r);

        $x1 = $xt - $weight*sin($a);
        $y1 = $yt - $weight*cos($a);
        $x2 = $xxt - $weight*sin($a);
        $y2 = $yyt - $weight*cos($a);

        $x3 = $xxt + $weight*sin($a);
        $y3 = $yyt + $weight*cos($a);
        $x4 = $xt + $weight*sin($a);
        $y4 = $yt + $weight*cos($a);

        $pts = array($x1,$y1,$x2,$y2,$x3,$y3,$x4,$y4);
        $aImg->SetColor($color);
        $aImg->FilledPolygon($pts);

    }

    function _StrokeLegend($aImg,$x,$y,$scaling=1,$aReturnWidth=false) {

        if( ! $this->legend->iShow ) return 0;

        $nlc = count($this->iLegColors);
        $nlw = count($this->iLegweights);

        // Setup font for ranges
        $value = new Text();
        $value->SetAlign('center','bottom');
        $value->SetFont($this->legend->iLblFontFamily,
        $this->legend->iLblFontStyle,
        $this->legend->iLblFontSize*$scaling);
        $value->SetColor($this->legend->iLblFontColor);

        // Remember x-center
        $xcenter = $x ;

        // Construct format string
        $fmt = $this->legend->iFormatString.'-'.$this->legend->iFormatString;

        // Make sure that the length of each range is enough to cover the
        // size of the labels
        $tst = sprintf($fmt,$this->iRanges[0],$this->iRanges[1]);
        $value->Set($tst);
        $w = $value->GetWidth($aImg);
        $l = round(max($this->legend->iLength * $scaling,$w*1.5));

        $r = $this->legend->iCircleRadius * $scaling ;
        $len = 2*$r + $this->scale->iMaxNum * $l;

        // We are called just to find out the width
        if( $aReturnWidth ) return $len;

        $x -= round($len/2);
        $x += $r;

        // 4 pixels extra vertical margin since the circle sometimes is +/- 1 pixel of the
        // theorethical radius due to imperfection in the GD library
        //$y -= round(max($r,$scaling*$this->iLegweights[($this->scale->iMaxNum-1) % $nlw])+4*$scaling);
        $y -= ($this->legend->iCircleRadius + 2)*$scaling+$this->legend->iBottomMargin*$scaling;

        // Adjust for bottom text
        if( $this->legend->iTxt != '' ) {
            // Setup font for text
            $value->Set($this->legend->iTxt);
            $y -= /*$this->legend->iTxtMargin + */ $value->GetHeight($aImg);
        }

        // Stroke 0-circle
        $this->_ThickCircle($aImg,$x,$y,$r,$this->legend->iCircleWeight,
        $this->legend->iCircleColor);

        // Remember the center of the circe
        $xc=$x; $yc=$y;

        $value->SetAlign('center','bottom');
        $x += $r+1;

        // Stroke all used ranges
        $txty = $y -
        round($this->iLegweights[($this->scale->iMaxNum-1)%$nlw]*$scaling) - 4*$scaling;
        if( $this->scale->iMaxNum >= count($this->iRanges) ) {
            JpGraphError::RaiseL(22007); //('To few values for the range legend.');
        }
        $i=0;$idx=0;
        while( $i < $this->scale->iMaxNum ) {
            $y1 = $y - round($this->iLegweights[$i % $nlw]*$scaling);
            $y2 = $y + round($this->iLegweights[$i % $nlw]*$scaling);
            $x2 = $x + $l ;
            $aImg->SetColor($this->iLegColors[$i % $nlc]);
            $aImg->FilledRectangle($x,$y1,$x2,$y2);
            if( $this->iRangeStyle == RANGE_OVERLAPPING ) {
                $lbl = sprintf($fmt,$this->iRanges[$idx],$this->iRanges[$idx+1]);
            }
            else {
                $lbl = sprintf($fmt,$this->iRanges[$idx],$this->iRanges[$idx+1]);
                ++$idx;
            }
            $value->Set($lbl);
            $value->Stroke($aImg,$x+$l/2,$txty);
            $x = $x2;
            ++$i;++$idx;
        }

        // Setup circle font
        $value->SetFont($this->legend->iCircleFontFamily,
        $this->legend->iCircleFontStyle,
        $this->legend->iCircleFontSize*$scaling);
        $value->SetColor($this->legend->iCircleFontColor);

        // Stroke 0-circle text
        $value->Set($this->legend->iZCircleTxt);
        $value->SetAlign('center','center');
        $value->ParagraphAlign('center');
        $value->Stroke($aImg,$xc,$yc);

        // Setup circle font
        $value->SetFont($this->legend->iTxtFontFamily,
        $this->legend->iTxtFontStyle,
        $this->legend->iTxtFontSize*$scaling);
        $value->SetColor($this->legend->iTxtFontColor);

        // Draw the text under the legend
        $value->Set($this->legend->iTxt);
        $value->SetAlign('center','top');
        $value->SetParagraphAlign('center');
        $value->Stroke($aImg,$xcenter,$y2+$this->legend->iTxtMargin*$scaling);
    }

    function SetAutoScaleAngle($aIsRegRose=true) {

        // If the user already has manually set an angle don't
        // trye to find a position
        if( is_numeric($this->scale->iAngle) )
            return;

        if( $aIsRegRose ) {

            // Create a complete data for all directions
            // and translate string directions to ordinal values.
            // This will much simplify the logic below
            for( $i=0; $i < 16; ++$i ) {
                $dtxt = $this->iAllDirectionLabels[$i];
                if( !empty($this->iData[$dtxt]) ) {
                    $data[$i] = $this->iData[$dtxt];
                }
                elseif( !empty($this->iData[strtolower($dtxt)]) ) {
                    $data[$i] = $this->iData[strtolower($dtxt)];
                }
                elseif( !empty($this->iData[$i]) ) {
                    $data[$i] = $this->iData[$i];
                }
                else {
                    $data[$i] = array();
                }
            }

            // Find the leg which has the lowest weighted sum of number of data around it
            $c0 = array_sum($data[0]);
            $c1 = array_sum($data[1]);
            $found = 1;
            $min = $c0+$c1*100; // Initialize to a high value
            for( $i=1; $i < 15; ++$i ) {
                $c2 = array_sum($data[$i+1]);

                // Weight the leg we will use more to give preference
                // to a short middle leg even if the 3 way sum is similair
                $w = $c0 + 3*$c1 + $c2 ;
                if( $w < $min ) {
                    $min = $w;
                    $found = $i;
                }
                $c0 = $c1;
                $c1 = $c2;
            }
            $this->scale->iAngle = $found*22.5;
        }
        else {
            $n = count($this->iData);
            foreach( $this->iData as $dir => $leg ) {
                if( !is_numeric($dir) ) {
                    $pos = array_search(strtoupper($dir),$this->iAllDirectionLabels);
                    if( $pos !== false ) {
                        $dir = $pos*22.5;
                    }
                }
                $data[round($dir)] = $leg;
            }

            // Get all the angles for the data and sort it
            $keys = array_keys($data);
            sort($keys, SORT_NUMERIC);

            $n = count($data);
            $found = false;
            $max = 0 ;
            for( $i=0; $i < 15; ++$i ) {
                $try_a = round(22.5*$i);

                if( $try_a > $keys[$n-1] ) break;

                if( in_array($try_a,$keys) ) continue;

                // Find the angle just lower than this
                $j=0;
                while( $j < $n && $keys[$j] <= $try_a ) ++$j;
                if( $j == 0 ) {
                    $kj = 0; $keys[$n-1];
                    $d1 = 0; abs($kj-$try_a);
                }
                else {
                    --$j;
                    $kj = $keys[$j];
                    $d1 = abs($kj-$try_a);
                }

                // Find the angle just larger than this
                $l=$n-1;
                while( $l >= 0 && $keys[$l] >= $try_a ) --$l;
                if( $l == $n-1) {
                    $kl = $keys[0];
                    $d2 = abs($kl-$try_a);
                }
                else {
                    ++$l;
                    $kl = $keys[$l];
                    $d2 = abs($kl-$try_a);
                }

                // Weight the distance so that legs with large spread
                // gets a better weight
                $w = $d1 + $d2;
                if( $i == 0 ) {
                    $w = round(1.4 * $w);
                }
                $diff = abs($d1 - $d2);
                $w *= (360-$diff);
                if( $w > $max ) {
                    $found = $i;
                    $max = $w;
                }
            }

            $a = $found*22.5;

            // Some heuristics to have some preferred positions
            if( $keys[$n-1] < 25 ) $a = 45;
            elseif( $keys[0] > 60 ) $a = 45;
            elseif( $keys[0] > 25 && $keys[$n-1] < 340 ) $a = 0;
            elseif( $keys[$n-1] < 75 ) $a = 90;
            elseif( $keys[$n-1] < 120 ) $a = 135;
            elseif( $keys[$n-1] < 160 ) $a = 180;

            $this->scale->iAngle = $a ;
        }
    }

    function NormAngle($a) {
        while( $a > 360 ) {
            $a -= 360;
        }
        return $a;
    }

    function SetLabelPosition($aPos) {
        $this->iLabelPositioning  = $aPos ;
    }

    function _StrokeFreeRose($dblImg,$value,$scaling,$xc,$yc,$r,$ri) {

        // Plot radial grid lines and remember the end position
        // and the angle for later use when plotting the labels
        if( $this->iType != WINDROSE_TYPEFREE ) {
            JpGraphError::RaiseL(22008); //('Internal error: Trying to plot free Windrose even though type is not a free windorose');
        }

        // Check if we should auto-position the angle for the
        // labels. Basically we try to find a firection with smallest
        // (or none) data.
        $this->SetAutoScaleAngle(false);

        $nlc = count($this->iLegColors);
        $nlw = count($this->iLegweights);

        // Stroke grid lines for directions and remember the
        // position for the labels
        $txtpos=array();
        $num = count($this->iData);

        $keys = array_keys($this->iData);

        foreach( $this->iData as $dir => $legdata ) {
            if( in_array($dir,$this->iAllDirectionLabels,true) === true) {
                $a = $this->iStandardDirections[strtoupper($dir)];
                if( in_array($a*180/M_PI,$keys) ) {
                    JpGraphError::RaiseL(22009,round($a*180/M_PI));
                    //('You have specified the same direction twice, once with an angle and once with a compass direction ('.$a*180/M_PI.' degrees.)');
                }
            }
            elseif( is_numeric($dir) ) {
                $this->NormAngle($dir);

                if( $this->iOrdinalEncoding == KEYENCODING_CLOCKWISE ) {
                    $dir = 360-$dir;
                }

                $a = $dir * M_PI/180;
            }
            else {
                JpGraphError::RaiseL(22010);//('Direction must either be a numeric value or one of the 16 compass directions');
            }

            $xxc = round($xc + cos($a)*$ri);
            $yyc = round($yc - sin($a)*$ri);
            $x = round($xc + cos($a)*$r);
            $y = round($yc - sin($a)*$r);
            if( empty($this->iRadialColorArray[$dir]) ) {
                $dblImg->SetColor($this->iGridColor2);
            }
            else {
                $dblImg->SetColor($this->iRadialColorArray[$dir]);
            }
            if( empty($this->iRadialWeightArray[$dir]) ) {
                $dblImg->SetLineWeight($this->iRadialGridWeight);
            }
            else {
                $dblImg->SetLineWeight($this->iRadialWeightArray[$dir]);
            }
            if( empty($this->iRadialStyleArray[$dir]) ) {
                $dblImg->SetLineStyle($this->iRadialGridStyle);
            }
            else {
                $dblImg->SetLineStyle($this->iRadialStyleArray[$dir]);
            }
            $dblImg->StyleLine($xxc,$yyc,$x,$y);
            $txtpos[] = array($x,$y,$a);
        }
        $dblImg->SetLineWeight(1);

        // Setup labels
        $lr = $scaling * $this->iLabelMargin;

        if( $this->iLabelPositioning == LBLPOSITION_EDGE ) {
            $value->SetAlign('left','top');
        }
        else {
            $value->SetAlign('center','center');
            $value->SetMargin(0);
        }

        for($i=0; $i < $num; ++$i ) {

            list($x,$y,$a) = $txtpos[$i];

            // Determine the label

            $da = $a*180/M_PI;
            if( $this->iOrdinalEncoding == KEYENCODING_CLOCKWISE ) {
                $da = 360 - $da;
            }

            //$da = 360-$da;
            
            if( !empty($this->iLabels[$keys[$i]]) ) {
                $lbl = $this->iLabels[$keys[$i]];
            }
            else {
                $lbl = sprintf($this->iLabelFormatString,$da);
            }

            if( $this->iLabelPositioning == LBLPOSITION_CENTER ) {
                $dx = $dy = 0;
            }
            else {
                // LBLPOSIITON_EDGE
                if( $a>=7*M_PI/4 || $a <= M_PI/4 ) $dx=0;
                if( $a>=M_PI/4 && $a <= 3*M_PI/4 ) $dx=($a-M_PI/4)*2/M_PI;
                if( $a>=3*M_PI/4 && $a <= 5*M_PI/4 ) $dx=1;
                if( $a>=5*M_PI/4 && $a <= 7*M_PI/4 ) $dx=(1-($a-M_PI*5/4)*2/M_PI);

                if( $a>=7*M_PI/4 ) $dy=(($a-M_PI)-3*M_PI/4)*2/M_PI;
                if( $a<=M_PI/4 ) $dy=(0.5+$a*2/M_PI);
                if( $a>=M_PI/4 && $a <= 3*M_PI/4 ) $dy=1;
                if( $a>=3*M_PI/4 && $a <= 5*M_PI/4 ) $dy=(1-($a-3*M_PI/4)*2/M_PI);
                if( $a>=5*M_PI/4 && $a <= 7*M_PI/4 ) $dy=0;
            }

            $value->Set($lbl);
            $th = $value->GetHeight($dblImg);
            $tw = $value->GetWidth($dblImg);
            $xt=round($lr*cos($a)+$x) - $dx*$tw;
            $yt=round($y-$lr*sin($a)) - $dy*$th;

            $value->Stroke($dblImg,$xt,$yt);
        }

        if( __DEBUG ) {
            $dblImg->SetColor('red');
            $dblImg->Circle($xc,$yc,$lr+$r);
        }

        // Stroke all the legs
        reset($this->iData);
        $i=0;
        foreach($this->iData as $dir => $legdata) {
            $legdata = array_slice($legdata,1);
            $nn = count($legdata);

            $a = $txtpos[$i][2];
            $rri = $ri/$scaling;
            for( $j=0; $j < $nn; ++$j ) {
                // We want the non scaled original radius
                $legr = $this->scale->RelTranslate($legdata[$j],$r/$scaling,$ri/$scaling) ;
                $this->_StrokeWindLeg($dblImg, $xc, $yc, $a,
                $rri *$scaling,
                $legr *$scaling,
                $this->iLegweights[$j % $nlw] * $scaling,
                $this->iLegColors[$j % $nlc]);
                $rri += $legr;
            }
            ++$i;
        }
    }

    // Translate potential string specified compass labels to their
    // corresponding index.
    function FixupIndexes($aDataArray,$num) {
        $ret = array();
        $keys = array_keys($aDataArray);
        foreach($aDataArray as $idx => $data) {
            if( is_string($idx) ) {
                $idx = strtoupper($idx);
                $res = array_search($idx,$this->iAllDirectionLabels);
                if( $res === false ) {
                    JpGraphError::RaiseL(22011,$idx); //('Windrose index must be numeric or direction label. You have specified index='.$idx);
                }
                $idx = $res;
                if( $idx % (16 / $num) !== 0 ) {
                    JpGraphError::RaiseL(22012); //('Windrose radial axis specification contains a direction which is not enabled.');
                }
                $idx /= (16/$num) ;

                if( in_array($idx,$keys,1) ) {
                    JpgraphError::RaiseL(22013,$idx); //('You have specified the look&feel for the same compass direction twice, once with text and once with index (Index='.$idx.')');
                }
            }
            if( $idx < 0 || $idx > 15 ) {
                JpgraphError::RaiseL(22014); //('Index for copmass direction must be between 0 and 15.');
            }
            $ret[$idx] = $data;
        }
        return $ret;
    }

    function _StrokeRegularRose($dblImg,$value,$scaling,$xc,$yc,$r,$ri) {
        // _StrokeRegularRose($dblImg,$xc,$yc,$r,$ri)
        // Plot radial grid lines and remember the end position
        // and the angle for later use when plotting the labels
        switch( $this->iType ) {
            case WINDROSE_TYPE4:
                $num = 4; break;
            case WINDROSE_TYPE8:
                $num = 8; break;
            case WINDROSE_TYPE16:
                $num = 16; break;
            default:
                JpGraphError::RaiseL(22015);//('You have specified an undefined Windrose plot type.');
        }

        // Check if we should auto-position the angle for the
        // labels. Basically we try to find a firection with smallest
        // (or none) data.
        $this->SetAutoScaleAngle(true);

        $nlc = count($this->iLegColors);
        $nlw = count($this->iLegweights);

        $this->iRadialColorArray = $this->FixupIndexes($this->iRadialColorArray,$num);
        $this->iRadialWeightArray = $this->FixupIndexes($this->iRadialWeightArray,$num);
        $this->iRadialStyleArray = $this->FixupIndexes($this->iRadialStyleArray,$num);

        $txtpos=array();
        $a = 2*M_PI/$num;
        $dblImg->SetColor($this->iGridColor2);
        $dblImg->SetLineStyle($this->iRadialGridStyle);
        $dblImg->SetLineWeight($this->iRadialGridWeight);

        // Translate any name specified directions to the index
        // so we can easily use it in the loop below
        for($i=0; $i < $num; ++$i ) {
            $xxc = round($xc + cos($a*$i)*$ri);
            $yyc = round($yc - sin($a*$i)*$ri);
            $x = round($xc + cos($a*$i)*$r);
            $y = round($yc - sin($a*$i)*$r);
            if( empty($this->iRadialColorArray[$i]) ) {
                $dblImg->SetColor($this->iGridColor2);
            }
            else {
                $dblImg->SetColor($this->iRadialColorArray[$i]);
            }
            if( empty($this->iRadialWeightArray[$i]) ) {
                $dblImg->SetLineWeight($this->iRadialGridWeight);
            }
            else {
                $dblImg->SetLineWeight($this->iRadialWeightArray[$i]);
            }
            if( empty($this->iRadialStyleArray[$i]) ) {
                $dblImg->SetLineStyle($this->iRadialGridStyle);
            }
            else {
                $dblImg->SetLineStyle($this->iRadialStyleArray[$i]);
            }

            $dblImg->StyleLine($xxc,$yyc,$x,$y);
            $txtpos[] = array($x,$y,$a*$i);
        }
        $dblImg->SetLineWeight(1);

        $lr = $scaling * $this->iLabelMargin;
        if( $this->iLabelPositioning == LBLPOSITION_CENTER ) {
            $value->SetAlign('center','center');
        }
        else {
            $value->SetAlign('left','top');
            $value->SetMargin(0);
            $lr /= 2 ;
        }

        for($i=0; $i < $num; ++$i ) {
            list($x,$y,$a) = $txtpos[$i];

            // Set the position of the label
            if( $this->iLabelPositioning == LBLPOSITION_CENTER ) {
                $dx = $dy = 0;
            }
            else {
                // LBLPOSIITON_EDGE
                if( $a>=7*M_PI/4 || $a <= M_PI/4 ) $dx=0;
                if( $a>=M_PI/4 && $a <= 3*M_PI/4 ) $dx=($a-M_PI/4)*2/M_PI;
                if( $a>=3*M_PI/4 && $a <= 5*M_PI/4 ) $dx=1;
                if( $a>=5*M_PI/4 && $a <= 7*M_PI/4 ) $dx=(1-($a-M_PI*5/4)*2/M_PI);

                if( $a>=7*M_PI/4 ) $dy=(($a-M_PI)-3*M_PI/4)*2/M_PI;
                if( $a<=M_PI/4 ) $dy=(0.5+$a*2/M_PI);
                if( $a>=M_PI/4 && $a <= 3*M_PI/4 ) $dy=1;
                if( $a>=3*M_PI/4 && $a <= 5*M_PI/4 ) $dy=(1-($a-3*M_PI/4)*2/M_PI);
                if( $a>=5*M_PI/4 && $a <= 7*M_PI/4 ) $dy=0;
            }

            $value->Set($this->iAllDirectionLabels[$i*(16/$num)]);
            $th = $value->GetHeight($dblImg);
            $tw = $value->GetWidth($dblImg);
            $xt=round($lr*cos($a)+$x) - $dx*$tw;
            $yt=round($y-$lr*sin($a)) - $dy*$th;

            $value->Stroke($dblImg,$xt,$yt);
        }

        if( __DEBUG ) {
            $dblImg->SetColor("red");
            $dblImg->Circle($xc,$yc,$lr+$r);
        }

        // Stroke all the legs
        reset($this->iData);
        $keys = array_keys($this->iData);
        foreach($this->iData as $idx => $legdata) {
            $legdata = array_slice($legdata,1);
            $nn = count($legdata);
            if( is_string($idx) ) {
                $idx = strtoupper($idx);
                $idx = array_search($idx,$this->iAllDirectionLabels);
                if( $idx === false ) {
                    JpGraphError::RaiseL(22016);//('Windrose leg index must be numeric or direction label.');
                }
                if( $idx % (16 / $num) !== 0 ) {
                    JpGraphError::RaiseL(22017);//('Windrose data contains a direction which is not enabled. Please adjust what labels are displayed.');
                }
                $idx /= (16/$num) ;

                if( in_array($idx,$keys,1) ) {
                    JpgraphError::RaiseL(22018,$idx);//('You have specified data for the same compass direction twice, once with text and once with index (Index='.$idx.')');

                }
            }
            if( $idx < 0 || $idx > 15 ) {
                JpgraphError::RaiseL(22019);//('Index for direction must be between 0 and 15. You can\'t specify angles for a Regular Windplot, only index and compass directions.');
            }
            $a = $idx * (360 / $num) ;
            $a *= M_PI/180.0;
            $rri = $ri/$scaling;
            for( $j=0; $j < $nn; ++$j ) {
                // We want the non scaled original radius
                $legr = $this->scale->RelTranslate($legdata[$j], $r/$scaling,$ri/$scaling) ;
                $this->_StrokeWindLeg($dblImg, $xc, $yc, $a,
                $rri *$scaling,
                $legr *$scaling,
                $this->iLegweights[$j % $nlw] * $scaling,
                $this->iLegColors[$j % $nlc]);
                $rri += $legr;
            }
        }
    }


    function getWidth($aImg) {

        $scaling = 1;//$this->iAntiAlias ? 2 : 1 ;
       	if( $this->iSize > 0 && $this->iSize < 1 ) {
			$this->iSize *= min($aImg->width,$aImg->height);
       	}


        $value = new Text();
        $value->SetFont($this->iFontFamily,$this->iFontStyle,$this->iFontSize*$scaling);
        $value->SetColor($this->iFontColor);
        // Setup extra size around the graph needed so that the labels
        // doesn't get cut. For this we need to find the largest label.
        // The code below gives a possible a little to large margin. The
        // really, really proper way would be to account for what angle
        // the label are at
        $n = count($this->iLabels);
        if( $n > 0 ) {
            $maxh=0;$maxw=0;
            foreach($this->iLabels as $key => $lbl) {
                $value->Set($lbl);
                $maxw = max($maxw,$value->GetWidth($aImg));
            }
        }
        else {
            $value->Set('888.888'); // Dummy value to get width/height
            $maxw = $value->GetWidth($aImg);
        }
        // Add an extra margin of 50% the font size
        $maxw += round($this->iFontSize*$scaling * 0.4) ;

        $valxmarg = 1.5*$maxw+2*$this->iLabelMargin*$scaling;
        $w = round($this->iSize*$scaling + $valxmarg);

        // Make sure that the width of the legend fits
        $legendwidth = $this->_StrokeLegend($aImg,0,0,$scaling,true)+10*$scaling;
        $w = max($w,$legendwidth);

        return $w;
    }

    function getHeight($aImg) {

        $scaling = 1;//$this->iAntiAlias ? 2 : 1 ;
       	if( $this->iSize > 0 && $this->iSize < 1 ) {
			$this->iSize *= min($aImg->width,$aImg->height);
       	}

        $value = new Text();
        $value->SetFont($this->iFontFamily,$this->iFontStyle,$this->iFontSize*$scaling);
        $value->SetColor($this->iFontColor);
        // Setup extra size around the graph needed so that the labels
        // doesn't get cut. For this we need to find the largest label.
        // The code below gives a possible a little to large margin. The
        // really, really proper way would be to account for what angle
        // the label are at
        $n = count($this->iLabels);
        if( $n > 0 ) {
            $maxh=0;$maxw=0;
            foreach($this->iLabels as $key => $lbl) {
                $value->Set($lbl);
                $maxh = max($maxh,$value->GetHeight($aImg));
            }
        }
        else {
            $value->Set('180.8'); // Dummy value to get width/height
            $maxh = $value->GetHeight($aImg);
        }
        // Add an extra margin of 50% the font size
        //$maxh += round($this->iFontSize*$scaling * 0.5) ;
        $valymarg = 2*$maxh+2*$this->iLabelMargin*$scaling;

        $legendheight = round($this->legend->iShow ? 1 : 0);
        $legendheight *= max($this->legend->iCircleRadius*2,$this->legend->iTxtFontSize*2)+
        				 $this->legend->iMargin + $this->legend->iBottomMargin + 2;
        $legendheight *= $scaling;
        $h = round($this->iSize*$scaling + $valymarg) + $legendheight ;

        return $h;
    }

    function Stroke($aGraph) {

		$aImg = $aGraph->img;

		if( $this->iX > 0 && $this->iX < 1 ) {
			$this->iX = round( $aImg->width * $this->iX ) ;
		}

       	if( $this->iY > 0 && $this->iY < 1 ) {
       		$this->iY = round( $aImg->height * $this->iY ) ;
       	}

       	if( $this->iSize > 0 && $this->iSize < 1 ) {
			$this->iSize *= min($aImg->width,$aImg->height);
       	}

       	if( $this->iCenterSize > 0 && $this->iCenterSize < 1 ) {
			$this->iCenterSize *= $this->iSize;
       	}

        $this->scale->AutoScale(($this->iSize - $this->iCenterSize)/2, round(2.5*$this->scale->iFontSize));

        $scaling = $this->iAntiAlias ? 2 : 1 ;

        $value = new Text();
        $value->SetFont($this->iFontFamily,$this->iFontStyle,$this->iFontSize*$scaling);
        $value->SetColor($this->iFontColor);

        $legendheight = round($this->legend->iShow ? 1 : 0);
        $legendheight *= max($this->legend->iCircleRadius*2,$this->legend->iTxtFontSize*2)+
        $this->legend->iMargin + $this->legend->iBottomMargin + 2;
        $legendheight *= $scaling;

        $w = $scaling*$this->getWidth($aImg);
        $h = $scaling*$this->getHeight($aImg);

        // Copy back the double buffered image to the proper canvas
        $ww = $w / $scaling ;
        $hh = $h / $scaling ;

        // Create the double buffer
        if( $this->iAntiAlias ) {
            $dblImg = new RotImage($w,$h);
            // Set the background color
            $dblImg->SetColor($this->iColor);
            $dblImg->FilledRectangle(0,0,$w,$h);
        }
        else {
            $dblImg = $aImg ;
            // Make sure the ix and it coordinates correpond to the new top left center
            $dblImg->SetTranslation($this->iX-$w/2, $this->iY-$h/2);
        }

        if( __DEBUG ) {
            $dblImg->SetColor('red');
            $dblImg->Rectangle(0,0,$w-1,$h-1);
        }

        $dblImg->SetColor('black');

        if( $this->iShowBox ) {
            $dblImg->SetColor($this->iBoxColor);
            $old = $dblImg->SetLineWeight($this->iBoxWeight);
            $dblImg->SetLineStyle($this->iBoxStyle);
            $dblImg->Rectangle(0,0,$w-1,$h-1);
            $dblImg->SetLineWeight($old);
        }

        $xc = round($w/2);
        $yc = round(($h-$legendheight)/2);

        if( __DEBUG ) {
            $dblImg->SetColor('red');
            $old = $dblImg->SetLineWeight(2);
            $dblImg->Line($xc-5,$yc-5,$xc+5,$yc+5);
			$dblImg->Line($xc+5,$yc-5,$xc-5,$yc+5);
			$dblImg->SetLineWeight($old);
        }

        $this->iSize *= $scaling;

        // Inner circle size
        $ri = $this->iCenterSize/2 ;

        // Full circle radius
        $r = round( $this->iSize/2 );

        // Get number of grid circles
        $n = $this->scale->GetNumCirc();

        // Plot circle grids
        $ri *= $scaling ;
        $rr = round(($r-$ri)/$n);
        for( $i = 1; $i <= $n; ++$i ) {
            $this->_ThickCircle($dblImg,$xc,$yc,$rr*$i+$ri,
            $this->iCircGridWeight,$this->iGridColor1);
        }

        $num = 0 ;

        if( $this->iType == WINDROSE_TYPEFREE ) {
            $this->_StrokeFreeRose($dblImg,$value,$scaling,$xc,$yc,$r,$ri);
        }
        else {
            // Check if we need to re-code the interpretation of the ordinal
            // number in the data. Internally ordinal value 0 is East and then
            // counted anti-clockwise. The user might choose an encoding
            // that have 0 being the first axis to the right of the "N" axis and then
            // counted clock-wise
            if( $this->iOrdinalEncoding == KEYENCODING_CLOCKWISE ) {
                if( $this->iType == WINDROSE_TYPE16 ) {
                    $const1 = 19; $const2 = 16;
                }
                elseif( $this->iType == WINDROSE_TYPE8 ) {
                    $const1 = 9; $const2 = 8;
                }
                else {
                    $const1 = 4; $const2 = 4;
                }
                $tmp = array();
                $n=count($this->iData);
                foreach( $this->iData as $key => $val ) {
                    if( is_numeric($key) ) {
                        $key = ($const1 - $key) % $const2 ;
                    }
                    $tmp[$key] = $val;
                }
                $this->iData = $tmp;
            }
            $this->_StrokeRegularRose($dblImg,$value,$scaling,$xc,$yc,$r,$ri);
        }

        // Stroke the labels
        $this->scale->iFontSize *= $scaling;
        $this->scale->iZFontSize *= $scaling;
        $this->scale->StrokeLabels($dblImg,$xc,$yc,$ri,$rr);

        // Stroke the inner circle again since the legs
        // might have written over it
        $this->_ThickCircle($dblImg,$xc,$yc,$ri,$this->iCircGridWeight,$this->iGridColor1);

        if( $ww > $aImg->width ) {
            JpgraphError::RaiseL(22020);
            //('Windrose plot is too large to fit the specified Graph size. Please use WindrosePlot::SetSize() to make the plot smaller or increase the size of the Graph in the initial WindroseGraph() call.');
        }

        $x = $xc;
        $y = $h;
        $this->_StrokeLegend($dblImg,$x,$y,$scaling);

        if( $this->iAntiAlias ) {
            $aImg->Copy($dblImg->img, $this->iX-$ww/2, $this->iY-$hh/2, 0, 0, $ww,$hh, $w,$h);
        }

        // We need to restore the translation matrix
        $aImg->SetTranslation(0,0);

    }

}

//============================================================
// CLASS WindroseGraph
//============================================================
class WindroseGraph extends Graph {
    private $posx, $posy;
    public $plots=array();

    function __construct($width=300,$height=200,$cachedName="",$timeout=0,$inline=1) {
        parent::__construct($width,$height,$cachedName,$timeout,$inline);
        $this->posx=$width/2;
        $this->posy=$height/2;
        $this->SetColor('white');
        $this->title->SetFont(FF_VERDANA,FS_NORMAL,12);
        $this->title->SetMargin(8);
        $this->subtitle->SetFont(FF_VERDANA,FS_NORMAL,10);
        $this->subtitle->SetMargin(0);
        $this->subsubtitle->SetFont(FF_VERDANA,FS_NORMAL,8);
        $this->subsubtitle->SetMargin(0);
    }

    function StrokeTexts() {
        if( $this->texts != null ) {
            $n = count($this->texts);
            for($i=0; $i < $n; ++$i ) {
                $this->texts[$i]->Stroke($this->img);
            }
        }
    }

    function StrokeIcons() {
        if( $this->iIcons != null ) {
            $n = count($this->iIcons);
            for( $i=0; $i < $n; ++$i ) {
                // Since Windrose graphs doesn't have any linear scale the position of
                // each icon has to be given as absolute coordinates
                $this->iIcons[$i]->_Stroke($this->img);
            }
        }
    }

    //---------------
    // PUBLIC METHODS
    function Add($aObj) {
        if( is_array($aObj) && count($aObj) > 0 ) {
            $cl = $aObj[0];
        }
        else {
            $cl = $aObj;
        }
        if( $cl instanceof Text ) {
            $this->AddText($aObj);
        }
        elseif( $cl instanceof IconPlot ) {
            $this->AddIcon($aObj);
        }
        elseif( ($cl instanceof WindrosePlot) || ($cl instanceof LayoutRect) || ($cl instanceof LayoutHor)) {
            $this->plots[] = $aObj;
        }
        else {
            JpgraphError::RaiseL(22021);
        }
    }

    function AddText($aTxt,$aToY2=false) {
        parent::AddText($aTxt);
    }

    function SetColor($c) {
        $this->SetMarginColor($c);
    }

    // Method description
    function Stroke($aStrokeFileName="") {

        // If the filename is the predefined value = '_csim_special_'
        // we assume that the call to stroke only needs to do enough
        // to correctly generate the CSIM maps.
        // We use this variable to skip things we don't strictly need
        // to do to generate the image map to improve performance
        // as best we can. Therefore you will see a lot of tests !$_csim in the
        // code below.
        $_csim = ($aStrokeFileName===_CSIM_SPECIALFILE);

        // We need to know if we have stroked the plot in the
        // GetCSIMareas. Otherwise the CSIM hasn't been generated
        // and in the case of GetCSIM called before stroke to generate
        // CSIM without storing an image to disk GetCSIM must call Stroke.
        $this->iHasStroked = true;

        if( $this->background_image != "" || $this->background_cflag != "" ) {
            $this->StrokeFrameBackground();
        }
        else {
            $this->StrokeFrame();
        }

        // n holds number of plots
        $n = count($this->plots);
        for($i=0; $i < $n ; ++$i) {
     		$this->plots[$i]->Stroke($this);
        }

        $this->footer->Stroke($this->img);
        $this->StrokeIcons();
        $this->StrokeTexts();
        $this->StrokeTitles();

        // If the filename is given as the special "__handle"
        // then the image handler is returned and the image is NOT
        // streamed back
        if( $aStrokeFileName == _IMG_HANDLER ) {
            return $this->img->img;
        }
        else {
            // Finally stream the generated picture
            $this->cache->PutAndStream($this->img,$this->cache_name,$this->inline,
            $aStrokeFileName);
        }
    }

} // Class

?>
