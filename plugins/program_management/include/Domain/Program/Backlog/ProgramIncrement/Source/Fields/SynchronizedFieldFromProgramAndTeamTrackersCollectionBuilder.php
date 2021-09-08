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

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;

final class SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder
{
    public function __construct(
        private GatherSynchronizedFields $gatherer,
        private LoggerInterface $logger,
        private RetrieveTrackerFromField $retrieve_tracker_from_field,
        private VerifyFieldPermissions $retrieve_field_permission
    ) {
    }

    /**
     * @throws FieldSynchronizationException
     */
    public function buildFromSourceTrackers(
        SourceTrackerCollection $source_tracker_collection
    ): SynchronizedFieldFromProgramAndTeamTrackersCollection {
        $collection = new SynchronizedFieldFromProgramAndTeamTrackersCollection(
            $this->logger,
            $this->retrieve_tracker_from_field,
            $this->retrieve_field_permission
        );
        foreach ($source_tracker_collection->getSourceTrackers() as $source_tracker) {
            $collection->add(
                new SynchronizedFieldFromProgramAndTeamTrackers(SynchronizedFieldReferences::fromTrackerIdentifier($this->gatherer, $source_tracker))
            );
        }

        return $collection;
    }
}
