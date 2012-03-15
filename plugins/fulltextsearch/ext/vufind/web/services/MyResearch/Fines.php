<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'services/MyResearch/MyResearch.php';

require_once 'Structures/DataGrid.php';

class Fines extends MyResearch
{
    function launch()
    {
        global $interface;
        global $finesIndexEngine;

        // Get My Fines
        if ($patron = $this->catalogLogin()) {
            if (PEAR::isError($patron))
                PEAR::raiseError($patron);
            $result = $this->catalog->getMyFines($patron);
            if (!PEAR::isError($result)) {
                if (count($result)) {
                    // Drop the index object into a global
                    //  so it's accessible inside the callback.
                    $finesIndexEngine = $this->db;

                    $dg = new Structures_DataGrid();
                    $dg->renderer->setTableAttribute('width', '100%');
                    $dg->renderer->setTableAttribute('cellspacing', '1');
                    $dg->renderer->setTableAttribute('cellpadding', '4');
                    $dg->renderer->setTableAttribute('class', 'datagrid');

                    $dg->addColumn(new Structures_DataGrid_Column(translate('Title'), 'id', 'id', null, null, 'printLink'));
                    $dg->addColumn(new Structures_DataGrid_Column(translate('Checked Out'), 'checkout', 'checkout'));
                    $dg->addColumn(new Structures_DataGrid_Column(translate('Due Date'), 'duedate', 'duedate'));
                    $dg->addColumn(new Structures_DataGrid_Column(translate('Fine'), 'fine', 'fine'));
                    $dg->addColumn(new Structures_DataGrid_Column(translate('Fee'), 'amount', 'amount', null, null, 'formatNumber'));
                    $dg->addColumn(new Structures_DataGrid_Column(translate('Balance'), 'balance', 'balance', null, null, 'formatNumber'));

                    $dg->bind($result);
                    if (method_exists($dg, 'getOutput')) {
                        $interface->assign('finesData', $dg->getOutput());
                    } else {
                        $interface->assign('finesData', $dg->renderer->toHTML());
                    }
                } else {
                    $interface->assign('finesData', translate('You do not have any fines'));
                }
            }
        }

        $interface->setTemplate('fines.tpl');
        $interface->setPageTitle('My Fines');
        $interface->display('layout.tpl');
    }
    
}

function printLink($params)
{
    global $configArray;
    global $finesIndexEngine;
    
    extract($params);
    
    if ($record['id']) {
        $record = $finesIndexEngine->getRecord($record['id']);
        return '<a href="' . $configArray['Site']['url'] . '/Record/' . urlencode($record['id']) . '">' . htmlspecialchars($record['title_short']) . '</a>';
    } else {
        return "n/a";
    }
}

function formatNumber($params)
{
    extract($params);
    if ($record[$fieldName]) {
        // Insert the decimal point in the appropriate place (we're converting a
        // value represented in pennies).  Note that we need to force the fractional
        // portion of the number to two decimal places in order to prevent $0.05
        // turning into $0.50:
        $number = substr($record[$fieldName], 0, -2) . '.' . 
            sprintf("%02d", substr($record[$fieldName], -2));
    } else {
        $number = 0;
    }

    // money_format() does not exist on windows
    if (function_exists('money_format')) {
        return money_format('%.2n', $number);
    } else {
        return safeMoneyFormat($number);
    }
}

// Windows alternatives
function safeMoneyFormat($number)
{
    // '' or NULL gets the locale values from environment variables
    setlocale(LC_ALL, '');
    $locale = localeconv();
    forEach($locale as $key => $val) {
      $$key = $val;
    }

    // Windows doesn't support UTF-8 encoding in setlocale, so we'll have to
    // convert the currency symbol manually:
    $currency_symbol = safeMoneyFormatMakeUTF8($currency_symbol);

    // How is the ammount signed?
    // Positive
    if ($number > 0) {
        $sign         = $positive_sign;
        $sign_posn    = $p_sign_posn;
        $sep_by_space = $p_sep_by_space;
        $cs_precedes  = $p_cs_precedes;
    // Negative
    } else {
        $sign         = $negative_sign;
        $sign_posn    = $n_sign_posn;
        $sep_by_space = $n_sep_by_space;
        $cs_precedes  = $n_cs_precedes;
    }

    // Format the absolute value of the number
    $m = number_format(abs($number), $frac_digits, $mon_decimal_point, $mon_thousands_sep);
    // Spaces between the number and symbol?
    if ($sep_by_space) {
        $space = ' ';
    } else {
        $space = '';
    }
    if ($cs_precedes) {
      $m = $currency_symbol.$space.$m;
    } else {
      $m = $m.$space.$currency_symbol;
    }
    // HTML spaces
    $m = str_replace(' ', '&nbsp;', $m);

    // Add symbol
    switch ($sign_posn) {
        case 0:
            $m = "($m)";
            break;
        case 1:
            $m = $sign.$m;
            break;
        case 2:
            $m = $m.$sign;
            break;
        case 3:
            $m = $sign.$m;
            break;
        case 4:
            $m = $m.$sign;
            break;
        default:
            $m = "$m [error sign_posn = $sign_posn&nbsp;!]";
    }
    return $m;
}

