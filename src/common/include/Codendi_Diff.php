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
 * Class representing a 'diff' between two sequences of strings.
 */
class Codendi_Diff // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $edits;
    /**
     *
     * Computes diff between sequences of strings.
     *
     * @param $from_lines array An array of strings.
     *        (Typically these are lines from a file.)
     * @param $to_lines array An array of strings.
     */
    public function __construct($from_lines, $to_lines)
    {
        $eng = new \Codendi_DiffEngine();
        $this->edits = $eng->diff($from_lines, $to_lines);
        //$this->_check($from_lines, $to_lines);
    }
    /**
     * Compute reversed Diff.
     *
     * SYNOPSIS:
     *
     *  $diff = new Codendi_Diff($lines1, $lines2);
     *  $rev = $diff->reverse();
     * @return object A Diff object representing the inverse of the
     *                original diff.
     */
    public function reverse()
    {
        $rev = $this;
        $rev->edits = [];
        foreach ($this->edits as $edit) {
            $rev->edits[] = $edit->reverse();
        }
        return $rev;
    }
    /**
     * Check for empty diff.
     *
     * @return bool True iff two sequences were identical.
     */
    public function isEmpty()
    {
        foreach ($this->edits as $edit) {
            if ($edit->type != 'copy') {
                return \false;
            }
        }
        return \true;
    }
    /**
     * Compute the length of the Longest Common Subsequence (LCS).
     *
     * This is mostly for diagnostic purposed.
     *
     * @return int The length of the LCS.
     */
    public function lcs()
    {
        $lcs = 0;
        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $lcs += \sizeof($edit->orig);
            }
        }
        return $lcs;
    }
    /**
     * Get the original set of lines.
     *
     * This reconstructs the $from_lines parameter passed to the
     *
     *
     * @return array The original sequence of strings.
     */
    public function orig()
    {
        $lines = [];
        foreach ($this->edits as $edit) {
            if ($edit->orig) {
                \array_splice($lines, \sizeof($lines), 0, $edit->orig);
            }
        }
        return $lines;
    }
    /**
     * Get the fin set of lines.
     *
     * This reconstructs the $to_lines parameter passed to the
     *
     *
     * @return array The sequence of strings.
     */
    public function _fin()
    {
        $lines = [];
        foreach ($this->edits as $edit) {
            if ($edit->fin) {
                \array_splice($lines, \sizeof($lines), 0, $edit->fin);
            }
        }
        return $lines;
    }
    /**
     * Check a Diff for validity.
     *
     * This is here only for debugging purposes.
     */
    public function _check($from_lines, $to_lines)
    {
        if (\serialize($from_lines) != \serialize($this->orig())) {
            \trigger_error("Reconstructed original doesn't match", \E_USER_ERROR);
        }
        if (\serialize($to_lines) != \serialize($this->_fin())) {
            \trigger_error("Reconstructed fin doesn't match", \E_USER_ERROR);
        }
        $rev = $this->reverse();
        if (\serialize($to_lines) != \serialize($rev->orig())) {
            \trigger_error("Reversed original doesn't match", \E_USER_ERROR);
        }
        if (\serialize($from_lines) != \serialize($rev->_fin())) {
            \trigger_error("Reversed fin doesn't match", \E_USER_ERROR);
        }
        $prevtype = 'none';
        foreach ($this->edits as $edit) {
            if ($prevtype == $edit->type) {
                \trigger_error("Edit sequence is non-optimal", \E_USER_ERROR);
            }
            $prevtype = $edit->type;
        }
        $lcs = $this->lcs();
        \trigger_error("Diff okay: LCS = {$lcs}", \E_USER_NOTICE);
    }
}
