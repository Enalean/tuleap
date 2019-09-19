<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class LastReleaseFinder
{

    public function __construct(GitExec $git_exec)
    {
        $this->git_exec = $git_exec;
    }

    public function retrieveFrom($git_remote_name_or_url = 'origin')
    {
        $version_list = $this->getReleaseList($git_remote_name_or_url);
        $maxVersion   = $this->maxVersion($version_list);
        echo "latest version : $maxVersion".PHP_EOL;
        return $maxVersion;
    }

    public function getReleaseList($git_remote_name_or_url)
    {
        $ls_remote_output = $this->git_exec->lsRemote($git_remote_name_or_url);
        $versions = array();
        $tags = preg_grep('%tags/[\d\.]{1,}$%', $ls_remote_output);
        foreach ($tags as $line) {
            $parts      = explode('/', $line);
            $versions[] = array_pop($parts);
        }
        return $versions;
    }

    public function maxVersion($versions)
    {
        return array_reduce($versions, array($this, 'max'));
    }

    private function max($v1, $v2)
    {
        return version_compare($v1, $v2, '>') ? $v1 : $v2;
    }
}

class ChangeDetector
{

    public function __construct(GitExec $git_exec, $candidate_paths)
    {
        $this->git_exec = $git_exec;
        $this->candidate_paths = $candidate_paths;
    }

    public function findPathsThatChangedSince($revision)
    {
        $changedPaths = array();
        foreach ($this->candidate_paths as $path) {
            if ($this->git_exec->hasChangedSince($path, $revision)) {
                $changedPaths[] = $path;
            }
        }
        return $changedPaths;
    }
}

/**
 * find() => given set of changed paths, removes the paths for which the VERSION file
 * was properly incremented
 */
class NonIncrementedPathFinder
{

    public function __construct(GitExec $git_exec, $old_revision, ChangeDetector $changed_paths_finder)
    {
        $this->git_exec = $git_exec;
        $this->change_detector = $changed_paths_finder;
        $this->old_revision = $old_revision;
    }

    public function pathsThatWereNotProperlyIncremented()
    {
        $changed_paths = $this->change_detector->findPathsThatChangedSince($this->old_revision);
        return array_values(array_filter($changed_paths, array($this, 'incremented')));
    }

    private function incremented($path)
    {
        $last_declared_version    = $this->getContentOfVERSIONFileAt($path, $this->old_revision);
        $current_declared_version = $this->getContentOfVERSIONFileAt($path, 'HEAD');

        echo("$path : old revision $last_declared_version new revision $current_declared_version".PHP_EOL);
        $incremented = version_compare($last_declared_version, $current_declared_version, '>=');
        return $incremented;
    }

    private function getContentOfVERSIONFileAt($path, $revision)
    {
        return $this->git_exec->fileContent($path."/VERSION", $revision);
    }
}

class CheckReleaseReporter
{

    public function __construct(NonIncrementedPathFinder $non_incremented_path_finder)
    {
        $this->non_incremented_paths_finder = $non_incremented_path_finder;
    }

    public function reportViolations()
    {
        $COLOR_RED     = "\033[31m";
        $COLOR_GREEN   = "\033[32m";
        $COLOR_NOCOLOR = "\033[0m";

        $non_incremented_paths = $this->non_incremented_paths_finder->pathsThatWereNotProperlyIncremented();
        foreach ($non_incremented_paths as $non_incremented_path) {
            echo "$COLOR_RED $non_incremented_path changed but wasn't incremented $COLOR_NOCOLOR".PHP_EOL;
        }

        if (! $non_incremented_paths) {
            echo "$COLOR_GREEN Everything was incremented correctly $COLOR_NOCOLOR".PHP_EOL;
        }

        exit(count($non_incremented_paths));
    }
}
