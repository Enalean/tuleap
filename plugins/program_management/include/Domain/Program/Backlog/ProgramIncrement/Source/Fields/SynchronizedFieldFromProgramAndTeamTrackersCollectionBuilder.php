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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProjectFromTracker;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;

final class SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder
{
    public function __construct(
        private GatherSynchronizedFields $gatherer,
        private LogMessage $logger,
        private RetrieveTrackerFromField $retrieve_tracker_from_field,
        private VerifyFieldPermissions $retrieve_field_permission,
        private RetrieveProjectFromTracker $retrieve_project_from_tracker,
    ) {
    }

    /**
     * @throws FieldSynchronizationException
     */
    public function buildFromSourceTrackers(
        SourceTrackerCollection $source_tracker_collection,
        ConfigurationErrorsCollector $errors_collector,
    ): SynchronizedFieldFromProgramAndTeamTrackersCollection {
        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection(
            $this->logger,
            $this->retrieve_tracker_from_field,
            $this->retrieve_field_permission,
            $this->retrieve_project_from_tracker
        );
        foreach ($source_tracker_collection->getSourceTrackers() as $source_tracker) {
            $collection->add(
                new SynchronizedFieldFromProgramAndTeamTrackers(SynchronizedFieldReferences::fromTrackerIdentifier($this->gatherer, $source_tracker, $errors_collector))
            );
        }

        return $collection;
    }
}
