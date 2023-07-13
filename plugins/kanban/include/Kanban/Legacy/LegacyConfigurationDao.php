<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\Legacy;

final class LegacyConfigurationDao extends \Tuleap\DB\DataAccessObject implements LegacyKanbanActivator, LegacyKanbanRetriever, LegacyKanbanDeactivator
{
    public function activateKanban(int $project_id): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_kanban_legacy_configuration',
            [
                'project_id' => $project_id,
                'kanban' => 1,
            ],
            [
                'kanban',
            ],
        );
    }

    public function isKanbanActivated(int $project_id): bool
    {
        $sql = "SELECT kanban
                FROM plugin_kanban_legacy_configuration
                WHERE project_id = ?";

        return $this->getDB()->cell($sql, $project_id) === 1;
    }

    public function deactivateKanban(int $project_id): void
    {
        $this->getDB()->delete(
            'plugin_kanban_legacy_configuration',
            ['project_id' => $project_id]
        );
    }

    public function duplicate(int $project_id, int $template_id): void
    {
        $sql = "INSERT INTO plugin_kanban_legacy_configuration (project_id, kanban)
                SELECT ?, kanban
                FROM plugin_kanban_legacy_configuration
                WHERE project_id = ?";

        $this->getDB()->run($sql, $project_id, $template_id);
    }
}
