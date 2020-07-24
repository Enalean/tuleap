<?php
//=======================================================================
// File:        JPGRAPH_RGB.INC.PHP
// Description: Class to handle RGb color space specification and
//              named colors
// Created:     2001-01-08 (Refactored to separate file 2008-08-01)
// Ver:         $Id: jpgraph_rgb.inc.php 1893 2009-10-02 23:15:25Z ljp $
//
// Copyright (c) Asial Corporation. All rights reserved.
//========================================================================


/*===================================================
// CLASS RGB
// Description: Color definitions as RGB triples
//===================================================
*/

class RGB
{
    public $rgb_table;
    public $img;

    public function __construct($aImg = null)
    {
        $this->img = $aImg;

        // Conversion array between color names and RGB
        $this->rgb_table = [
            'aqua' => [0,255,255],
            'lime' => [0,255,0],
            'teal' => [0,128,128],
            'whitesmoke' => [245,245,245],
            'gainsboro' => [220,220,220],
            'oldlace' => [253,245,230],
            'linen' => [250,240,230],
            'antiquewhite' => [250,235,215],
            'papayawhip' => [255,239,213],
            'blanchedalmond' => [255,235,205],
            'bisque' => [255,228,196],
            'peachpuff' => [255,218,185],
            'navajowhite' => [255,222,173],
            'moccasin' => [255,228,181],
            'cornsilk' => [255,248,220],
            'ivory' => [255,255,240],
            'lemonchiffon' => [255,250,205],
            'seashell' => [255,245,238],
            'mintcream' => [245,255,250],
            'azure' => [240,255,255],
            'aliceblue' => [240,248,255],
            'lavender' => [230,230,250],
            'lavenderblush' => [255,240,245],
            'mistyrose' => [255,228,225],
            'white' => [255,255,255],
            'black' => [0,0,0],
            'darkslategray' => [47,79,79],
            'dimgray' => [105,105,105],
            'slategray' => [112,128,144],
            'lightslategray' => [119,136,153],
            'gray' => [190,190,190],
            'lightgray' => [211,211,211],
            'midnightblue' => [25,25,112],
            'navy' => [0,0,128],
            'indigo' => [75,0,130],
            'electricindigo' => [102,0,255],
            'deepindigo' => [138,43,226],
            'pigmentindigo' => [75,0,130],
            'indigodye' => [0,65,106],
            'cornflowerblue' => [100,149,237],
            'darkslateblue' => [72,61,139],
            'slateblue' => [106,90,205],
            'mediumslateblue' => [123,104,238],
            'lightslateblue' => [132,112,255],
            'mediumblue' => [0,0,205],
            'royalblue' => [65,105,225],
            'blue' => [0,0,255],
            'dodgerblue' => [30,144,255],
            'deepskyblue' => [0,191,255],
            'skyblue' => [135,206,235],
            'lightskyblue' => [135,206,250],
            'steelblue' => [70,130,180],
            'lightred' => [211,167,168],
            'lightsteelblue' => [176,196,222],
            'lightblue' => [173,216,230],
            'powderblue' => [176,224,230],
            'paleturquoise' => [175,238,238],
            'darkturquoise' => [0,206,209],
            'mediumturquoise' => [72,209,204],
            'turquoise' => [64,224,208],
            'cyan' => [0,255,255],
            'lightcyan' => [224,255,255],
            'cadetblue' => [95,158,160],
            'mediumaquamarine' => [102,205,170],
            'aquamarine' => [127,255,212],
            'darkgreen' => [0,100,0],
            'darkolivegreen' => [85,107,47],
            'darkseagreen' => [143,188,143],
            'seagreen' => [46,139,87],
            'mediumseagreen' => [60,179,113],
            'lightseagreen' => [32,178,170],
            'palegreen' => [152,251,152],
            'springgreen' => [0,255,127],
            'lawngreen' => [124,252,0],
            'green' => [0,255,0],
            'chartreuse' => [127,255,0],
            'mediumspringgreen' => [0,250,154],
            'greenyellow' => [173,255,47],
            'limegreen' => [50,205,50],
            'yellowgreen' => [154,205,50],
            'forestgreen' => [34,139,34],
            'olivedrab' => [107,142,35],
            'darkkhaki' => [189,183,107],
            'khaki' => [240,230,140],
            'palegoldenrod' => [238,232,170],
            'lightgoldenrodyellow' => [250,250,210],
            'lightyellow' => [255,255,200],
            'yellow' => [255,255,0],
            'gold' => [255,215,0],
            'lightgoldenrod' => [238,221,130],
            'goldenrod' => [218,165,32],
            'darkgoldenrod' => [184,134,11],
            'rosybrown' => [188,143,143],
            'indianred' => [205,92,92],
            'saddlebrown' => [139,69,19],
            'sienna' => [160,82,45],
            'peru' => [205,133,63],
            'burlywood' => [222,184,135],
            'beige' => [245,245,220],
            'wheat' => [245,222,179],
            'sandybrown' => [244,164,96],
            'tan' => [210,180,140],
            'chocolate' => [210,105,30],
            'firebrick' => [178,34,34],
            'brown' => [165,42,42],
            'darksalmon' => [233,150,122],
            'salmon' => [250,128,114],
            'lightsalmon' => [255,160,122],
            'orange' => [255,165,0],
            'darkorange' => [255,140,0],
            'coral' => [255,127,80],
            'lightcoral' => [240,128,128],
            'tomato' => [255,99,71],
            'orangered' => [255,69,0],
            'red' => [255,0,0],
            'hotpink' => [255,105,180],
            'deeppink' => [255,20,147],
            'pink' => [255,192,203],
            'lightpink' => [255,182,193],
            'palevioletred' => [219,112,147],
            'maroon' => [176,48,96],
            'mediumvioletred' => [199,21,133],
            'violetred' => [208,32,144],
            'magenta' => [255,0,255],
            'violet' => [238,130,238],
            'plum' => [221,160,221],
            'orchid' => [218,112,214],
            'mediumorchid' => [186,85,211],
            'darkorchid' => [153,50,204],
            'darkviolet' => [148,0,211],
            'blueviolet' => [138,43,226],
            'purple' => [160,32,240],
            'mediumpurple' => [147,112,219],
            'thistle' => [216,191,216],
            'snow1' => [255,250,250],
            'snow2' => [238,233,233],
            'snow3' => [205,201,201],
            'snow4' => [139,137,137],
            'seashell1' => [255,245,238],
            'seashell2' => [238,229,222],
            'seashell3' => [205,197,191],
            'seashell4' => [139,134,130],
            'AntiqueWhite1' => [255,239,219],
            'AntiqueWhite2' => [238,223,204],
            'AntiqueWhite3' => [205,192,176],
            'AntiqueWhite4' => [139,131,120],
            'bisque1' => [255,228,196],
            'bisque2' => [238,213,183],
            'bisque3' => [205,183,158],
            'bisque4' => [139,125,107],
            'peachPuff1' => [255,218,185],
            'peachpuff2' => [238,203,173],
            'peachpuff3' => [205,175,149],
            'peachpuff4' => [139,119,101],
            'navajowhite1' => [255,222,173],
            'navajowhite2' => [238,207,161],
            'navajowhite3' => [205,179,139],
            'navajowhite4' => [139,121,94],
            'lemonchiffon1' => [255,250,205],
            'lemonchiffon2' => [238,233,191],
            'lemonchiffon3' => [205,201,165],
            'lemonchiffon4' => [139,137,112],
            'ivory1' => [255,255,240],
            'ivory2' => [238,238,224],
            'ivory3' => [205,205,193],
            'ivory4' => [139,139,131],
            'honeydew' => [193,205,193],
            'lavenderblush1' => [255,240,245],
            'lavenderblush2' => [238,224,229],
            'lavenderblush3' => [205,193,197],
            'lavenderblush4' => [139,131,134],
            'mistyrose1' => [255,228,225],
            'mistyrose2' => [238,213,210],
            'mistyrose3' => [205,183,181],
            'mistyrose4' => [139,125,123],
            'azure1' => [240,255,255],
            'azure2' => [224,238,238],
            'azure3' => [193,205,205],
            'azure4' => [131,139,139],
            'slateblue1' => [131,111,255],
            'slateblue2' => [122,103,238],
            'slateblue3' => [105,89,205],
            'slateblue4' => [71,60,139],
            'royalblue1' => [72,118,255],
            'royalblue2' => [67,110,238],
            'royalblue3' => [58,95,205],
            'royalblue4' => [39,64,139],
            'dodgerblue1' => [30,144,255],
            'dodgerblue2' => [28,134,238],
            'dodgerblue3' => [24,116,205],
            'dodgerblue4' => [16,78,139],
            'steelblue1' => [99,184,255],
            'steelblue2' => [92,172,238],
            'steelblue3' => [79,148,205],
            'steelblue4' => [54,100,139],
            'deepskyblue1' => [0,191,255],
            'deepskyblue2' => [0,178,238],
            'deepskyblue3' => [0,154,205],
            'deepskyblue4' => [0,104,139],
            'skyblue1' => [135,206,255],
            'skyblue2' => [126,192,238],
            'skyblue3' => [108,166,205],
            'skyblue4' => [74,112,139],
            'lightskyblue1' => [176,226,255],
            'lightskyblue2' => [164,211,238],
            'lightskyblue3' => [141,182,205],
            'lightskyblue4' => [96,123,139],
            'slategray1' => [198,226,255],
            'slategray2' => [185,211,238],
            'slategray3' => [159,182,205],
            'slategray4' => [108,123,139],
            'lightsteelblue1' => [202,225,255],
            'lightsteelblue2' => [188,210,238],
            'lightsteelblue3' => [162,181,205],
            'lightsteelblue4' => [110,123,139],
            'lightblue1' => [191,239,255],
            'lightblue2' => [178,223,238],
            'lightblue3' => [154,192,205],
            'lightblue4' => [104,131,139],
            'lightcyan1' => [224,255,255],
            'lightcyan2' => [209,238,238],
            'lightcyan3' => [180,205,205],
            'lightcyan4' => [122,139,139],
            'paleturquoise1' => [187,255,255],
            'paleturquoise2' => [174,238,238],
            'paleturquoise3' => [150,205,205],
            'paleturquoise4' => [102,139,139],
            'cadetblue1' => [152,245,255],
            'cadetblue2' => [142,229,238],
            'cadetblue3' => [122,197,205],
            'cadetblue4' => [83,134,139],
            'turquoise1' => [0,245,255],
            'turquoise2' => [0,229,238],
            'turquoise3' => [0,197,205],
            'turquoise4' => [0,134,139],
            'cyan1' => [0,255,255],
            'cyan2' => [0,238,238],
            'cyan3' => [0,205,205],
            'cyan4' => [0,139,139],
            'darkslategray1' => [151,255,255],
            'darkslategray2' => [141,238,238],
            'darkslategray3' => [121,205,205],
            'darkslategray4' => [82,139,139],
            'aquamarine1' => [127,255,212],
            'aquamarine2' => [118,238,198],
            'aquamarine3' => [102,205,170],
            'aquamarine4' => [69,139,116],
            'darkseagreen1' => [193,255,193],
            'darkseagreen2' => [180,238,180],
            'darkseagreen3' => [155,205,155],
            'darkseagreen4' => [105,139,105],
            'seagreen1' => [84,255,159],
            'seagreen2' => [78,238,148],
            'seagreen3' => [67,205,128],
            'seagreen4' => [46,139,87],
            'palegreen1' => [154,255,154],
            'palegreen2' => [144,238,144],
            'palegreen3' => [124,205,124],
            'palegreen4' => [84,139,84],
            'springgreen1' => [0,255,127],
            'springgreen2' => [0,238,118],
            'springgreen3' => [0,205,102],
            'springgreen4' => [0,139,69],
            'chartreuse1' => [127,255,0],
            'chartreuse2' => [118,238,0],
            'chartreuse3' => [102,205,0],
            'chartreuse4' => [69,139,0],
            'olivedrab1' => [192,255,62],
            'olivedrab2' => [179,238,58],
            'olivedrab3' => [154,205,50],
            'olivedrab4' => [105,139,34],
            'darkolivegreen1' => [202,255,112],
            'darkolivegreen2' => [188,238,104],
            'darkolivegreen3' => [162,205,90],
            'darkolivegreen4' => [110,139,61],
            'khaki1' => [255,246,143],
            'khaki2' => [238,230,133],
            'khaki3' => [205,198,115],
            'khaki4' => [139,134,78],
            'lightgoldenrod1' => [255,236,139],
            'lightgoldenrod2' => [238,220,130],
            'lightgoldenrod3' => [205,190,112],
            'lightgoldenrod4' => [139,129,76],
            'yellow1' => [255,255,0],
            'yellow2' => [238,238,0],
            'yellow3' => [205,205,0],
            'yellow4' => [139,139,0],
            'gold1' => [255,215,0],
            'gold2' => [238,201,0],
            'gold3' => [205,173,0],
            'gold4' => [139,117,0],
            'goldenrod1' => [255,193,37],
            'goldenrod2' => [238,180,34],
            'goldenrod3' => [205,155,29],
            'goldenrod4' => [139,105,20],
            'darkgoldenrod1' => [255,185,15],
            'darkgoldenrod2' => [238,173,14],
            'darkgoldenrod3' => [205,149,12],
            'darkgoldenrod4' => [139,101,8],
            'rosybrown1' => [255,193,193],
            'rosybrown2' => [238,180,180],
            'rosybrown3' => [205,155,155],
            'rosybrown4' => [139,105,105],
            'indianred1' => [255,106,106],
            'indianred2' => [238,99,99],
            'indianred3' => [205,85,85],
            'indianred4' => [139,58,58],
            'sienna1' => [255,130,71],
            'sienna2' => [238,121,66],
            'sienna3' => [205,104,57],
            'sienna4' => [139,71,38],
            'burlywood1' => [255,211,155],
            'burlywood2' => [238,197,145],
            'burlywood3' => [205,170,125],
            'burlywood4' => [139,115,85],
            'wheat1' => [255,231,186],
            'wheat2' => [238,216,174],
            'wheat3' => [205,186,150],
            'wheat4' => [139,126,102],
            'tan1' => [255,165,79],
            'tan2' => [238,154,73],
            'tan3' => [205,133,63],
            'tan4' => [139,90,43],
            'chocolate1' => [255,127,36],
            'chocolate2' => [238,118,33],
            'chocolate3' => [205,102,29],
            'chocolate4' => [139,69,19],
            'firebrick1' => [255,48,48],
            'firebrick2' => [238,44,44],
            'firebrick3' => [205,38,38],
            'firebrick4' => [139,26,26],
            'brown1' => [255,64,64],
            'brown2' => [238,59,59],
            'brown3' => [205,51,51],
            'brown4' => [139,35,35],
            'salmon1' => [255,140,105],
            'salmon2' => [238,130,98],
            'salmon3' => [205,112,84],
            'salmon4' => [139,76,57],
            'lightsalmon1' => [255,160,122],
            'lightsalmon2' => [238,149,114],
            'lightsalmon3' => [205,129,98],
            'lightsalmon4' => [139,87,66],
            'orange1' => [255,165,0],
            'orange2' => [238,154,0],
            'orange3' => [205,133,0],
            'orange4' => [139,90,0],
            'darkorange1' => [255,127,0],
            'darkorange2' => [238,118,0],
            'darkorange3' => [205,102,0],
            'darkorange4' => [139,69,0],
            'coral1' => [255,114,86],
            'coral2' => [238,106,80],
            'coral3' => [205,91,69],
            'coral4' => [139,62,47],
            'tomato1' => [255,99,71],
            'tomato2' => [238,92,66],
            'tomato3' => [205,79,57],
            'tomato4' => [139,54,38],
            'orangered1' => [255,69,0],
            'orangered2' => [238,64,0],
            'orangered3' => [205,55,0],
            'orangered4' => [139,37,0],
            'deeppink1' => [255,20,147],
            'deeppink2' => [238,18,137],
            'deeppink3' => [205,16,118],
            'deeppink4' => [139,10,80],
            'hotpink1' => [255,110,180],
            'hotpink2' => [238,106,167],
            'hotpink3' => [205,96,144],
            'hotpink4' => [139,58,98],
            'pink1' => [255,181,197],
            'pink2' => [238,169,184],
            'pink3' => [205,145,158],
            'pink4' => [139,99,108],
            'lightpink1' => [255,174,185],
            'lightpink2' => [238,162,173],
            'lightpink3' => [205,140,149],
            'lightpink4' => [139,95,101],
            'palevioletred1' => [255,130,171],
            'palevioletred2' => [238,121,159],
            'palevioletred3' => [205,104,137],
            'palevioletred4' => [139,71,93],
            'maroon1' => [255,52,179],
            'maroon2' => [238,48,167],
            'maroon3' => [205,41,144],
            'maroon4' => [139,28,98],
            'violetred1' => [255,62,150],
            'violetred2' => [238,58,140],
            'violetred3' => [205,50,120],
            'violetred4' => [139,34,82],
            'magenta1' => [255,0,255],
            'magenta2' => [238,0,238],
            'magenta3' => [205,0,205],
            'magenta4' => [139,0,139],
            'mediumred' => [140,34,34],
            'orchid1' => [255,131,250],
            'orchid2' => [238,122,233],
            'orchid3' => [205,105,201],
            'orchid4' => [139,71,137],
            'plum1' => [255,187,255],
            'plum2' => [238,174,238],
            'plum3' => [205,150,205],
            'plum4' => [139,102,139],
            'mediumorchid1' => [224,102,255],
            'mediumorchid2' => [209,95,238],
            'mediumorchid3' => [180,82,205],
            'mediumorchid4' => [122,55,139],
            'darkorchid1' => [191,62,255],
            'darkorchid2' => [178,58,238],
            'darkorchid3' => [154,50,205],
            'darkorchid4' => [104,34,139],
            'purple1' => [155,48,255],
            'purple2' => [145,44,238],
            'purple3' => [125,38,205],
            'purple4' => [85,26,139],
            'mediumpurple1' => [171,130,255],
            'mediumpurple2' => [159,121,238],
            'mediumpurple3' => [137,104,205],
            'mediumpurple4' => [93,71,139],
            'thistle1' => [255,225,255],
            'thistle2' => [238,210,238],
            'thistle3' => [205,181,205],
            'thistle4' => [139,123,139],
            'gray1' => [10,10,10],
            'gray2' => [40,40,30],
            'gray3' => [70,70,70],
            'gray4' => [100,100,100],
            'gray5' => [130,130,130],
            'gray6' => [160,160,160],
            'gray7' => [190,190,190],
            'gray8' => [210,210,210],
            'gray9' => [240,240,240],
            'darkgray' => [100,100,100],
            'darkblue' => [0,0,139],
            'darkcyan' => [0,139,139],
            'darkmagenta' => [139,0,139],
            'darkred' => [139,0,0],
            'silver' => [192, 192, 192],
            'eggplant' => [144,176,168],
            'lightgreen' => [144,238,144]];
    }


