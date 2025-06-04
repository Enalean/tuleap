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

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\RetrieveFullArtifactLinkField;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\RetrieveFullTracker;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ArtifactLinkFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DurationFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\MissingTimeFrameFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoArtifactLinkFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldHasIncorrectTypeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class SynchronizedFieldsGatherer implements GatherSynchronizedFields
{
    public function __construct(
        private RetrieveFullTracker $tracker_retriever,
        private \Tuleap\Tracker\Semantic\Title\TrackerSemanticTitleFactory $title_factory,
        private \Tuleap\Tracker\Semantic\Description\TrackerSemanticDescriptionFactory $description_factory,
        private \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory $status_factory,
        private SemanticTimeframeBuilder $timeframe_builder,
        private RetrieveFullArtifactLinkField $artifact_link_retriever,
    ) {
    }

    public function getTitleField(TrackerIdentifier $tracker_identifier, ?ConfigurationErrorsCollector $errors_collector): TitleFieldReference
    {
        $full_tracker = $this->tracker_retriever->getNonNullTracker($tracker_identifier);
        $title_field  = $this->title_factory->getByTracker($full_tracker)->getField();
        if (! $title_field) {
            throw new FieldRetrievalException($tracker_identifier->getId(), 'title');
        }
        if (! $title_field instanceof \Tracker_FormElement_Field_String) {
            $errors_collector?->addTitleHasIncorrectType(
                '/plugins/tracker/?tracker=' . urlencode((string) $full_tracker->getId()) . '&func=admin-semantic&semantic=title',
                TrackerReferenceProxy::fromTracker($full_tracker),
                $full_tracker->getProject()->getPublicName(),
                $title_field->getLabel()
            );
            throw new TitleFieldHasIncorrectTypeException($tracker_identifier->getId(), $title_field->getId());
        }
        return TitleFieldReferenceProxy::fromTrackerField($title_field);
    }

    public function getDescriptionField(TrackerIdentifier $tracker_identifier): DescriptionFieldReference
    {
        $full_tracker      = $this->tracker_retriever->getNonNullTracker($tracker_identifier);
        $description_field = $this->description_factory->getByTracker($full_tracker)->getField();
        if (! $description_field) {
            throw new FieldRetrievalException($tracker_identifier->getId(), 'description');
        }
        return DescriptionFieldReferenceProxy::fromTrackerField($description_field);
    }

    public function getStatusField(TrackerIdentifier $tracker_identifier): StatusFieldReference
    {
        $full_tracker = $this->tracker_retriever->getNonNullTracker($tracker_identifier);
        $status_field = $this->status_factory->getByTracker($full_tracker)->getField();
        if (! $status_field) {
            throw new FieldRetrievalException($tracker_identifier->getId(), 'status');
        }
        return StatusFieldReferenceProxy::fromTrackerField($status_field);
    }

    public function getStartDateField(TrackerIdentifier $tracker_identifier): StartDateFieldReference
    {
        $full_tracker     = $this->tracker_retriever->getNonNullTracker($tracker_identifier);
        $start_date_field = $this->timeframe_builder->getSemantic($full_tracker)->getStartDateField();
        if (! $start_date_field) {
            throw new MissingTimeFrameFieldException($tracker_identifier->getId(), 'start date');
        }
        return StartDateFieldReferenceProxy::fromTrackerField($start_date_field);
    }

    public function getEndPeriodField(TrackerIdentifier $tracker_identifier): EndDateFieldReference|DurationFieldReference
    {
        $full_tracker   = $this->tracker_retriever->getNonNullTracker($tracker_identifier);
        $semantic       = $this->timeframe_builder->getSemantic($full_tracker);
        $duration_field = $semantic->getDurationField();
        if ($duration_field !== null) {
            return DurationFieldReferenceProxy::fromTrackerField($duration_field);
        }
        $end_date_field = $semantic->getEndDateField();
        if ($end_date_field !== null) {
            return EndDateFieldReferenceProxy::fromTrackerField($end_date_field);
        }

        throw new MissingTimeFrameFieldException($tracker_identifier->getId(), 'end date or duration');
    }

    public function getArtifactLinkField(TrackerIdentifier $tracker_identifier, ?ConfigurationErrorsCollector $errors_collector): ArtifactLinkFieldReference
    {
        $full_tracker        = $this->tracker_retriever->getNonNullTracker($tracker_identifier);
        $artifact_link_field = $this->artifact_link_retriever->getArtifactLinkField($tracker_identifier);
        if (! $artifact_link_field) {
            $errors_collector?->addMissingFieldArtifactLink(
                '/plugins/tracker/?tracker=' . urlencode((string) $full_tracker->getId()) . '&func=admin-formElements',
                TrackerReferenceProxy::fromTracker($full_tracker),
                $full_tracker->getProject()->getPublicName(),
            );
            throw new NoArtifactLinkFieldException($tracker_identifier);
        }
        return ArtifactLinkFieldReferenceProxy::fromTrackerField($artifact_link_field);
    }
}