// Adapted from code at http://us.php.net/manual/en/function.utf8-encode.php
// This is needed for Windows only as a support function for safeMoneyFormat;
// utf8_encode by itself doesn't do the job, but this is capable of properly
// turning currency symbols into valid UTF-8.
function safeMoneyFormatMakeUTF8($instr){
    static $nibble_good_chars = false;
    static $byte_map = array();
    
    if (empty($byte_map)) {
        for($x=128;$x<256;++$x){
            $byte_map[chr($x)]=utf8_encode(chr($x));
        }
        $cp1252_map=array(
            "\x80"=>"\xE2\x82\xAC",    // EURO SIGN
            "\x82" => "\xE2\x80\x9A",  // SINGLE LOW-9 QUOTATION MARK
            "\x83" => "\xC6\x92",      // LATIN SMALL LETTER F WITH HOOK
            "\x84" => "\xE2\x80\x9E",  // DOUBLE LOW-9 QUOTATION MARK
            "\x85" => "\xE2\x80\xA6",  // HORIZONTAL ELLIPSIS
            "\x86" => "\xE2\x80\xA0",  // DAGGER
            "\x87" => "\xE2\x80\xA1",  // DOUBLE DAGGER
            "\x88" => "\xCB\x86",      // MODIFIER LETTER CIRCUMFLEX ACCENT
            "\x89" => "\xE2\x80\xB0",  // PER MILLE SIGN
            "\x8A" => "\xC5\xA0",      // LATIN CAPITAL LETTER S WITH CARON
            "\x8B" => "\xE2\x80\xB9",  // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
            "\x8C" => "\xC5\x92",      // LATIN CAPITAL LIGATURE OE
            "\x8E" => "\xC5\xBD",      // LATIN CAPITAL LETTER Z WITH CARON
            "\x91" => "\xE2\x80\x98",  // LEFT SINGLE QUOTATION MARK
            "\x92" => "\xE2\x80\x99",  // RIGHT SINGLE QUOTATION MARK
            "\x93" => "\xE2\x80\x9C",  // LEFT DOUBLE QUOTATION MARK
            "\x94" => "\xE2\x80\x9D",  // RIGHT DOUBLE QUOTATION MARK
            "\x95" => "\xE2\x80\xA2",  // BULLET
            "\x96" => "\xE2\x80\x93",  // EN DASH
            "\x97" => "\xE2\x80\x94",  // EM DASH
            "\x98" => "\xCB\x9C",      // SMALL TILDE
            "\x99" => "\xE2\x84\xA2",  // TRADE MARK SIGN
            "\x9A" => "\xC5\xA1",      // LATIN SMALL LETTER S WITH CARON
            "\x9B" => "\xE2\x80\xBA",  // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
            "\x9C" => "\xC5\x93",      // LATIN SMALL LIGATURE OE
            "\x9E" => "\xC5\xBE",      // LATIN SMALL LETTER Z WITH CARON
            "\x9F" => "\xC5\xB8"       // LATIN CAPITAL LETTER Y WITH DIAERESIS
        );
        foreach($cp1252_map as $k=>$v){
            $byte_map[$k]=$v;
        }
    }
    if (!$nibble_good_chars) {
        $ascii_char='[\x00-\x7F]';
        $cont_byte='[\x80-\xBF]';
        $utf8_2='[\xC0-\xDF]'.$cont_byte;
        $utf8_3='[\xE0-\xEF]'.$cont_byte.'{2}';
        $utf8_4='[\xF0-\xF7]'.$cont_byte.'{3}';
        $utf8_5='[\xF8-\xFB]'.$cont_byte.'{4}';
        $nibble_good_chars = "@^($ascii_char+|$utf8_2|$utf8_3|$utf8_4|$utf8_5)(.*)$@s";
    }

    $outstr='';
    $char='';
    $rest='';
    while((strlen($instr))>0){
        if(1==preg_match($nibble_good_chars,$instr,$match)){
            $char=$match[1];
            $rest=$match[2];
            $outstr.=$char;
        }elseif(1==preg_match('@^(.)(.*)$@s',$instr,$match)){
            $char=$match[1];
            $rest=$match[2];
            $outstr.=$byte_map[$char];
        }
        $instr=$rest;
    }
    return $outstr;
}
?>