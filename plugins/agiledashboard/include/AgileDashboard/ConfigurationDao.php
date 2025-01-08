<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use Tuleap\DB\DataAccessObject;

class ConfigurationDao extends DataAccessObject
{
    public function updateConfiguration(int $project_id, bool $scrum_is_activated): void
    {
        $sql = <<<SQL
        REPLACE INTO plugin_agiledashboard_configuration (project_id, scrum)
            VALUES (?, ?)
        SQL;

        $this->getDB()->run($sql, $project_id, $scrum_is_activated);
    }

    public function duplicate(int $project_id, int $template_id): void
    {
        $sql = <<<SQL
        INSERT INTO plugin_agiledashboard_configuration (project_id, scrum)
            SELECT ?, scrum
            FROM plugin_agiledashboard_configuration
            WHERE project_id = ?
        SQL;

        $this->getDB()->run($sql, $project_id, $template_id);
    }

    public function isScrumActivated(int $project_id): bool
    {
        $sql = <<<SQL
        SELECT scrum
        FROM plugin_agiledashboard_configuration
        WHERE project_id = ?
        SQL;

        return $this->getDB()->single($sql, [$project_id]) !== 0;
    }
}
