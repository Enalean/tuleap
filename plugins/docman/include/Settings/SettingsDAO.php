<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Settings;

use Tuleap\Docman\FilenamePattern\FilenamePattern;

final class SettingsDAO extends \Tuleap\DB\DataAccessObject implements SearchFilenamePatternInSettings, DAOSettings, ForbidWritersDAOSettings
{
    /**
     * @return null|array{filename_pattern: string, is_filename_pattern_enforced: int}
     */
    #[\Override]
    public function searchFileNamePatternFromProjectId(int $project_id): ?array
    {
        $sql = 'SELECT filename_pattern, is_filename_pattern_enforced
                FROM plugin_docman_project_settings
                WHERE group_id = ?';

        return $this->getDB()->row($sql, $project_id);
    }

    #[\Override]
    public function saveFilenamePattern(int $project_id, FilenamePattern $filename_pattern): void
    {
        $this->getDB()->update(
            'plugin_docman_project_settings',
            [
                'filename_pattern'             => $filename_pattern->getPattern(),
                'is_filename_pattern_enforced' => $filename_pattern->isEnforced(),
            ],
            ['group_id' => $project_id]
        );
    }

    /**
     * @return null|array{forbid_writers_to_update: int, forbid_writers_to_delete: int}
     */
    #[\Override]
    public function searchByProjectId(int $project_id): ?array
    {
        $sql = 'SELECT forbid_writers_to_update, forbid_writers_to_delete FROM plugin_docman_project_settings WHERE group_id = ?';

        return $this->getDB()->row($sql, $project_id);
    }

    public function saveForbidWriters(
        int $project_id,
        bool $forbid_writers_to_update,
        bool $forbid_writers_to_delete,
    ): void {
        $this->getDB()->update(
            'plugin_docman_project_settings',
            [
                'forbid_writers_to_update' => (int) $forbid_writers_to_update,
                'forbid_writers_to_delete' => (int) $forbid_writers_to_delete,
            ],
            ['group_id' => $project_id]
        );
    }
}
