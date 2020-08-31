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

class Codendi_HWLDF_WordAccumulator // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var array
     */
    private $_lines;
    public function __construct()
    {
        $this->_lines = [];
        $this->_line = \false;
        $this->_group = \false;
        $this->_tag = '~begin';
    }
    public function _flushGroup($new_tag)
    {
        if ($this->_group !== \false) {
            if (! $this->_line) {
                $this->_line = "";
            }
            if ($this->_tag) {
                $this->_line .= '<' . $this->_tag . '>';
            }
            $this->_line .= $this->_group;
            if ($this->_tag) {
                $this->_line .= '</' . $this->_tag . '>';
            }
        }
        $this->_group = '';
        $this->_tag = $new_tag;
    }
    public function _flushLine($new_tag)
    {
        $this->_flushGroup($new_tag);
        if ($this->_line) {
            $this->_lines[] = $this->_line;
        }
        $this->_line = "";
    }
    public function addWords($words, $tag = '')
    {
        if ($tag != $this->_tag) {
            $this->_flushGroup($tag);
        }
        foreach ($words as $word) {
            // new-line should only come as first char of word.
            if ($word === '') {
                continue;
            }
            if ($word[0] == "\n") {
                $this->_group .= " ";
                $this->_flushLine($tag);
                $word = \substr($word, 1);
            }
            \assert(! \strstr($word, "\n"));
            $this->_group .= $word;
        }
    }
    public function getLines()
    {
        $this->_flushLine('~done');
        return $this->_lines;
    }
}
