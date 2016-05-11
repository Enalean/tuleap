<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Git_Command_Exception;
use Git_Exec;
use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\PullRequest\Exception\UnknownReferenceException;

class GitExec extends Git_Exec
{

    public function getBranchSha1($branch_name)
    {
        $output = array();

        try {
            $this->gitCmdWithOutput('show-ref --hash refs/heads/' . escapeshellarg($branch_name), $output);
        } catch (Git_Command_Exception $exception) {
            throw new UnknownBranchNameException($branch_name, 0, $exception);
        }

        if (count($output) === 1) {
            return $output[0];
        }

        throw new UnknownBranchNameException($branch_name);
    }

    public function getModifiedFiles($src_reference, $dest_reference)
    {
        $output = array();

        try {
            $this->gitCmdWithOutput(
                'diff --name-status ' . escapeshellarg($dest_reference) . '...' . escapeshellarg($src_reference),
                $output
            );
        } catch (Git_Command_Exception $exception) {
            throw new UnknownReferenceException();
        }

        return $output;
    }

    public function fetch($remote, $branch_name)
    {
        $output = array();
        $remote = escapeshellarg($remote);
        $branch = escapeshellarg('refs/heads/' . $branch_name);

        return $this->gitCmdWithOutput("fetch $remote $branch", $output);
    }

    public function fetchNoHistory($remote, $branch_name)
    {
        $output = array();
        $remote = escapeshellarg($remote);
        $branch = escapeshellarg('refs/heads/' . $branch_name);

        return $this->gitCmdWithOutput("fetch --depth 1 $remote $branch", $output);
    }

    public function fastForwardMerge($reference)
    {
        $output    = array();
        $reference = escapeshellarg($reference);

        return $this->gitCmdWithOutput('merge --ff-only ' . $reference, $output);
    }

    public function getAllBranchNames()
    {
        $output = array();
        $this->gitCmdWithOutput("branch | cut -c 3-", $output);
        return $output;
    }

    public function getCommitMessage($ref)
    {
       $ref    = escapeshellarg($ref);
       $cmd    = "log -1 $ref --pretty=%B";
       $output = array();

       $this->gitCmdWithOutput($cmd, $output);
       return $output;
    }

    public function getShortStat($ref_base, $ref_compare)
    {
        return $this->getFileDiffStat($ref_base, $ref_compare, '*');
    }

    public function getFileDiffStat($ref_base, $ref_compare, $file_path)
    {
        $ref_base    = escapeshellarg($ref_base);
        $ref_compare = escapeshellarg($ref_compare);
        $file_path   = escapeshellarg($file_path);
        $cmd         = "diff --numstat $ref_base $ref_compare -- $file_path";
        $output      = array();

        $this->gitCmdWithOutput($cmd, $output);
        return $this->parseDiffNumStatOutput($output);
    }

    public function getCommonAncestor($ref1, $ref2)
    {
        $ref1   = escapeshellarg($ref1);
        $ref2   = escapeshellarg($ref2);
        $cmd    = "merge-base $ref1 $ref2";
        $output = array();

        $this->gitCmdWithOutput($cmd, $output);
        return $output[0];
    }

    public function getMergedBranches($ref)
    {
        $ref    = escapeshellarg($ref);
        $cmd    = "branch --merged $ref | cut -c 3-";
        $output = array();

        $this->gitCmdWithOutput($cmd, $output);
        return $output;
    }

    private function parseDiffNumStatOutput($output) {
        $lines_added   = 0;
        $lines_removed = 0;
        $files_changed = 0;

        foreach ($output as $file_stat) {
            $tokens = explode("\t", $file_stat);
            if (count($tokens) != 3) {
                continue;
            }
            if (is_numeric($tokens[0])) {
                $lines_added   += intval($tokens[0]);
            }
            if (is_numeric($tokens[1])) {
                $lines_removed += intval($tokens[1]);
            }
            $files_changed += 1;
        }

        return new ShortStat($files_changed, $lines_added, $lines_removed);
    }
}
