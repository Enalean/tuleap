<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\AccessControl;

use Tuleap\SVNCore\Repository;

/**
 * I return instances of AccessFileHistory
 */
class AccessFileHistoryFactory
{
    private $dao;

    public function __construct(AccessFileHistoryDao $dao)
    {
        $this->dao = $dao;
    }

    /** return AccessFileHistory[] */
    public function getByRepository(Repository $repository)
    {
        $accessFiles = [];
        foreach ($this->dao->searchByRepositoryId($repository->getId()) as $row) {
            $accessFiles[] = $this->instantiateFromRowAndRepository($row, $repository);
        }

        return $accessFiles;
    }

    /** return AccessFileHistory */
    public function getById($id, Repository $repository)
    {
        $row = $this->dao->searchById($id, $repository->getId());

        if (! $row) {
            throw new AccessFileHistoryNotFoundException();
        }

        return $this->instantiateFromRowAndRepository($row, $repository);
    }

    /** return AccessFileHistory */
    public function getByVersionNumber($id, Repository $repository)
    {
        $row = $this->dao->searchByVersionNumber($id, $repository->getId());

        if (! $row) {
            throw new AccessFileHistoryNotFoundException();
        }

        return $this->instantiateFromRowAndRepository($row, $repository);
    }

    /** return AccessFileHistory */
    public function getCurrentVersion(Repository $repository)
    {
        $row = $this->dao->searchCurrentVersion($repository->getId());

        if (! $row) {
            return new NullAccessFileHistory($repository);
        }

        return $this->instantiateFromRowAndRepository($row, $repository);
    }

    /** return AccessFileHistory */
    public function getLastVersion(Repository $repository)
    {
        $row = $this->dao->searchLastVersion($repository->getId());

        if (! $row) {
            return new NullAccessFileHistory($repository);
        }

        return $this->instantiateFromRowAndRepository($row, $repository);
    }

    private function instantiateFromRowAndRepository(array $row, Repository $repository)
    {
        return new AccessFileHistory(
            $repository,
            (int) $row['id'],
            (int) $row['version_number'],
            (string) $row['content'],
            (int) $row['version_date']
        );
    }
}
