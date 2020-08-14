<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVN\AccessControl;

use DataAccessObject;

class AccessFileHistoryDao extends DataAccessObject
{

    /**
     * @return false|int
     */
    public function create(int $version_number, int $repository_id, string $content, int $version_date)
    {
        $this->da->startTransaction();

        $version_number = $this->da->escapeInt($version_number);
        $repository_id  = $this->da->escapeInt($repository_id);
        $content        = $this->da->quoteSmart($content);
        $version_date   = $this->da->escapeInt($version_date);

        $sql = "INSERT INTO plugin_svn_accessfile_history
                    (version_number, repository_id, content, version_date)
                  VALUES ($version_number, $repository_id, $content, $version_date)";

        $id = $this->updateAndGetLastId($sql);
        if (! $id) {
            $this->rollBack();
            return false;
        }

        $sql = "UPDATE plugin_svn_repositories
                SET accessfile_id = $id
                WHERE id = $repository_id";

        if (! $this->update($sql)) {
            $this->rollBack();
            return false;
        }

        $this->commit();
        return (int) $id;
    }

    public function searchByRepositoryId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_svn_accessfile_history
                WHERE repository_id = $repository_id";

        return $this->retrieve($sql);
    }

    public function searchCurrentVersion($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT accessfile.*
                FROM plugin_svn_accessfile_history AS accessfile
                    INNER JOIN plugin_svn_repositories AS repository ON (
                        repository.accessfile_id = accessfile.id
                        AND repository.id = $repository_id
                    )
                ";

        return $this->retrieveFirstRow($sql);
    }

    public function searchLastVersion($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_svn_accessfile_history
                WHERE repository_id = $repository_id
                ORDER BY version_number DESC
                LIMIT 1";

        return $this->retrieveFirstRow($sql);
    }

    public function searchById($id, $repository_id)
    {
        $id            = $this->da->escapeInt($id);
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_svn_accessfile_history
                WHERE id = $id
                  AND repository_id = $repository_id";

        return $this->retrieveFirstRow($sql);
    }

    public function searchByVersionNumber($version_number, $repository_id)
    {
        $version_number = $this->da->escapeInt($version_number);
        $repository_id  = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_svn_accessfile_history
                WHERE version_number = $version_number
                  AND repository_id = $repository_id";

        return $this->retrieveFirstRow($sql);
    }


    public function useAVersion($repository_id, $version_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $version_id    = $this->da->escapeInt($version_id);

        $sql = "UPDATE plugin_svn_repositories AS repository
                    INNER JOIN plugin_svn_accessfile_history AS accessfile ON (
                        repository.id = accessfile.repository_id
                        AND accessfile.id = $version_id
                    )
                SET repository.accessfile_id = accessfile.id";

        return $this->update($sql);
    }
}
