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

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;
use Tuleap\DB\DBConnection;
use Tuleap\MediawikiStandalone\Service\MediawikiFlavorUsage;

final class OngoingInitializationsDao extends DataAccessObject implements OngoingInitializationsState, CheckOngoingInitializationStatus
{
    public function __construct(private readonly MediawikiFlavorUsage $flavor_usage, ?DBConnection $db_connection = null)
    {
        parent::__construct($db_connection);
    }

    #[\Override]
    public function startInitialization(\Project $project): void
    {
        $this->getDB()->insertIgnore(
            'plugin_mediawiki_standalone_ongoing_initializations',
            ['project_id' => $project->getID()]
        );
    }

    #[\Override]
    public function finishInitialization(\Project $project): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($project): void {
                $project_id = $project->getID();
                $db->delete(
                    'plugin_mediawiki_standalone_ongoing_initializations',
                    ['project_id' => $project_id]
                );
                if ($this->flavor_usage->wasLegacyMediawikiUsed($project)) {
                    $db->delete('plugin_mediawiki_database', ['project_id' => $project_id]);
                }
            }
        );
    }

    #[\Override]
    public function markAsError(\Project $project): void
    {
        $this->getDB()->update(
            'plugin_mediawiki_standalone_ongoing_initializations',
            ['is_error' => true],
            ['project_id' => $project->getID()]
        );
    }

    #[\Override]
    public function getStatus(\Project $project): OngoingInitializationStatus
    {
        $row = $this->getDB()->row(
            'SELECT is_error FROM plugin_mediawiki_standalone_ongoing_initializations WHERE project_id = ?',
            $project->getID(),
        );

        if (! $row) {
            return OngoingInitializationStatus::None;
        }

        return $row['is_error']
            ? OngoingInitializationStatus::InError
            : OngoingInitializationStatus::Ongoing;
    }
}
