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
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CheckRequiredField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;

final class RequiredFieldChecker implements CheckRequiredField
{
    public function areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
        TrackerCollection $trackers,
        SynchronizedFieldFromProgramAndTeamTrackersCollection $field_collection,
        ConfigurationErrorsCollector $errors_collector
    ): bool {
        $are_fields_ok = true;
        foreach ($trackers->getTrackers() as $program_increment_tracker) {
            foreach ($program_increment_tracker->getFullTracker()->getFormElementFields() as $field) {
                if ($field->isRequired() && ! $field_collection->isFieldSynchronized($field)) {
                    $errors_collector->addRequiredFieldError(
                        $field->getTrackerId(),
                        $field->getId(),
                        $field->getLabel()
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
