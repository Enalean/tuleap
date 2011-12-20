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
 * Description of ExtractCrossRef
 */
class ExtractCrossRef {
    const ROOT_PATHS = '/var/lib/codendi/gitolite/repositories,/var/lib/codendi/gitroot,/gitroot,/gitolite/repositories';

    public function getProjectName($repoPath) {
        $rootPaths = explode(',', self::ROOT_PATHS);
        foreach ($rootPaths as $rootPath) {
            if (strpos($repoPath, $rootPath) === 0) {
                return $this->getTheFirstElementAfterTheRootPath($repoPath, $rootPath);
            }
        }
        throw new GitNoProjectFound();

    }

    private function getTheFirstElementAfterTheRootPath($repoPath, $rootPath) {
        $relativePath = substr($repoPath, strlen($rootPath)+1);
        $pathElements = explode('/', $relativePath);
        return $pathElements[0];
    }

}
class GitNoProjectFound extends Exception {
}

?>
