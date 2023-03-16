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

namespace Tuleap\MediawikiStandalone\Instance;

use Tuleap\DB\DataAccessObject;

final class OngoingInitializationsDao extends DataAccessObject implements OngoingInitializationsState, CheckOngoingInitializationsError
{
    public function startInitialization(int $project_id): void
    {
        $this->getDB()->insertIgnore(
            'plugin_mediawiki_standalone_ongoing_initializations',
            ['project_id' => $project_id]
        );
    }

    public function isOngoingMigration(int $project_id): bool
    {
        return $this->getDB()->exists(
            "SELECT 1 FROM plugin_mediawiki_standalone_ongoing_initializations WHERE project_id = ?",
            $project_id
        );
    }

    public function isInError(int $project_id): bool
    {
        return $this->getDB()->exists(
            "SELECT 1 FROM plugin_mediawiki_standalone_ongoing_initializations WHERE project_id = ? AND is_error = TRUE",
            $project_id
        );
    }

    public function markAsError(int $project_id): void
    {
        $this->getDB()->update(
            'plugin_mediawiki_standalone_ongoing_initializations',
            ['is_error' => true],
            ['project_id' => $project_id]
        );
    }
}
