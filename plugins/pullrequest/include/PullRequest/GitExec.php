<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
            $this->gitCmdWithOutput('show-ref --hash ' . escapeshellarg($branch_name), $output);
        } catch (Git_Command_Exception $exception) {
            throw new UnknownBranchNameException($branch_name, 0, $exception);
        }

        if (count($output) === 1) {
            return $output[0];
        }

        throw new UnknownBranchNameException($branch_name);
    }

    public function getModifiedFilesNameStatus($src_reference, $dest_reference)
    {
        $output = array();

        try {
            $this->gitCmdWithOutput(
                'diff --no-renames --name-status ' . escapeshellarg($dest_reference) . '...' . escapeshellarg($src_reference),
                $output
            );
        } catch (Git_Command_Exception $exception) {
            throw new UnknownReferenceException();
        }

        return $output;
    }

    public function getModifiedFilesLineStat($ref_base, $ref_compare)
    {
        $ref_base    = escapeshellarg($ref_base);
        $ref_compare = escapeshellarg($ref_compare);
        $cmd         = "diff --no-renames --numstat $ref_base...$ref_compare";
        $output      = [];

        try {
            $this->gitCmdWithOutput($cmd, $output);
        } catch (Git_Command_Exception $exception) {
            throw new UnknownReferenceException();
        }
        return $output;
    }

    public function sharedCloneAndCheckout($remote, $branch_name)
    {
        $output = array();
        $remote = escapeshellarg($remote);
        $branch = escapeshellarg($branch_name);
        $cmd    = "clone --shared -b $branch $remote " . $this->getPath();

        $retVal = 1;
        $git    = self::getGitCommand();

        // --work-tree --git-dir does not play well with git clone repo path
        exec("$git $cmd 2>&1", $output, $retVal);

        if ($retVal == 0) {
            return true;
        } else {
            throw new Git_Command_Exception("$git $cmd", $output, $retVal);
        }
    }

    public function merge($reference, $user)
    {
        $output    = array();
        $reference = escapeshellarg($reference);

        $this->setLocalCommiter($user->getRealName(), $user->getEmail());
        return $this->gitCmdWithOutput("merge --no-edit " . $reference, $output);
    }

    public function fastForwardMergeOnly($reference)
    {
        $this->gitCmdWithOutput('merge --ff-only ' . escapeshellarg($reference), $output);
    }

    /**
     * @return array
     */
    public function mergeBase($first_commit_reference, $second_commit_reference)
    {
        $output = [];

        $first_commit_reference  = escapeshellarg($first_commit_reference);
        $second_commit_reference = escapeshellarg($second_commit_reference);

        $this->gitCmdWithOutput("merge-base $first_commit_reference $second_commit_reference", $output);

        return $output;
    }

    /**
     * @return array
     */
    public function mergeTree($merge_base, $first_commit_reference, $second_commit_reference)
    {
        $output = [];

        $merge_base              = escapeshellarg($merge_base);
        $first_commit_reference  = escapeshellarg($first_commit_reference);
        $second_commit_reference = escapeshellarg($second_commit_reference);

        $this->gitCmdWithOutput("merge-tree $merge_base $second_commit_reference $first_commit_reference", $output);

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
        return $this->parseDiffNumStatOutput(
            $this->getModifiedFilesLineStat($ref_base, $ref_compare)
        );
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

    /** @return bool true if $merged_ref is an ancestor of $base_ref */
    public function isAncestor($base_ref, $merged_ref)
    {
        $base_ref   = escapeshellarg($base_ref);
        $merged_ref = escapeshellarg($merged_ref);

        try {
            $this->gitCmd("merge-base --is-ancestor $merged_ref $base_ref");
            return true;
        } catch (Git_Command_Exception $e) {
            return false;
        }
    }

    public function unidiff($file_path, $old_rev, $new_rev)
    {
        $file_path = escapeshellarg($file_path);
        $old_rev   = escapeshellarg($old_rev);
        $new_rev   = escapeshellarg($new_rev);
        $cmd       = "diff -U9999999 $old_rev..$new_rev -- $file_path";

        $this->gitCmdWithOutput($cmd, $output);
        return $output;
    }

    public function unidiffFromCommonAncestor($file_path, $old_rev, $new_rev)
    {
        $file_path = escapeshellarg($file_path);
        $old_rev   = escapeshellarg($old_rev);
        $new_rev   = escapeshellarg($new_rev);
        $cmd       = "diff -U9999999 $old_rev...$new_rev -- $file_path";

        $this->gitCmdWithOutput($cmd, $output);
        return $output;
    }

    /**
     * @return string[]
     */
    public function getReferencesFromPattern($pattern)
    {
        $output = [];
        $this->gitCmdWithOutput('for-each-ref --format="%(refname)" ' . escapeshellarg($pattern), $output);
        return $output;
    }

    public function removeReference($reference)
    {
        $output = null;
        $this->execAsGitoliteGroup('update-ref -d ' . escapeshellarg($reference), $output);
    }

    private function parseDiffNumStatOutput($output)
    {
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

    private function execAsGitoliteGroup($cmd, &$output)
    {
        $retVal   = 1;
        $as_group = 'sg - gitolite -c ';
        $git      = self::getGitCommand() . ' --work-tree=' . escapeshellarg($this->getPath()) . ' --git-dir=' . escapeshellarg($this->getGitDir());

        exec("$as_group '$git $cmd' 2>&1", $output, $retVal);

        if ($retVal == 0) {
            return true;
        } else {
            throw new Git_Command_Exception("$git $cmd", $output, $retVal);
        }
    }
}
