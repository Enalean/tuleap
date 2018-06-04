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

    public function getModifiedFiles($src_reference, $dest_reference)
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

    public function fetchRemote($remote_name)
    {
        $remote_name = escapeshellarg($remote_name);
        $cmd         = "fetch $remote_name";

        $this->execAsGitoliteGroup($cmd, $output);
        return $output;
    }

    public function cloneAndCheckout($remote, $branch_name)
    {
        $output = array();
        $remote = escapeshellarg($remote);
        $branch = escapeshellarg($branch_name);
        $cmd    = "clone -b $branch $remote " . $this->getPath();

        $retVal = 1;
        $git    = $this->getGitCommand();

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
        $cmd         = "diff --no-renames --numstat $ref_base...$ref_compare -- $file_path";
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

    /** @return true if $merged_ref is an ancestor of $base_ref */
    public function isAncestor($base_ref, $merged_ref)
    {
        $base_ref   = escapeshellarg($base_ref);
        $merged_ref = escapeshellarg($merged_ref);

        $merge_base_cmd    = "merge-base $base_ref $merged_ref";
        $merge_base_output = array();

        $rev_parse_cmd    = "rev-parse --verify $merged_ref";
        $rev_parse_output = array();

        try {
            $this->gitCmdWithOutput($merge_base_cmd, $merge_base_output);
            $this->gitCmdWithOutput($rev_parse_cmd, $rev_parse_output);
            return $rev_parse_output[0] == $merge_base_output[0];
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

    public function addRemote($remote_name, $remote_path)
    {
        $remote_name = escapeshellarg($remote_name);
        $remote_path = escapeshellarg($remote_path);
        $cmd         = "remote add $remote_name $remote_path";

        $this->execAsGitoliteGroup($cmd, $output);
        return $output;
    }

    public function remoteExists($remote_name)
    {
        $remote_name = escapeshellarg($remote_name);
        $cmd         = "remote show $remote_name";

        try {
            $this->gitCmdWithOutput($cmd, $output);
        } catch (Git_Command_Exception $e) {
            return false;
        }
        return true;
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

    private function execAsGitoliteGroup($cmd, &$output)
    {
        $retVal   = 1;
        $as_group = 'sg - gitolite -c ';
        $git      = $this->getGitCommand() . ' --work-tree=' . escapeshellarg($this->getPath()) . ' --git-dir=' . escapeshellarg($this->getGitDir());

        exec("$as_group '$git $cmd' 2>&1", $output, $retVal);

        if ($retVal == 0) {
            return true;
        } else {
            throw new Git_Command_Exception("$git $cmd", $output, $retVal);
        }
    }
}
