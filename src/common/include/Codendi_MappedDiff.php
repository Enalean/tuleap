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
 * FIXME: bad name.
 */
class Codendi_MappedDiff extends \Codendi_Diff // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     *
     *
     * Computes diff between sequences of strings.
     *
     * This can be used to compute things like
     * case-insensitve diffs, or diffs which ignore
     * changes in white-space.
     *
     * @param $from_lines array An array of strings.
     *  (Typically these are lines from a file.)
     *
     * @param $to_lines array An array of strings.
     *
     * @param $mapped_from_lines array This array should
     *  have the same size number of elements as $from_lines.
     *  The elements in $mapped_from_lines and
     *  $mapped_to_lines are what is actually compared
     *  when computing the diff.
     *
     * @param $mapped_to_lines array This array should
     *  have the same number of elements as $to_lines.
     */
    public function __construct($from_lines, $to_lines, $mapped_from_lines, $mapped_to_lines)
    {
        \assert(\sizeof($from_lines) == \sizeof($mapped_from_lines));
        \assert(\sizeof($to_lines) == \sizeof($mapped_to_lines));
        parent::__construct($mapped_from_lines, $mapped_to_lines);
        $xi = $yi = 0;
        // Optimizing loop invariants:
        // http://phplens.com/lens/php-book/optimizing-debugging-php.php
        for ($i = 0, $max = \sizeof($this->edits); $i < $max; $i++) {
            $orig =& $this->edits[$i]->orig;
            if (\is_array($orig)) {
                $orig = \array_slice($from_lines, $xi, \sizeof($orig));
                $xi += \sizeof($orig);
            }
            $fin =& $this->edits[$i]->fin;
            if (\is_array($fin)) {
                $fin = \array_slice($to_lines, $yi, \sizeof($fin));
                $yi += \sizeof($fin);
            }
        }
    }
}
