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

// difflib.php
//
// A PHP diff engine for phpwiki.
//
// Copyright (C) 2000, 2001 Geoffrey T. Dairiki <dairiki@dairiki.org>
// You may copy this code freely under the conditions of the GPL.
class Codendi_DiffOp
{
    public $type;
    public $orig;
    public $fin;

    public function reverse()
    {
        trigger_error("pure virtual", E_USER_ERROR);
    }

    public function norig()
    {
        return $this->orig ? sizeof($this->orig) : 0;
    }

    public function nfin()
    {
        return $this->fin ? sizeof($this->fin) : 0;
    }
}

class Codendi_DiffOp_Copy extends Codendi_DiffOp
{
    public $type = 'copy';

    public function __construct($orig, $fin = false)
    {
        if (!is_array($fin)) {
            $fin = $orig;
        }
        $this->orig = $orig;
        $this->fin = $fin;
    }

    public function reverse()
    {
        return new Codendi_DiffOp_Copy($this->fin, $this->orig);
    }
}

class Codendi_DiffOp_Delete extends Codendi_DiffOp
{
    public $type = 'delete';

    public function __construct($lines)
    {
        $this->orig = $lines;
        $this->fin = false;
    }

    public function reverse()
    {
        return new Codendi_DiffOp_Add($this->orig);
    }
}

class Codendi_DiffOp_Add extends Codendi_DiffOp
{
    public $type = 'add';

    public function __construct($lines)
    {
        $this->fin = $lines;
        $this->orig = false;
    }

    public function reverse()
    {
        return new Codendi_DiffOp_Delete($this->fin);
    }
}

class Codendi_DiffOp_Change extends Codendi_DiffOp
{
    public $type = 'change';

    public function __construct($orig, $fin)
    {
        $this->orig = $orig;
        $this->fin = $fin;
    }

    public function reverse()
    {
        return new Codendi_DiffOp_Change($this->fin, $this->orig);
    }
}

/**
 * Class used internally by Diff to actually compute the diffs.
 *
 * The algorithm used here is mostly lifted from the perl module
 * Algorithm::Diff (version 1.06) by Ned Konz, which is available at:
 *   http://www.perl.com/CPAN/authors/id/N/NE/NEDKONZ/Algorithm-Diff-1.06.zip
 *
 * More ideas are taken from:
 *   http://www.ics.uci.edu/~eppstein/161/960229.html
 *
 * Some ideas are (and a bit of code) are from from analyze.c, from GNU
 * diffutils-2.7, which can be found at:
 *   ftp://gnudist.gnu.org/pub/gnu/diffutils/diffutils-2.7.tar.gz
 *
 * Finally, some ideas (subdivision by NCHUNKS > 2, and some optimizations)
 * are my own.
 *
 * @access private
 */
class Codendi_DiffEngine
{
    private $xv;
    private $yv;
    /**
     * @var array
     */
    private $xchanged;
    /**
     * @var array
     */
    private $ychanged;