    //----------------
    // PUBLIC METHODS
    // Colors can be specified as either
    // 1. #xxxxxx   HTML style
    // 2. "colorname"  as a named color
    // 3. array(r,g,b) RGB triple
    // This function translates this to a native RGB format and returns an
    // RGB triple.

    public function Color($aColor)
    {
        if (is_string($aColor)) {
            $matches = [];
            // this regex will parse a color string and fill the $matches array as such:
            // 0: the full match if any
            // 1: a hex string preceded by a hash, can be 3 characters (#fff) or 6 (#ffffff) (4 or 5 also accepted but...)
            // 2,3,4: r,g,b values in hex if the first character of the string is #
            // 5: all alpha-numeric characters at the beginning of the string if string does not start with #
            // 6: alpha value prefixed by @ if supplied
            // 7: alpha value with @ stripped
            // 8: adjust value prefixed with : if supplied
            // 9: adjust value with : stripped
            $regex = '/(#([0-9a-fA-F]{1,2})([0-9a-fA-F]{1,2})([0-9a-fA-F]{1,2}))?([\w]+)?(@([\d\.,]+))?(:([\d\.,]+))?/';
            if (! preg_match($regex, $aColor, $matches)) {
                JpGraphError::RaiseL(25078, $aColor);//(" Unknown color: $aColor");
            }
            if (empty($matches[5])) {
                $r = strlen($matches[2]) == 1 ? $matches[2] . $matches[2] : $matches[2];
                $g = strlen($matches[3]) == 1 ? $matches[3] . $matches[3] : $matches[3];
                $b = strlen($matches[4]) == 1 ? $matches[4] . $matches[4] : $matches[4];
                $r = hexdec($r);
                $g = hexdec($g);
                $b = hexdec($b);
            } else {
                if (! isset($this->rgb_table[$matches[5]])) {
                    JpGraphError::RaiseL(25078, $aColor);//(" Unknown color: $aColor");
                }
                $r = $this->rgb_table[$matches[5]][0];
                $g = $this->rgb_table[$matches[5]][1];
                $b = $this->rgb_table[$matches[5]][2];
            }
            $alpha    = isset($matches[7]) ? str_replace(',', '.', $matches[7]) : 0;
            $adj    = isset($matches[9]) ? str_replace(',', '.', $matches[9]) : 1.0;

            if ($adj < 0) {
                JpGraphError::RaiseL(25077);//('Adjustment factor for color must be > 0');
            }

            // Scale adj so that an adj=2 always
            // makes the color 100% white (i.e. 255,255,255.
            // and adj=1 neutral and adj=0 black.
            if ($adj == 1) {
                return [$r, $g, $b, $alpha];
            } elseif ($adj > 1) {
                $m = ($adj - 1.0) * (255 - min(255, min($r, min($g, $b))));
                return [min(255, $r + $m), min(255, $g + $m), min(255, $b + $m), $alpha];
            } elseif ($adj < 1) {
                $m = ($adj - 1.0) * max(255, max($r, max($g, $b)));
                return [max(0, $r + $m), max(0, $g + $m), max(0, $b + $m), $alpha];
            }
        } elseif (is_array($aColor)) {
            if (! isset($aColor[3])) {
                $aColor[3] = 0;
            }
            return $aColor;
        } else {
            JpGraphError::RaiseL(25079, $aColor, count($aColor));//(" Unknown color specification: $aColor , size=".count($aColor));
        }
    }

