<?php
//=======================================================================
// File:        JPGRAPH_LED.PHP
// Description: Module to generate Dotted LED-like digits
// Created:     2006-11-26
// Ver:         $Id: jpgraph_led.php 1674 2009-07-22 19:42:23Z ljp $
//
// Copyright 2006 (c) Asial Corporation. All rights reserved.
//
// Changed: 2007-08-06 by Alexander Kurochkin (inspector@list.ru)
//========================================================================

// Constants for color schema
DEFINE('LEDC_RED', 0);
DEFINE('LEDC_GREEN', 1);
DEFINE('LEDC_BLUE', 2);
DEFINE('LEDC_YELLOW', 3);
DEFINE('LEDC_GRAY', 4);
DEFINE('LEDC_CHOCOLATE', 5);
DEFINE('LEDC_PERU', 6);
DEFINE('LEDC_GOLDENROD', 7);
DEFINE('LEDC_KHAKI', 8);
DEFINE('LEDC_OLIVE', 9);
DEFINE('LEDC_LIMEGREEN', 10);
DEFINE('LEDC_FORESTGREEN', 11);
DEFINE('LEDC_TEAL', 12);
DEFINE('LEDC_STEELBLUE', 13);
DEFINE('LEDC_NAVY', 14);
DEFINE('LEDC_INVERTGRAY', 15);

//========================================================================
// CLASS DigitalLED74
// Description:
// Construct a number as an image that looks like LED numbers in a
// 7x4 digital matrix
//========================================================================
class DigitalLED74
{
    private $iLED_X = 4;
    private $iLED_Y = 7;

        // fg-up, fg-down, bg
    private $iColorSchema = [
            LEDC_RED  => ['red','darkred:0.9','red:0.3'],// 0
            LEDC_GREEN  => ['green','darkgreen','green:0.3'],// 1
            LEDC_BLUE  => ['lightblue:0.9','darkblue:0.85','darkblue:0.7'],// 2
            LEDC_YELLOW  => ['yellow','yellow:0.4','yellow:0.3'],// 3
            LEDC_GRAY  => ['gray:1.4','darkgray:0.85','darkgray:0.7'],
            LEDC_CHOCOLATE => ['chocolate','chocolate:0.7','chocolate:0.5'],
            LEDC_PERU  => ['peru:0.95','peru:0.6','peru:0.5'],
            LEDC_GOLDENROD => ['goldenrod','goldenrod:0.6','goldenrod:0.5'],
            LEDC_KHAKI  => ['khaki:0.7','khaki:0.4','khaki:0.3'],
            LEDC_OLIVE  => ['#808000','#808000:0.7','#808000:0.6'],
            LEDC_LIMEGREEN => ['limegreen:0.9','limegreen:0.5','limegreen:0.4'],
            LEDC_FORESTGREEN => ['forestgreen','forestgreen:0.7','forestgreen:0.5'],
            LEDC_TEAL  => ['teal','teal:0.7','teal:0.5'],
            LEDC_STEELBLUE => ['steelblue','steelblue:0.65','steelblue:0.5'],
            LEDC_NAVY  => ['navy:1.3','navy:0.95','navy:0.8'],//14
            LEDC_INVERTGRAY => ['darkgray','lightgray:1.5','white']//15
            ];

