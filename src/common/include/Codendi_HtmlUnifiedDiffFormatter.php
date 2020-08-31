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

// diff.php
//
// PhpWiki diff output code.
//
// Copyright (C) 2000, 2001 Geoffrey T. Dairiki <dairiki@dairiki.org>
// You may copy this code freely under the conditions of the GPL.
/**
 * HTML unified diff formatter.
 *
 * This class formats a diff into a CSS-based
 * unified diff format.
 *
 * Within groups of changed lines, diffs are highlit
 * at the character-diff level.
 */
class Codendi_HtmlUnifiedDiffFormatter extends \Codendi_UnifiedDiffFormatter // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct($context_lines = 4)
    {
        parent::__construct($context_lines);
        $this->_html = '';
    }
    public function _block_header($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xbeg > 1) {
            return '[...]';
        }
        return "";
    }
    public function _start_diff()
    {
        $this->_html .= '';
    }
    public function _end_diff()
    {
        $this->_html .= '';
        return $this->_html;
    }
    public function _start_block($header)
    {
        $this->_html .= '<div class="block">';
        if ($header) {
            $this->_html .= '<tt>' . $header . '</tt>';
        }
    }
    public function _end_block()
    {
        $this->_html .= '</div>';
    }
    public function _lines($lines, $class = '', $prefix = \false, $elem = \false)
    {
        if (! $prefix) {
            $prefix = '&nbsp;';
        }
        $this->_html .= '<div class="difftext">';
        foreach ($lines as $line) {
            if ($elem) {
                $line = '<' . $elem . '>' . $line . '</' . $elem . '>';
            }
            $this->_html .= '<div class="' . $class . '">';
            $this->_html .= '<tt class="prefix">' . $prefix . '</tt>';
            $this->_html .= $line . '&nbsp;';
            $this->_html .= '</div>';
        }
        $this->_html .= '</div>';
    }
    public function _context($lines)
    {
        $this->_lines($lines, 'context');
    }
    public function _deleted($lines)
    {
        $this->_lines($lines, 'deleted', '-', 'del');
    }
    public function _added($lines)
    {
        $this->_lines($lines, 'added', '+', 'ins');
    }
    public function _changed($orig, $fin)
    {
        $diff = new \Codendi_WordLevelDiff($orig, $fin);
        $this->_lines($diff->orig(), 'original', '-');
        $this->_lines($diff->_fin(), 'final', '+');
    }
}