    // Compare two colors
    // return true if equal
    public function Equal($aCol1, $aCol2)
    {
        $c1 = $this->Color($aCol1);
        $c2 = $this->Color($aCol2);
        return $c1[0] == $c2[0] && $c1[1] == $c2[1] && $c1[2] == $c2[2];
    }

    // Allocate a new color in the current image
    // Return new color index, -1 if no more colors could be allocated
    public function Allocate($aColor, $aAlpha = 0.0)
    {
        list ($r, $g, $b, $a) = $this->color($aColor);
        // If alpha is specified in the color string then this
        // takes precedence over the second argument
        if ($a > 0) {
            $aAlpha = $a;
        }
        if ($aAlpha < 0 || $aAlpha > 1) {
            JpGraphError::RaiseL(25080);//('Alpha parameter for color must be between 0.0 and 1.0');
        }
        return imagecolorresolvealpha($this->img, $r, $g, $b, round($aAlpha * 127));
    }

    // Try to convert an array with three valid numbers to the corresponding hex array
    // This is currenly only used in processing the colors for barplots in order to be able
    // to handle the case where the color might be specified as an array of colros as well.
    // In that case we must be able to find out if an array of values should be interpretated as
    // a single color (specifeid as an RGB triple)
    public static function tryHexConversion($aColor)
    {
        if (is_array($aColor)) {
            if (count($aColor) == 3) {
                if (is_numeric($aColor[0]) && is_numeric($aColor[1]) && is_numeric($aColor[2])) {
                    if (
                        ($aColor[0] >= 0 && $aColor[0] <= 255) &&
                        ($aColor[1] >= 0 && $aColor[1] <= 255) &&
                        ($aColor[2] >= 0 && $aColor[2] <= 255)
                    ) {
                        return sprintf('#%02x%02x%02x', $aColor[0], $aColor[1], $aColor[2]);
                    }
                }
            }
        }
        return $aColor;
    }

    // Return a RGB tripple corresponding to a position in the normal light spectrum
    // The argumen values is in the range [0, 1] where a value of 0 correponds to blue and
    // a value of 1 corresponds to red. Values in betwen is mapped to a linear interpolation
    // of the constituting colors in the visible color spectra.
    // The $aDynamicRange specified how much of the dynamic range we shold use
    // a value of 1.0 give the full dyanmic range and a lower value give more dark
    // colors. In the extreme of 0.0 then all colors will be black.
    public static function GetSpectrum($aVal, $aDynamicRange = 1.0)
    {
        if ($aVal < 0 || $aVal > 1.0001) {
            return [0, 0, 0]; // Invalid case - just return black
        }

        $sat = round(255 * $aDynamicRange);
        $a = 0.25;
        if ($aVal <= 0.25) {
            return [0, round($sat * $aVal / $a), $sat];
        } elseif ($aVal <= 0.5) {
            return [0, $sat, round($sat - $sat * ($aVal - 0.25) / $a)];
        } elseif ($aVal <= 0.75) {
            return [round($sat * ($aVal - 0.5) / $a), $sat, 0];
        } else {
            return [$sat, round($sat - $sat * ($aVal - 0.75) / $a), 0];
        }
    }
}