        /* Each line of the character is encoded as a 4 bit value
         0      ____
         1      ___x
         2      __x_
         3      __xx
         4      _x__
         5      _x_x
         6      _xx_
         7      _xxx
         8      x___
         9      x__x
         10     x_x_
         11     x_xx
         12     xx__
         13     xx_x
         14     xxx_
         15     xxxx
        */
    private $iLEDSpec = [
            0 => [6,9,11,15,13,9,6],
            1 => [2,6,10,2,2,2,2],
            2 => [6,9,1,2,4,8,15],
            3 => [6,9,1,6,1,9,6],
            4 => [1,3,5,9,15,1,1],
            5 => [15,8,8,14,1,9,6],
            6 => [6,8,8,14,9,9,6],
            7 => [15,1,1,2,4,4,4],
            8 => [6,9,9,6,9,9,6],
            9 => [6,9,9,7,1,1,6],
            '!' => [4,4,4,4,4,0,4],
            '?' => [6,9,1,2,2,0,2],
            '#' => [0,9,15,9,15,9,0],
            '@' => [6,9,11,11,10,9,6],
            '-' => [0,0,0,15,0,0,0],
            '_' => [0,0,0,0,0,0,15],
            '=' => [0,0,15,0,15,0,0],
            '+' => [0,0,4,14,4,0,0],
            '|' => [4,4,4,4,4,4,4], //vertical line, used for simulate rus 'Ы'
            ',' => [0,0,0,0,0,12,4],
            '.' => [0,0,0,0,0,12,12],
            ':' => [12,12,0,0,0,12,12],
            ';' => [12,12,0,0,0,12,4],
            '[' => [3,2,2,2,2,2,3],
            ']' => [12,4,4,4,4,4,12],
            '(' => [1,2,2,2,2,2,1],
            ')' => [8,4,4,4,4,4,8],
            '{' => [3,2,2,6,2,2,3],
            '}' => [12,4,4,6,4,4,12],
            '<' => [1,2,4,8,4,2,1],
            '>' => [8,4,2,1,2,4,8],
            '*' => [9,6,15,6,9,0,0],
            '"' => [10,10,0,0,0,0,0],
            '\'' => [4,4,0,0,0,0,0],
            '`' => [4,2,0,0,0,0,0],
            '~' => [13,11,0,0,0,0,0],
            '^' => [4,10,0,0,0,0,0],
            '\\' => [8,8,4,6,2,1,1],
            '/' => [1,1,2,6,4,8,8],
            '%' => [1,9,2,6,4,9,8],
            '&' => [0,4,10,4,11,10,5],
            '$' => [2,7,8,6,1,14,4],
            ' ' => [0,0,0,0,0,0,0],
            '•' => [0,0,6,6,0,0,0], //149
            '°' => [14,10,14,0,0,0,0], //176
            '†' => [4,4,14,4,4,4,4], //134
            '‡' => [4,4,14,4,14,4,4], //135
            '±' => [0,4,14,4,0,14,0], //177
            '‰' => [0,4,2,15,2,4,0], //137 show right arrow
            '™' => [0,2,4,15,4,2,0], //156 show left arrow
            'Ў' => [0,0,8,8,0,0,0], //159 show small hi-stick - that need for simulate rus 'Ф'
            "\t" => [8,8,8,0,0,0,0], //show hi-stick - that need for simulate rus 'У'
            "\r" => [8,8,8,8,8,8,8], //vertical line - that need for simulate 'M', 'W' and rus 'М','Ш' ,'Щ'
            "\n" => [15,15,15,15,15,15,15], //fill up - that need for simulate rus 'Ж'
            "Ґ" => [10,5,10,5,10,5,10], //chess
            "µ" => [15,0,15,0,15,0,15], //4 horizontal lines
            // latin
            'A' => [6,9,9,15,9,9,9],
            'B' => [14,9,9,14,9,9,14],
            'C' => [6,9,8,8,8,9,6],
            'D' => [14,9,9,9,9,9,14],
            'E' => [15,8,8,14,8,8,15],
            'F' => [15,8,8,14,8,8,8],
            'G' => [6,9,8,8,11,9,6],
            'H' => [9,9,9,15,9,9,9],
            'I' => [14,4,4,4,4,4,14],
            'J' => [15,1,1,1,1,9,6],
            'K' => [8,9,10,12,12,10,9],
            'L' => [8,8,8,8,8,8,15],
            'M' => [8,13,10,8,8,8,8],// need to add \r
            'N' => [9,9,13,11,9,9,9],
            'O' => [6,9,9,9,9,9,6],
            'P' => [14,9,9,14,8,8,8],
            'Q' => [6,9,9,9,13,11,6],
            'R' => [14,9,9,14,12,10,9],
            'S' => [6,9,8,6,1,9,6],
            'T' => [14,4,4,4,4,4,4],
            'U' => [9,9,9,9,9,9,6],
            'V' => [0,0,0,10,10,10,4],
            'W' => [8,8,8,8,10,13,8],// need to add \r
            'X' => [9,9,6,6,6,9,9],
            'Y' => [10,10,10,10,4,4,4],
            'Z' => [15,1,2,6,4,8,15],
            // russian utf-8
            'А' => [6,9,9,15,9,9,9],
            'Б' => [14,8,8,14,9,9,14],
            'В' => [14,9,9,14,9,9,14],
            'Г' => [15,8,8,8,8,8,8],
            'Д' => [14,9,9,9,9,9,14],
            'Е' => [15,8,8,14,8,8,15],
            'Ё' => [6,15,8,14,8,8,15],
            //Ж is combine: >\n<
            'З' => [6,9,1,2,1,9,6],
            'И' => [9,9,9,11,13,9,9],
            'Й' => [13,9,9,11,13,9,9],
            'К' => [9,10,12,10,9,9,9],
            'Л' => [7,9,9,9,9,9,9],
            'М' => [8,13,10,8,8,8,8],// need to add \r
            'Н' => [9,9,9,15,9,9,9],
            'О' => [6,9,9,9,9,9,6],
            'П' => [15,9,9,9,9,9,9],
            'Р' => [14,9,9,14,8,8,8],
            'С' => [6,9,8,8,8,9,6],
            'Т' => [14,4,4,4,4,4,4],
            'У' => [9,9,9,7,1,9,6],
            'Ф' => [2,7,10,10,7,2,2],// need to add Ў
            'Х' => [9,9,6,6,6,9,9],
            'Ц' => [10,10,10,10,10,15,1],
            'Ч' => [9,9,9,7,1,1,1],
            'Ш' => [10,10,10,10,10,10,15],// \r
            'Щ' => [10,10,10,10,10,15,0],// need to add \r
            'Ъ' => [12,4,4,6,5,5,6],
            'Ы' => [8,8,8,14,9,9,14],// need to add |
            'Ь' => [8,8,8,14,9,9,14],
            'Э' => [6,9,1,7,1,9,6],
            'Ю' => [2,2,2,3,2,2,2],// need to add O
            'Я' => [7,9,9,7,3,5,9]
            ];

