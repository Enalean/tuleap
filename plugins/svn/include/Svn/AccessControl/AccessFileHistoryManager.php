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

namespace Tuleap\Svn\AccessControl;

use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\Repository\Repository;

class AccessFileHistoryManager {

    private $dao;

    public function __construct(AccessFileHistoryDao $dao) {
        $this->dao = $dao;
    }

    public function create(AccessFileHistory $access_file) {
        if (! $this->dao->create($access_file)) {
            throw new CannotCreateAccessFileHistoryException($GLOBALS['Language']->getText('plugin_svn','update_access_history_file_error'));
        }
    }

    /** return AccessFileHistory[] */
    public function getByRepository(Repository $repository) {
        $accessFiles = array();
        foreach ($this->dao->searchByRepositoryId($repository->getId()) as $row) {
            $accessFiles[] = $this->instantiateFromRowAndRepository($row, $repository);
        }

        return $accessFiles;
    }

    public function getById($id, Repository $repository) {
        return $this->dao->searchById($id, $repository->getId());
    }

    /** return AccessFileHistory */
    public function getCurrentVersion(Repository $repository) {
        $row = $this->dao->searchCurrentVersion($repository->getId());

        if (! $row) {
            return new NullAccessFileHistory($repository);
        }

        return $this->instantiateFromRowAndRepository($row, $repository);
    }

    /** return AccessFileHistory */
    public function getLastVersion(Repository $repository) {
        $row = $this->dao->searchLastVersion($repository->getId());

        if (! $row) {
            return new NullAccessFileHistory($repository);
        }

        return $this->instantiateFromRowAndRepository($row, $repository);
    }

    private function instantiateFromRowAndRepository(array $row, Repository $repository) {
        return new AccessFileHistory(
            $repository,
            $row['id'],
            $row['version_number'],
            $row['content'],
            $row['version_date']
        );
    }

    public function useAnOldVersion(Repository $repository, $version_id) {
        return $this->dao->useAnOldVersion($repository->getId(), $version_id);
    }
}
