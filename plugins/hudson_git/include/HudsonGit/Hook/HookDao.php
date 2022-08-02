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

declare(strict_types=1);

namespace Tuleap\HudsonGit\Hook;

use Tuleap\DB\DataAccessObject;

class HookDao extends DataAccessObject
{
    public function delete(int $repository_id): void
    {
        $sql = 'DELETE FROM plugin_hudson_git_server WHERE repository_id = ?';

        $this->getDB()->run($sql, $repository_id);
    }

    public function save(int $id, string $jenkins_server, bool $is_commit_reference_needed): void
    {
        $sql = 'REPLACE INTO plugin_hudson_git_server(repository_id, jenkins_server_url, is_commit_reference_needed)
                VALUES(?, ?, ?)';

        $this->getDB()->run($sql, $id, $jenkins_server, $is_commit_reference_needed ? 1 : 0);
    }

    /**
     * @psalm-return array{jenkins_server_url: string, is_commit_reference_needed:0|1}|null
     */
    public function searchById(int $id): ?array
    {
        $sql = 'SELECT jenkins_server_url, is_commit_reference_needed
                FROM plugin_hudson_git_server
                WHERE repository_id = ?';

        return $this->getDB()->row($sql, $id);
    }
}
