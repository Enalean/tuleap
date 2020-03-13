<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Statistics_DiskUsageOutput
{
    protected $_dum;

    public function __construct(Statistics_DiskUsageManager $dum)
    {
        $this->_dum = $dum;
    }

    /**
     * Return human readable sizes
     *
     * @link        http://aidanlister.com/repos/v/function.size_readable.php
     * @param       int     $size        size in bytes
     * @param       string  $max         maximum unit
     * @param       string  $system      'si' for SI, 'bi' for binary prefixes
     * @param       string  $retstring   return string format
     */
    public function sizeReadable($size, $max = null, $system = 'bi', $retstring = 'auto')
    {
        // Pick units
        $systems['si']['prefix'] = array('B', 'K', 'MB', 'GB', 'TB', 'PB');
        $systems['si']['size']   = 1000;
        $systems['bi']['prefix'] = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        $systems['bi']['size']   = 1024;
        $sys = isset($systems[$system]) ? $systems[$system] : $systems['si'];

        // Max unit to display
        $depth = count($sys['prefix']) - 1;
        if ($max && false !== $d = array_search($max, $sys['prefix'])) {
            $depth = $d;
        }

        // Loop
        $i = 0;
        while (abs($size) >= $sys['size'] && $i < $depth) {
            $size /= $sys['size'];
            $i++;
        }

        // Adapt the decimal places to the number of digit:
        // 1.24 / 12.3 / 123
        if ($retstring == 'auto') {
            $nbDigit = (int) (log(abs($size)) / log(10)) + 1;
            switch ($nbDigit) {
                case 1:
                    $retstring = '%.2f %s';
                    break;
                case 2:
                    $retstring = '%.1f %s';
                    break;
                default:
                    $retstring = '%d %s';
                    break;
            }
        }

        return sprintf($retstring, $size, $sys['prefix'][$i]);
    }
}
