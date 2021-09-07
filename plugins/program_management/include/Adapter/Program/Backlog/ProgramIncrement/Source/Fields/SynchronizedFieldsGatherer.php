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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ArtifactLinkFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndPeriodFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\MissingTimeFrameFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoArtifactLinkFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldHasIncorrectTypeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class SynchronizedFieldsGatherer implements GatherSynchronizedFields
{
    public function __construct(
        private \TrackerFactory $tracker_factory,
        private \Tracker_Semantic_TitleFactory $title_factory,
        private \Tracker_Semantic_DescriptionFactory $description_factory,
        private \Tracker_Semantic_StatusFactory $status_factory,
        private SemanticTimeframeBuilder $timeframe_builder,
        private \Tracker_FormElementFactory $form_element_factory
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

    public function getDescriptionField(ProgramIncrementTrackerIdentifier $program_increment): DescriptionFieldReference
    {
        $full_tracker      = $this->getFullTracker($program_increment);
        $description_field = $this->description_factory->getByTracker($full_tracker)->getField();
        if (! $description_field) {
            throw new FieldRetrievalException($program_increment->id, 'description');
        }
        return DescriptionFieldReferenceProxy::fromTrackerField($description_field);
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

    public function getStartDateField(ProgramIncrementTrackerIdentifier $program_increment): StartDateFieldReference
    {
        $full_tracker     = $this->getFullTracker($program_increment);
        $start_date_field = $this->timeframe_builder->getSemantic($full_tracker)->getStartDateField();
        if (! $start_date_field) {
            throw new MissingTimeFrameFieldException($program_increment->id, 'start date');
        }
        return StartDateFieldReferenceProxy::fromTrackerField($start_date_field);
    }

    public function getEndPeriodField(ProgramIncrementTrackerIdentifier $program_increment): EndPeriodFieldReference
    {
        $full_tracker   = $this->getFullTracker($program_increment);
        $semantic       = $this->timeframe_builder->getSemantic($full_tracker);
        $duration_field = $semantic->getDurationField();
        if ($duration_field !== null) {
            return EndPeriodFieldReferenceProxy::fromTrackerField($duration_field);
        }
        $end_date_field = $semantic->getEndDateField();
        if ($end_date_field !== null) {
            return EndPeriodFieldReferenceProxy::fromTrackerField($end_date_field);
        }
        throw new MissingTimeFrameFieldException($program_increment->id, 'end date or duration');
    }

    public function getArtifactLinkField(
        ProgramIncrementTrackerIdentifier $program_increment
    ): ArtifactLinkFieldReference {
        $full_tracker         = $this->getFullTracker($program_increment);
        $artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($full_tracker);
        if (count($artifact_link_fields) > 0) {
            return ArtifactLinkFieldReferenceProxy::fromTrackerField(($artifact_link_fields[0]));
        }
        throw new NoArtifactLinkFieldException($program_increment->id);
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