    private $iSuperSampling = 3;
    private $iMarg = 1;
    private $iRad = 4;

    public function __construct($aRadius = 2, $aMargin = 0.6)
    {
        $this->iRad = $aRadius;
        $this->iMarg = $aMargin;
    }

    public function SetSupersampling($aSuperSampling = 2)
    {
        $this->iSuperSampling = $aSuperSampling;
    }

    public function _GetLED($aLedIdx, $aColor = 0)
    {
        $width =  $this->iLED_X * $this->iRad * 2 +  ($this->iLED_X + 1) * $this->iMarg + $this->iRad;
        $height = $this->iLED_Y * $this->iRad * 2 +  ($this->iLED_Y) * $this->iMarg + $this->iRad * 2;

        // Adjust radious for supersampling
        $rad = $this->iRad * $this->iSuperSampling;

        // Margin in between "Led" dots
        $marg = $this->iMarg * $this->iSuperSampling;

        $swidth = $width * $this->iSuperSampling;
        $sheight = $height * $this->iSuperSampling;

        $simg = new RotImage($swidth, $sheight, 0, DEFAULT_GFORMAT, false);
        $simg->SetColor($this->iColorSchema[$aColor][2]);
        $simg->FilledRectangle(0, 0, $swidth - 1, $sheight - 1);

        if (array_key_exists($aLedIdx, $this->iLEDSpec)) {
            $d = $this->iLEDSpec[$aLedIdx];
        } else {
            $d = [0, 0, 0, 0, 0, 0, 0];
        }

        for ($r = 0; $r < 7; ++$r) {
            $dr = $d[$r];
            for ($c = 0; $c < 4; ++$c) {
                if (($dr & pow(2, 3 - $c)) !== 0) {
                    $color = $this->iColorSchema[$aColor][0];
                } else {
                    $color = $this->iColorSchema[$aColor][1];
                }

                $x = 2 * $rad * $c + $rad + ($c + 1) * $marg + $rad;
                $y = 2 * $rad * $r + $rad + ($r + 1) * $marg + $rad;

                $simg->SetColor($color);
                $simg->FilledCircle($x, $y, $rad);
            }
        }

        $img =  new Image($width, $height, DEFAULT_GFORMAT, false);
        $img->Copy($simg->img, 0, 0, 0, 0, $width, $height, $swidth, $sheight);
        $simg->Destroy();
        unset($simg);
        return $img;
    }


    public function Stroke($aValStr, $aColor = 0, $aFileName = '')
    {
        $this->StrokeNumber($aValStr, $aColor, $aFileName);
    }


    public function StrokeNumber($aValStr, $aColor = 0, $aFileName = '')
    {
        if ($aColor < 0 || $aColor >= sizeof($this->iColorSchema)) {
            $aColor = 0;
        }

        if (($n = mb_strlen($aValStr, 'utf8')) == 0) {
            $aValStr = ' ';
            $n = 1;
        }

        for ($i = 0; $i < $n; ++$i) {
            $d = mb_substr($aValStr, $i, 1, 'utf8');
            if (ctype_digit($d)) {
                $d = (int) $d;
            } else {
                $d = strtoupper($d);
            }
            $digit_img[$i] = $this->_GetLED($d, $aColor);
        }

        $w = imagesx($digit_img[0]->img);
        $h = imagesy($digit_img[0]->img);

        $number_img = new Image($w * $n, $h, DEFAULT_GFORMAT, false);

        for ($i = 0; $i < $n; ++$i) {
            $number_img->Copy($digit_img[$i]->img, $i * $w, 0, 0, 0, $w, $h, $w, $h);
        }

        if ($aFileName != '') {
            $number_img->Stream($aFileName);
        } else {
            $number_img->Headers();
            $number_img->Stream();
        }
    }
}