    public function diff($from_lines, $to_lines)
    {
        $n_from = sizeof($from_lines);
        $n_to = sizeof($to_lines);

        $this->xchanged = $this->ychanged = array();
        $this->xv = $this->yv = array();
        $this->xind = $this->yind = array();
        unset($this->seq);
        unset($this->in_seq);
        unset($this->lcs);

        // Skip leading common lines.
        for ($skip = 0; $skip < $n_from && $skip < $n_to; $skip++) {
            if ($from_lines[$skip] != $to_lines[$skip]) {
                break;
            }
            $this->xchanged[$skip] = $this->ychanged[$skip] = false;
        }
        // Skip trailing common lines.
        $xi = $n_from;
        $yi = $n_to;
        for ($endskip = 0; --$xi > $skip && --$yi > $skip; $endskip++) {
            if ($from_lines[$xi] != $to_lines[$yi]) {
                break;
            }
            $this->xchanged[$xi] = $this->ychanged[$yi] = false;
        }

        // Ignore lines which do not exist in both files.
        for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
            $xhash[$from_lines[$xi]] = 1;
        }
        for ($yi = $skip; $yi < $n_to - $endskip; $yi++) {
            $line = $to_lines[$yi];
            if (($this->ychanged[$yi] = empty($xhash[$line]))) {
                continue;
            }
            $yhash[$line] = 1;
            $this->yv[] = $line;
            $this->yind[] = $yi;
        }
        for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
            $line = $from_lines[$xi];
            if (($this->xchanged[$xi] = empty($yhash[$line]))) {
                continue;
            }
            $this->xv[] = $line;
            $this->xind[] = $xi;
        }

        // Find the LCS.
        $this->_compareseq(0, sizeof($this->xv), 0, sizeof($this->yv));

        // Merge edits when possible
        $this->_shift_boundaries($from_lines, $this->xchanged, $this->ychanged);
        $this->_shift_boundaries($to_lines, $this->ychanged, $this->xchanged);

        // Compute the edit operations.
        $edits = array();
        $xi = $yi = 0;
        while ($xi < $n_from || $yi < $n_to) {
            // Skip matching "snake".
            $copy = array();
            while ($xi < $n_from && $yi < $n_to
                    && !$this->xchanged[$xi] && !$this->ychanged[$yi]) {
                $copy[] = $from_lines[$xi++];
                ++$yi;
            }
            if ($copy) {
                $edits[] = new Codendi_DiffOp_Copy($copy);
            }

            // Find deletes & adds.
            $delete = array();
            while ($xi < $n_from && $this->xchanged[$xi]) {
                $delete[] = $from_lines[$xi++];
            }

            $add = array();
            while ($yi < $n_to && $this->ychanged[$yi]) {
                $add[] = $to_lines[$yi++];
            }

            if ($delete && $add) {
                $edits[] = new Codendi_DiffOp_Change($delete, $add);
            } elseif ($delete) {
                $edits[] = new Codendi_DiffOp_Delete($delete);
            } elseif ($add) {
                $edits[] = new Codendi_DiffOp_Add($add);
            }
        }
        return $edits;
    }


    /* Divide the Largest Common Subsequence (LCS) of the sequences
     * [XOFF, XLIM) and [YOFF, YLIM) into NCHUNKS approximately equally
     * sized segments.
     *
     * Returns (LCS, PTS).  LCS is the length of the LCS. PTS is an
     * array of NCHUNKS+1 (X, Y) indexes giving the diving points between
     * sub sequences.  The first sub-sequence is contained in [X0, X1),
     * [Y0, Y1), the second in [X1, X2), [Y1, Y2) and so on.  Note
     * that (X0, Y0) == (XOFF, YOFF) and
     * (X[NCHUNKS], Y[NCHUNKS]) == (XLIM, YLIM).
     *
     * This function assumes that the first lines of the specified portions
     * of the two files do not match, and likewise that the last lines do not
     * match.  The caller must trim matching lines from the beginning and end
     * of the portions it is going to specify.
     */
    public function _diag($xoff, $xlim, $yoff, $ylim, $nchunks)
    {
        $flip = false;

        if ($xlim - $xoff > $ylim - $yoff) {
            // Things seems faster (I'm not sure I understand why)
            // when the shortest sequence in X.
            $flip = true;
            list ($xoff, $xlim, $yoff, $ylim)
                = array( $yoff, $ylim, $xoff, $xlim);
        }

        if ($flip) {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->xv[$i]][] = $i;
            }
        } else {
            for ($i = $ylim - 1; $i >= $yoff; $i--) {
                $ymatches[$this->yv[$i]][] = $i;
            }
        }

            $this->lcs = 0;
            $this->seq[0] = $yoff - 1;
            $this->in_seq = array();
            $ymids[0] = array();

            $numer = $xlim - $xoff + $nchunks - 1;
            $x = $xoff;
        for ($chunk = 0; $chunk < $nchunks; $chunk++) {
            if ($chunk > 0) {
                for ($i = 0; $i <= $this->lcs; $i++) {
                    $ymids[$i][$chunk - 1] = $this->seq[$i];
                }
            }

            $x1 = $xoff + (int) (($numer + ($xlim - $xoff) * $chunk) / $nchunks);
            for (; $x < $x1; $x++) {
                $line = $flip ? $this->yv[$x] : $this->xv[$x];
                if (empty($ymatches[$line])) {
                    continue;
                }
                $matches = $ymatches[$line];
                foreach ($matches as $y) {
                    if (empty($this->in_seq[$y])) {
                        $k = $this->_lcs_pos($y);
                        $ymids[$k] = $ymids[$k - 1];
                        break;
                    }
                }
                foreach ($matches as $y) {
                    if ($y > $this->seq[$k - 1]) {
                        // Optimization: this is a common case:
                        //  next match is just replacing previous match.
                        $this->in_seq[$this->seq[$k]] = false;
                        $this->seq[$k] = $y;
                        $this->in_seq[$y] = 1;
                    } elseif (empty($this->in_seq[$y])) {
                        $k = $this->_lcs_pos($y);
                        $ymids[$k] = $ymids[$k - 1];
                    }
                }
            }
        }

            $seps[] = $flip ? array($yoff, $xoff) : array($xoff, $yoff);
            $ymid = $ymids[$this->lcs];
        for ($n = 0; $n < $nchunks - 1; $n++) {
            $x1 = $xoff + (int) (($numer + ($xlim - $xoff) * $n) / $nchunks);
            $y1 = $ymid[$n] + 1;
            $seps[] = $flip ? array($y1, $x1) : array($x1, $y1);
        }
            $seps[] = $flip ? array($ylim, $xlim) : array($xlim, $ylim);

            return array($this->lcs, $seps);
    }

    public function _lcs_pos($ypos)
    {
        $end = $this->lcs;
        if ($end == 0 || $ypos > $this->seq[$end]) {
            $this->seq[++$this->lcs] = $ypos;
            $this->in_seq[$ypos] = 1;
            return $this->lcs;
        }

        $beg = 1;
        while ($beg < $end) {
            $mid = (int) (($beg + $end) / 2);
            if ($ypos > $this->seq[$mid]) {
                $beg = $mid + 1;
            } else {
                $end = $mid;
            }
        }

        $this->in_seq[$this->seq[$end]] = false;
        $this->seq[$end] = $ypos;
        $this->in_seq[$ypos] = 1;
        return $end;
    }

    /* Find LCS of two sequences.
     *
     * The results are recorded in the vectors $this->{x,y}changed[], by
     * storing a 1 in the element for each line that is an insertion
     * or deletion (ie. is not in the LCS).
     *
     * The subsequence of file 0 is [XOFF, XLIM) and likewise for file 1.
     *
     * Note that XLIM, YLIM are exclusive bounds.
     * All line numbers are origin-0 and discarded lines are not counted.
     */
    public function _compareseq($xoff, $xlim, $yoff, $ylim)
    {
        // Slide down the bottom initial diagonal.
        while ($xoff < $xlim && $yoff < $ylim
               && $this->xv[$xoff] == $this->yv[$yoff]) {
            ++$xoff;
            ++$yoff;
        }

        // Slide up the top initial diagonal.
        while ($xlim > $xoff && $ylim > $yoff
               && $this->xv[$xlim - 1] == $this->yv[$ylim - 1]) {
            --$xlim;
            --$ylim;
        }

        if ($xoff == $xlim || $yoff == $ylim) {
            $lcs = 0;
        } else {
            // This is ad hoc but seems to work well.
            //$nchunks = sqrt(min($xlim - $xoff, $ylim - $yoff) / 2.5);
            //$nchunks = max(2,min(8,(int)$nchunks));
            $nchunks = min(7, $xlim - $xoff, $ylim - $yoff) + 1;
            list ($lcs, $seps)
                = $this->_diag($xoff, $xlim, $yoff, $ylim, $nchunks);
        }

        if ($lcs == 0) {
            // X and Y sequences have no common subsequence:
            // mark all changed.
            while ($yoff < $ylim) {
                $this->ychanged[$this->yind[$yoff++]] = 1;
            }
            while ($xoff < $xlim) {
                $this->xchanged[$this->xind[$xoff++]] = 1;
            }
        } else {
            // Use the partitions to split this problem into subproblems.
            reset($seps);
            $pt1 = $seps[0];
            while ($pt2 = next($seps)) {
                $this->_compareseq($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
                $pt1 = $pt2;
            }
        }
    }

    /* Adjust inserts/deletes of identical lines to join changes
     * as much as possible.
     *
     * We do something when a run of changed lines include a
     * line at one end and has an excluded, identical line at the other.
     * We are free to choose which identical line is included.
     * `compareseq' usually chooses the one at the beginning,
     * but usually it is cleaner to consider the following identical line
     * to be the "change".
     *
     * This is extracted verbatim from analyze.c (GNU diffutils-2.7).
     */
    public function _shift_boundaries($lines, &$changed, $other_changed)
    {
        $i = 0;
        $j = 0;

        $len = sizeof($lines);
        $other_len = sizeof($other_changed);

        while (1) {
            /*
             * Scan forwards to find beginning of another run of changes.
             * Also keep track of the corresponding point in the other file.
             *
             * Throughout this code, $i and $j are adjusted together so that
             * the first $i elements of $changed and the first $j elements
             * of $other_changed both contain the same number of zeros
             * (unchanged lines).
             * Furthermore, $j is always kept so that $j == $other_len or
             * $other_changed[$j] == false.
             */
            while ($j < $other_len && $other_changed[$j]) {
                $j++;
            }

            while ($i < $len && ! $changed[$i]) {
                $i++;
                $j++;
                while ($j < $other_len && $other_changed[$j]) {
                    $j++;
                }
            }

            if ($i == $len) {
                break;
            }

            $start = $i;

            // Find the end of this run of changes.
            while (++$i < $len && $changed[$i]) {
                continue;
            }

            do {
                /*
                 * Record the length of this run of changes, so that
                 * we can later determine whether the run has grown.
                 */
                $runlength = $i - $start;

                /*
                 * Move the changed region back, so long as the
                 * previous unchanged line matches the last changed one.
                 * This merges with previous changed regions.
                 */
                while ($start > 0 && $lines[$start - 1] == $lines[$i - 1]) {
                    $changed[--$start] = 1;
                    $changed[--$i] = false;
                    while ($start > 0 && $changed[$start - 1]) {
                        $start--;
                    }
                    while ($other_changed[--$j]) {
                        continue;
                    }
                }

                /*
                 * Set CORRESPONDING to the end of the changed run, at the last
                 * point where it corresponds to a changed run in the other file.
                 * CORRESPONDING == LEN means no such point has been found.
                 */
                $corresponding = $j < $other_len ? $i : $len;

                /*
                 * Move the changed region forward, so long as the
                 * first changed line matches the following unchanged one.
                 * This merges with following changed regions.
                 * Do this second, so that if there are no merges,
                 * the changed region is moved forward as far as possible.
                 */
                while ($i < $len && $lines[$start] == $lines[$i]) {
                    $changed[$start++] = false;
                    $changed[$i++] = 1;
                    while ($i < $len && $changed[$i]) {
                        $i++;
                    }

                    $j++;
                    if ($j < $other_len && $other_changed[$j]) {
                        $corresponding = $i;
                        while ($j < $other_len && $other_changed[$j]) {
                            $j++;
                        }
                    }
                }
            } while ($runlength != $i - $start);

            /*
             * If possible, move the fully-merged run of changes
             * back to a corresponding run in the other file.
             */
            while ($corresponding < $i) {
                $changed[--$start] = 1;
                $changed[--$i] = 0;
                while ($other_changed[--$j]) {
                    continue;
                }
            }
        }
    }
}

