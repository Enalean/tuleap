<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Project\ProjectBackground;

use Tuleap\DB\DataAccessObject;

class ProjectBackgroundDao extends DataAccessObject
{
    public function getBackground(int $project_id): ?string
    {
        return $this->getDB()->cell(
            "SELECT background FROM project_background WHERE project_id = ?",
            $project_id
        ) ?: null;
    }

    public function setBackgroundByProjectID(int $project_id, string $background_identifier): void
    {
        $sql = 'INSERT INTO project_background(project_id, background) VALUES (?, ?) ON DUPLICATE KEY UPDATE background = ?';
        $this->getDB()->run($sql, $project_id, $background_identifier, $background_identifier);
    }

    public function deleteBackgroundByProjectID(int $project_id): void
    {
        $this->getDB()->run('DELETE FROM project_background WHERE project_id = ?', $project_id);
    }
}
