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

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
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
use Tuleap\ProgramManagement\Domain\TrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;
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

    public function getTitleField(TrackerIdentifier $tracker_identifier, ?ConfigurationErrorsCollector $errors_collector): TitleFieldReference
    {
        $full_tracker = $this->getFullTracker($tracker_identifier);
        $title_field  = $this->title_factory->getByTracker($full_tracker)->getField();
        if (! $title_field) {
            throw new FieldRetrievalException($tracker_identifier->getId(), 'title');
        }
        if (! $title_field instanceof \Tracker_FormElement_Field_String) {
            $errors_collector?->addTitleHasIncorrectType(
                "/plugins/tracker/?tracker=" . urlencode((string) $full_tracker->getId()) . "&func=admin-semantic&semantic=title",
                $full_tracker->getName(),
                $full_tracker->getProject()->getPublicName(),
                $title_field->getLabel()
            );
            throw new TitleFieldHasIncorrectTypeException($tracker_identifier->getId(), $title_field->getId());
        }
        return TitleFieldReferenceProxy::fromTrackerField($title_field);
    }

    public function getDescriptionField(TrackerIdentifier $tracker_identifier): DescriptionFieldReference
    {
        $full_tracker      = $this->getFullTracker($tracker_identifier);
        $description_field = $this->description_factory->getByTracker($full_tracker)->getField();
        if (! $description_field) {
            throw new FieldRetrievalException($tracker_identifier->getId(), 'description');
        }
        return DescriptionFieldReferenceProxy::fromTrackerField($description_field);
    }

    public function getStatusField(TrackerIdentifier $tracker_identifier): StatusFieldReference
    {
        $full_tracker = $this->getFullTracker($tracker_identifier);
        $status_field = $this->status_factory->getByTracker($full_tracker)->getField();
        if (! $status_field) {
            throw new FieldRetrievalException($tracker_identifier->getId(), 'status');
        }
        return StatusFieldReferenceProxy::fromTrackerField($status_field);
    }

    public function getStartDateField(TrackerIdentifier $tracker_identifier): StartDateFieldReference
    {
        $full_tracker     = $this->getFullTracker($tracker_identifier);
        $start_date_field = $this->timeframe_builder->getSemantic($full_tracker)->getStartDateField();
        if (! $start_date_field) {
            throw new MissingTimeFrameFieldException($tracker_identifier->getId(), 'start date');
        }
        return StartDateFieldReferenceProxy::fromTrackerField($start_date_field);
    }

    public function getEndPeriodField(TrackerIdentifier $tracker_identifier): EndPeriodFieldReference
    {
        $full_tracker   = $this->getFullTracker($tracker_identifier);
        $semantic       = $this->timeframe_builder->getSemantic($full_tracker);
        $duration_field = $semantic->getDurationField();
        if ($duration_field !== null) {
            return EndPeriodFieldReferenceProxy::fromTrackerField($duration_field);
        }
        $end_date_field = $semantic->getEndDateField();
        if ($end_date_field !== null) {
            return EndPeriodFieldReferenceProxy::fromTrackerField($end_date_field);
        }

        throw new MissingTimeFrameFieldException($tracker_identifier->getId(), 'end date or duration');
    }

    public function getArtifactLinkField(TrackerIdentifier $tracker_identifier): ArtifactLinkFieldReference
    {
        $full_tracker         = $this->getFullTracker($tracker_identifier);
        $artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($full_tracker);
        if (count($artifact_link_fields) > 0) {
            return ArtifactLinkFieldReferenceProxy::fromTrackerField(($artifact_link_fields[0]));
        }
        throw new NoArtifactLinkFieldException($tracker_identifier->getId());
    }

    private function getFullTracker(TrackerIdentifier $tracker_identifier): \Tracker
    {
        $full_tracker = $this->tracker_factory->getTrackerById($tracker_identifier->getId());
        if (! $full_tracker) {
            throw new TrackerNotFoundException($tracker_identifier->getId());
        }
        return $full_tracker;
    }
}
