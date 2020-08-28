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

class Codendi_WordLevelDiff extends \Codendi_MappedDiff // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct($orig_lines, $fin_lines)
    {
        list($orig_words, $orig_stripped) = $this->_split($orig_lines);
        list($fin_words, $fin_stripped) = $this->_split($fin_lines);
        parent::__construct($orig_words, $fin_words, $orig_stripped, $fin_stripped);
    }
    public function _split($lines)
    {
        // FIXME: fix POSIX char class.
        if (! \preg_match_all('/ ( [^\S\n]+ | [[:alnum:]]+ | . ) (?: (?!< \n) [^\S\n])? /xs', \implode("\n", $lines), $m)) {
            return [[''], ['']];
        }
        return [$m[0], $m[1]];
    }
    public function orig()
    {
        $orig = new \Codendi_HWLDF_WordAccumulator();
        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $orig->addWords($edit->orig);
            } elseif ($edit->orig) {
                $orig->addWords($edit->orig, 'del');
            }
        }
        return $orig->getLines();
    }
    public function _fin()
    {
        $fin = new \Codendi_HWLDF_WordAccumulator();
        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $fin->addWords($edit->fin);
            } elseif ($edit->fin) {
                $fin->addWords($edit->fin, 'ins');
            }
        }
        return $fin->getLines();
    }
}
