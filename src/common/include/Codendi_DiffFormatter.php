<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Copied and Adapted from phpwiki diff formatter
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * A class to format Diffs
 *
 * This class formats the diff in classic diff format.
 * It is intended that this class be customized via inheritance,
 * to obtain fancier outputs.
 */
class Codendi_DiffFormatter // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * Number of leading context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses
     * may want to set this to other values.
     *
     * @var int
     */
    public $leading_context_lines = 0;
    /**
     * Number of trailing context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses
     * may want to set this to other values.
     *
     * @var int
     */
    public $trailing_context_lines = 0;
    /**
     * Format a diff.
     *
     * @param $diff object A Diff object.
     * @return string The formatted output.
     */
    public function format($diff)
    {
        $xi = $yi = 1;
        $block = \false;
        $context = [];
        $nlead = $this->leading_context_lines;
        $ntrail = $this->trailing_context_lines;
        $this->_start_diff();
        $x0 = $y0 = 0;
        foreach ($diff->edits as $edit) {
            if ($edit->type == 'copy') {
                if (\is_array($block)) {
                    if (\sizeof($edit->orig) <= $nlead + $ntrail) {
                        $block[] = $edit;
                    } else {
                        if ($ntrail) {
                            $context = \array_slice($edit->orig, 0, $ntrail);
                            $block[] = new \Codendi_DiffOp_Copy($context);
                        }
                        $this->_block($x0, $ntrail + $xi - $x0, $y0, $ntrail + $yi - $y0, $block);
                        $block = \false;
                    }
                }
                $context = $edit->orig;
            } else {
                if (! \is_array($block)) {
                    $context = \array_slice($context, \max(0, \sizeof($context) - $nlead));
                    $x0 = $xi - \sizeof($context);
                    $y0 = $yi - \sizeof($context);
                    $block = [];
                    \assert(\is_array($context));
                    if ($context) {
                        $block[] = new \Codendi_DiffOp_Copy($context);
                    }
                }
                $block[] = $edit;
            }
            if ($edit->orig) {
                $xi += \sizeof($edit->orig);
            }
            if ($edit->fin) {
                $yi += \sizeof($edit->fin);
            }
        }
        if (\is_array($block)) {
            $this->_block($x0, $xi - $x0, $y0, $yi - $y0, $block);
        }
        return $this->_end_diff();
    }
    public function _block($xbeg, $xlen, $ybeg, $ylen, &$edits)
    {
        $this->_start_block($this->_block_header($xbeg, $xlen, $ybeg, $ylen));
        foreach ($edits as $edit) {
            if ($edit->type == 'copy') {
                $this->_context($edit->orig);
            } elseif ($edit->type == 'add') {
                $this->_added($edit->fin);
            } elseif ($edit->type == 'delete') {
                $this->_deleted($edit->orig);
            } elseif ($edit->type == 'change') {
                $this->_changed($edit->orig, $edit->fin);
            } else {
                \trigger_error("Unknown edit type", \E_USER_ERROR);
            }
        }
        $this->_end_block();
    }
    public function _start_diff()
    {
        \ob_start();
    }
    public function _end_diff()
    {
        $val = \ob_get_contents();
        \ob_end_clean();
        return $val;
    }
    public function _block_header($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen > 1) {
            $xbeg .= "," . ($xbeg + $xlen - 1);
        }
        if ($ylen > 1) {
            $ybeg .= "," . ($ybeg + $ylen - 1);
        }
        return $xbeg . ($xlen ? $ylen ? 'c' : 'd' : 'a') . $ybeg;
    }
    public function _start_block($header)
    {
        echo $header;
    }
    public function _end_block()
    {
    }
    public function _lines($lines, $prefix = ' ')
    {
        foreach ($lines as $line) {
            echo "{$prefix} {$line}\n";
        }
    }
    public function _context($lines)
    {
        $this->_lines($lines);
    }
    public function _added($lines)
    {
        $this->_lines($lines, ">");
    }
    public function _deleted($lines)
    {
        $this->_lines($lines, "<");
    }
    public function _changed($orig, $fin)
    {
        $this->_deleted($orig);
        echo "---\n";
        $this->_added($fin);
    }
}
