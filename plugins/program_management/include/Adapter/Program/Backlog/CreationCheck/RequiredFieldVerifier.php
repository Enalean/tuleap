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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifyRequiredFieldsLimitedToSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProjectFromTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveTrackerFromField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;

final class RequiredFieldVerifier implements VerifyRequiredFieldsLimitedToSynchronizedFields
{
    private \TrackerFactory $tracker_factory;

    public function __construct(\TrackerFactory $tracker_factory)
    {
        $this->tracker_factory = $tracker_factory;
    }

    #[\Override]
    public function areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
        TrackerCollection $trackers,
        SynchronizedFieldFromProgramAndTeamTrackersCollection $field_collection,
        ConfigurationErrorsCollector $errors_collector,
        RetrieveTrackerFromField $retrieve_tracker_from_field,
        RetrieveProjectFromTracker $retrieve_project_from_tracker,
    ): bool {
        $are_fields_ok = true;
        foreach ($trackers->getTrackers() as $program_increment_tracker) {
            $full_tracker = $this->tracker_factory->getTrackerById($program_increment_tracker->getId());
            if (! $full_tracker) {
                throw new \RuntimeException('Tracker with id #' . $program_increment_tracker->getId() . ' is not found.');
            }
            foreach ($full_tracker->getFormElementFields() as $field) {
                if ($field->isRequired() && ! $field_collection->isFieldIdSynchronized($field->getId())) {
                    $tracker_reference = $retrieve_tracker_from_field->fromFieldId($field->getId());
                    $project_reference = $retrieve_project_from_tracker->fromTrackerReference($tracker_reference);
                    $errors_collector->addRequiredFieldError(
                        $tracker_reference,
                        $project_reference,
                        $field->getId(),
                        $field->getLabel(),
                    );
                    $are_fields_ok = false;
                    if (! $errors_collector->shouldCollectAllIssues()) {
                        return $are_fields_ok;
                    }
                }
            }
        }

        return $are_fields_ok;
    }
}
