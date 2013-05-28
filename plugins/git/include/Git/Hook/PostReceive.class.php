<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Git_Hook_PostReceive {
    const FAKE_EMPTY_COMMIT = '0000000000000000000000000000000000000000';
    const CROSS_REF_TYPE    = 'git_commit';

    private $exec_repo;
    private $extract_cross_ref;

    public function __construct(Git_Exec $exec_repo, Git_Hook_ExtractCrossReferences $extract_cross_ref) {
        $this->exec_repo         = $exec_repo;
        $this->extract_cross_ref = $extract_cross_ref;
    }

    public function execute($repository_path, $user_name, $oldrev, $newrev, $refname) {
        $project_name = $this->getProjectName($repository_path);
        $ref_prefix   = $this->getRepositoryPath($repository_path);
        foreach ($this->getRevisionsList($oldrev, $newrev, $refname) as $rev) {
            $rev_id = $ref_prefix.'/'.$rev;
            $text   = $this->exec_repo->catFile($rev);
            $this->extract_cross_ref->extract($project_name, $user_name, self::CROSS_REF_TYPE, $rev_id, $text);
        }
    }

    private function getRevisionsList($oldrev, $newrev, $refname) {
        if ($oldrev == self::FAKE_EMPTY_COMMIT) {
            return $this->exec_repo->revListSinceStart($refname, $newrev);
        } elseif ($newrev == self::FAKE_EMPTY_COMMIT) {
            return array();
        } else {
            return $this->exec_repo->revList($oldrev, $newrev);
        }
    }

    private function getProjectName($repoPath) {
        $path = $this->getPathFromRoot($repoPath);
        return $this->getTheFirstElementAfterTheRootPath($path);
    }

    private function getRepositoryPath($repoPath) {
        $path = $this->getPathFromRoot($repoPath);
        $pathElements = explode('/', $path);
        array_shift($pathElements);
        $repository_name = array_pop($pathElements);
        array_push($pathElements, basename($repository_name, '.git'));
        return implode('/', $pathElements);
    }

    private function getPathFromRoot($repoPath) {
        $rootPaths = array('gitolite/repositories', 'gitroot');
        foreach ($rootPaths as $rootPath) {
            if (strpos($repoPath, $rootPath) !== false) {
                $cut_point = strpos($repoPath, $rootPath) + strlen($rootPath) + 1;
                return substr($repoPath, $cut_point);
            }
        }
        throw new GitNoProjectFoundException();
    }


    private function getTheFirstElementAfterTheRootPath($relativePath) {
        $pathElements = explode('/', $relativePath);
        return $pathElements[0];
    }
}

class GitNoProjectFoundException extends Exception {
}

?>