/**
 * Class representing a 'diff' between two sequences of strings.
 */
class Codendi_Diff
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
        $eng = new Codendi_DiffEngine();
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
        $rev->edits = array();
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
                return false;
            }
        }
        return true;
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
                $lcs += sizeof($edit->orig);
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
        $lines = array();

        foreach ($this->edits as $edit) {
            if ($edit->orig) {
                array_splice($lines, sizeof($lines), 0, $edit->orig);
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
        $lines = array();

        foreach ($this->edits as $edit) {
            if ($edit->fin) {
                array_splice($lines, sizeof($lines), 0, $edit->fin);
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
        if (serialize($from_lines) != serialize($this->orig())) {
            trigger_error("Reconstructed original doesn't match", E_USER_ERROR);
        }
        if (serialize($to_lines) != serialize($this->_fin())) {
            trigger_error("Reconstructed fin doesn't match", E_USER_ERROR);
        }

        $rev = $this->reverse();
        if (serialize($to_lines) != serialize($rev->orig())) {
            trigger_error("Reversed original doesn't match", E_USER_ERROR);
        }
        if (serialize($from_lines) != serialize($rev->_fin())) {
            trigger_error("Reversed fin doesn't match", E_USER_ERROR);
        }

        $prevtype = 'none';
        foreach ($this->edits as $edit) {
            if ($prevtype == $edit->type) {
                trigger_error("Edit sequence is non-optimal", E_USER_ERROR);
            }
            $prevtype = $edit->type;
        }

        $lcs = $this->lcs();
        trigger_error("Diff okay: LCS = $lcs", E_USER_NOTICE);
    }
}




/**
 * FIXME: bad name.
 */
class Codendi_MappedDiff extends Codendi_Diff
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
    public function __construct(
        $from_lines,
        $to_lines,
        $mapped_from_lines,
        $mapped_to_lines
    ) {
        assert(sizeof($from_lines) == sizeof($mapped_from_lines));
        assert(sizeof($to_lines) == sizeof($mapped_to_lines));

        parent::__construct($mapped_from_lines, $mapped_to_lines);

        $xi = $yi = 0;
        // Optimizing loop invariants:
        // http://phplens.com/lens/php-book/optimizing-debugging-php.php
        for ($i = 0, $max = sizeof($this->edits); $i < $max; $i++) {
            $orig = &$this->edits[$i]->orig;
            if (is_array($orig)) {
                $orig = array_slice($from_lines, $xi, sizeof($orig));
                $xi += sizeof($orig);
            }

            $fin = &$this->edits[$i]->fin;
            if (is_array($fin)) {
                $fin = array_slice($to_lines, $yi, sizeof($fin));
                $yi += sizeof($fin);
            }
        }
    }
}


/**
 * A class to format Diffs
 *
 * This class formats the diff in classic diff format.
 * It is intended that this class be customized via inheritance,
 * to obtain fancier outputs.
 */
class Codendi_DiffFormatter
{
    /**
     * Number of leading context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses
     * may want to set this to other values.
     */
    public $leading_context_lines = 0;

    /**
     * Number of trailing context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses
     * may want to set this to other values.
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
        $block = false;
        $context = array();

        $nlead = $this->leading_context_lines;
        $ntrail = $this->trailing_context_lines;

        $this->_start_diff();

        $x0 = $y0 = 0;
        foreach ($diff->edits as $edit) {
            if ($edit->type == 'copy') {
                if (is_array($block)) {
                    if (sizeof($edit->orig) <= $nlead + $ntrail) {
                        $block[] = $edit;
                    } else {
                        if ($ntrail) {
                            $context = array_slice($edit->orig, 0, $ntrail);
                            $block[] = new Codendi_DiffOp_Copy($context);
                        }
                        $this->_block(
                            $x0,
                            $ntrail + $xi - $x0,
                            $y0,
                            $ntrail + $yi - $y0,
                            $block
                        );
                        $block = false;
                    }
                }
                $context = $edit->orig;
            } else {
                if (! is_array($block)) {
                    $context = array_slice($context, max(0, sizeof($context) - $nlead));
                    $x0 = $xi - sizeof($context);
                    $y0 = $yi - sizeof($context);
                    $block = array();
                    assert(is_array($context));
                    if ($context) {
                        $block[] = new Codendi_DiffOp_Copy($context);
                    }
                }
                $block[] = $edit;
            }

            if ($edit->orig) {
                $xi += sizeof($edit->orig);
            }
            if ($edit->fin) {
                $yi += sizeof($edit->fin);
            }
        }

        if (is_array($block)) {
            $this->_block(
                $x0,
                $xi - $x0,
                $y0,
                $yi - $y0,
                $block
            );
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
                trigger_error("Unknown edit type", E_USER_ERROR);
            }
        }

        $this->_end_block();
    }

    public function _start_diff()
    {
        ob_start();
    }

    public function _end_diff()
    {
        $val = ob_get_contents();
        ob_end_clean();
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

        return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
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
            echo "$prefix $line\n";
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

/**
 * "Unified" diff formatter.
 *
 * This class formats the diff in classic "unified diff" format.
 */
class Codendi_UnifiedDiffFormatter extends Codendi_DiffFormatter
{
    public function __construct($context_lines = 4)
    {
        $this->leading_context_lines = $context_lines;
        $this->trailing_context_lines = $context_lines;
    }

    public function _block_header($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen != 1) {
            $xbeg .= "," . $xlen;
        }
        if ($ylen != 1) {
            $ybeg .= "," . $ylen;
        }
        return "@@ -$xbeg +$ybeg @@\n";
    }

    public function _added($lines)
    {
        $this->_lines($lines, "+");
    }
    public function _deleted($lines)
    {
        $this->_lines($lines, "-");
    }
    public function _changed($orig, $fin)
    {
        $this->_deleted($orig);
        $this->_added($fin);
    }
}

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
class Codendi_BlockDiffFormatter extends Codendi_DiffFormatter
{
    public function __construct($context_lines = 4)
    {
        $this->leading_context_lines = $context_lines;
        $this->trailing_context_lines = $context_lines;
    }
    public function _lines($lines, $prefix = '')
    {
        if (! $prefix == '') {
            echo "$prefix\n";
        }
        foreach ($lines as $line) {
            echo "$line\n";
        }
        if (! $prefix == '') {
            echo "$prefix\n";
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
class Codendi_HtmlUnifiedDiffFormatter extends Codendi_UnifiedDiffFormatter
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

    public function _lines($lines, $class = '', $prefix = false, $elem = false)
    {
        if (!$prefix) {
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
        $diff = new Codendi_WordLevelDiff($orig, $fin);
        $this->_lines($diff->orig(), 'original', '-');
        $this->_lines($diff->_fin(), 'final', '+');
    }
}

class Codendi_WordLevelDiff extends Codendi_MappedDiff
{
    public function __construct($orig_lines, $fin_lines)
    {
        list ($orig_words, $orig_stripped) = $this->_split($orig_lines);
        list ($fin_words, $fin_stripped) = $this->_split($fin_lines);

        parent::__construct(
            $orig_words,
            $fin_words,
            $orig_stripped,
            $fin_stripped
        );
    }

    public function _split($lines)
    {
        // FIXME: fix POSIX char class.
        if (!preg_match_all(
            '/ ( [^\S\n]+ | [[:alnum:]]+ | . ) (?: (?!< \n) [^\S\n])? /xs',
            implode("\n", $lines),
            $m
        )) {
            return array(array(''), array(''));
        }
        return array($m[0], $m[1]);
    }

    public function orig()
    {
        $orig = new Codendi_HWLDF_WordAccumulator();

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
        $fin = new Codendi_HWLDF_WordAccumulator();

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

class Codendi_HWLDF_WordAccumulator
{
    public function __construct()
    {
        $this->_lines = array();
        $this->_line = false;
        $this->_group = false;
        $this->_tag = '~begin';
    }

    public function _flushGroup($new_tag)
    {
        if ($this->_group !== false) {
            if (!$this->_line) {
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
                $word = substr($word, 1);
            }
            assert(!strstr($word, "\n"));
            $this->_group .= $word;
        }
    }

    public function getLines()
    {
        $this->_flushLine('~done');
        return $this->_lines;
    }
}
