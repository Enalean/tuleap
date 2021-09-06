<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldHasIncorrectTypeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;

final class SynchronizedFieldsGatherer implements GatherSynchronizedFields
{
    public function __construct(
        private \TrackerFactory $tracker_factory,
        private \Tracker_Semantic_TitleFactory $title_factory,
        private \Tracker_Semantic_StatusFactory $status_factory
    ) {
    }

    public function getTitleField(ProgramIncrementTrackerIdentifier $program_increment): TitleFieldReference
    {
        $full_tracker = $this->getFullTracker($program_increment);
        $title_field  = $this->title_factory->getByTracker($full_tracker)->getField();
        if (! $title_field) {
            throw new FieldRetrievalException($program_increment->id, 'title');
        }
        if (! $title_field instanceof \Tracker_FormElement_Field_String) {
            throw new TitleFieldHasIncorrectTypeException($program_increment->id, $title_field->getId());
        }
        return TitleFieldReferenceProxy::fromTrackerField($title_field);
    }

    public function getStatusField(ProgramIncrementTrackerIdentifier $program_increment): StatusFieldReference
    {
        $full_tracker = $this->getFullTracker($program_increment);
        $status_field = $this->status_factory->getByTracker($full_tracker)->getField();
        if (! $status_field) {
            throw new FieldRetrievalException($program_increment->id, 'status');
        }
        return StatusFieldReferenceProxy::fromTrackerField($status_field);
    }

    private function getFullTracker(ProgramIncrementTrackerIdentifier $program_increment): \Tracker
    {
        $full_tracker = $this->tracker_factory->getTrackerById($program_increment->id);
        if (! $full_tracker) {
            throw new \RuntimeException(
                sprintf('Program Increment tracker with id #%s could not be found', $program_increment->id)
            );
        }
        return $full_tracker;
    }
}
