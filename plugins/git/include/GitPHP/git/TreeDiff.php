<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

class TreeDiff implements \Iterator, \Countable
{
    public const EMPTY_TREE_HASH = "4b825dc642cb6eb9a060e54bf8d69288fbee4904";

    /**
     * fromHash
     *
     * Stores the from hash
     *
     * @access protected
     */
    protected $fromHash;

    /**
     * toHash
     *
     * Stores the to hash
     *
     * @access protected
     */
    protected $toHash;

    /**
     * renames
     *
     * Stores whether to detect renames
     *
     * @access protected
     */
    protected $renames;

    /**
     * project
     *
     * Stores the project
     *
     * @access protected
     */
    protected $project;

    /**
     * fileDiffs
     *
     * Stores the individual file diffs
     *
     * @access protected
     */
    protected $fileDiffs = array();

    /**
     * dataRead
     *
     * Stores whether data has been read
     *
     * @access protected
     */
    protected $dataRead = false;

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     * @param mixed $project project
     * @param string $toHash to commit hash
     * @param string $fromHash from commit hash
     * @param bool $renames whether to detect file renames
     * @return mixed TreeDiff object
     * @throws \Exception exception on invalid parameters
     */
    public function __construct($project, $toHash, $fromHash = '', $renames = false)
    {
        $this->project = $project;

        $toCommit = $this->project->GetCommit($toHash);
        $this->toHash = $toHash;

        if (empty($fromHash)) {
            $parent = $toCommit->GetParent();
            if ($parent) {
                $this->fromHash = $parent->GetHash();
            }
        } else {
            $fromCommit = $this->project->GetCommit($fromHash);
            $this->fromHash = $fromHash;
        }

        $this->renames = $renames;
    }

    /**
     * ReadData
     *
     * Reads the tree diff data
     *
     * @access private
     */
    private function ReadData() // @codingStandardsIgnoreLine
    {
        $this->dataRead = true;

        $this->fileDiffs = array();

        $exe = new GitExe($this->project);

        $args = array();

        $args[] = '-r';
        if ($this->renames) {
            $args[] = '-M';
        }

        if (empty($this->fromHash)) {
            $args[] = escapeshellarg(self::EMPTY_TREE_HASH);
        } else {
            $args[] = escapeshellarg($this->fromHash);
        }

        $stat_args = array_merge($args, ['--numstat']);

        $args[] = escapeshellarg($this->toHash);
        $stat_args[] = escapeshellarg($this->toHash);

        $stats_indexed_by_filename = $this->getStatsIndexedByFilename($exe, $stat_args);
        $diffTreeLines = explode("\n", $exe->Execute(GitExe::DIFF_TREE, $args));
        foreach ($diffTreeLines as $line) {
            $trimmed = trim($line);
            if ((strlen($trimmed) > 0) && (substr_compare($trimmed, ':', 0, 1) === 0)) {
                try {
                    $this->fileDiffs[] = new FileDiff($this->project, $trimmed, '', $stats_indexed_by_filename);
                } catch (\Exception $e) {
                }
            }
        }

        unset($exe);
    }

    /**
     * GetFromHash
     *
     * Gets the from hash for this treediff
     *
     * @access public
     * @return string from hash
     */
    public function GetFromHash() // @codingStandardsIgnoreLine
    {
        return $this->fromHash;
    }

    /**
     * GetToHash
     *
     * Gets the to hash for this treediff
     *
     * @access public
     * @return string to hash
     */
    public function GetToHash() // @codingStandardsIgnoreLine
    {
        return $this->toHash;
    }

    /**
     * GetRenames
     *
     * Get whether this treediff is set to detect renames
     *
     * @access public
     * @return bool true if renames will be detected
     */
    public function GetRenames() // @codingStandardsIgnoreLine
    {
        return $this->renames;
    }

    /**
     * SetRenames
     *
     * Set whether this treediff is set to detect renames
     *
     * @access public
     * @param bool $renames whether to detect renames
     */
    public function SetRenames($renames) // @codingStandardsIgnoreLine
    {
        if ($renames == $this->renames) {
            return;
        }

        $this->renames = $renames;
        $this->dataRead = false;
    }

    /**
     * rewind
     *
     * Rewinds the iterator
     */
    public function rewind()
    {
        if (!$this->dataRead) {
            $this->ReadData();
        }

        reset($this->fileDiffs);
    }

    /**
     * current
     *
     * Returns the current element in the array
     */
    public function current()
    {
        if (!$this->dataRead) {
            $this->ReadData();
        }

        return current($this->fileDiffs);
    }

    /**
     * key
     *
     * Returns the current key
     */
    public function key()
    {
        if (!$this->dataRead) {
            $this->ReadData();
        }

        return key($this->fileDiffs);
    }

    /**
     * next
     *
     * Advance the pointer
     */
    public function next()
    {
        if (!$this->dataRead) {
            $this->ReadData();
        }

        next($this->fileDiffs);
    }

    /**
     * valid
     *
     * Test for a valid pointer
     */
    public function valid()
    {
        if (!$this->dataRead) {
            $this->ReadData();
        }

        return key($this->fileDiffs) !== null;
    }

    /**
     * Count
     *
     * Gets the number of file changes in this treediff
     *
     * @access public
     * @return int count of file changes
     */
    public function Count() // @codingStandardsIgnoreLine
    {
        if (!$this->dataRead) {
            $this->ReadData();
        }

        return count($this->fileDiffs);
    }

    /**
     * @param array  $stat_args
     *
     * @return array
     */
    private function getStatsIndexedByFilename(GitExe $exe, array $stat_args)
    {
        $stats_indexed_by_filename = [];
        $diff_stats                = explode("\n", $exe->Execute(GitExe::DIFF, $stat_args));
        foreach ($diff_stats as $line) {
            if (! trim($line)) {
                continue;
            }
            list($added, $removed, $filename) = explode("\t", $line);
            $stats_indexed_by_filename[$filename] = [
                'added'   => $added,
                'removed' => $removed
            ];
        }

        return $stats_indexed_by_filename;
    }
}
