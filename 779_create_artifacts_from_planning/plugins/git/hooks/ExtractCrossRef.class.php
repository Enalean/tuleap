<?php
/**
 * Copyright (c) Enalean 2011. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Manage interaction with environment of extractCrossRef hook script
 */
class ExtractCrossRef {

    public function getProjectName($repoPath) {
        $rootPaths = array('gitolite/repositories', 'gitroot');
        foreach ($rootPaths as $rootPath) {
            if (strpos($repoPath, $rootPath) !== false) {
                $cutPoint = strpos($repoPath, $rootPath) + strlen($rootPath) + 1;
                return $this->getTheFirstElementAfterTheRootPath($repoPath, $cutPoint);
            }
        }
        throw new GitNoProjectFoundException();

    }

    private function getTheFirstElementAfterTheRootPath($repoPath, $cutPoint) {
        $relativePath = substr($repoPath, $cutPoint);
        $pathElements = explode('/', $relativePath);
        return $pathElements[0];
    }

}
class GitNoProjectFoundException extends Exception {
}

?>
