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
 * block conflict diff formatter.
 *
 * This class will format a diff identical to Diff3 (i.e. editpage
 * conflicts), but when there are only two source files. To be used by
 * future enhancements to reloading / upgrading pgsrc.
 *
 * Functional but not finished yet, need to eliminate redundant block
 * suffixes (i.e. "=======" immediately followed by another prefix)
 * see class LoadFileConflictPageEditor
 */
class Codendi_BlockDiffFormatter extends \Codendi_DiffFormatter // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct($context_lines = 4)
    {
        $this->leading_context_lines = $context_lines;
        $this->trailing_context_lines = $context_lines;
    }
    public function _lines($lines, $prefix = '')
    {
        if (! $prefix == '') {
            echo "{$prefix}\n";
        }
        foreach ($lines as $line) {
            echo "{$line}\n";
        }
        if (! $prefix == '') {
            echo "{$prefix}\n";
        }
    }
    public function _added($lines)
    {
        $this->_lines($lines, ">>>>>>>");
    }
    public function _deleted($lines)
    {
        $this->_lines($lines, "<<<<<<<");
    }
    public function _block_header($xbeg, $xlen, $ybeg, $ylen)
    {
        return "";
    }
    public function _changed($orig, $fin)
    {
        $this->_deleted($orig);
        $this->_added($fin);
    }
}
