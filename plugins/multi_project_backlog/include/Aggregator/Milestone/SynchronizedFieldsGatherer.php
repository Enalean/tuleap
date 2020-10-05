<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone;

use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

class SynchronizedFieldsGatherer
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var \Tracker_Semantic_TitleFactory
     */
    private $title_factory;
    /**
     * @var \Tracker_Semantic_DescriptionFactory
     */
    private $description_factory;
    /**
     * @var \Tracker_Semantic_StatusFactory
     */
    private $status_factory;
    /**
     * @var SemanticTimeframeBuilder
     */
    private $timeframe_builder;

    public function __construct(
        \Tracker_FormElementFactory $form_element_factory,
        \Tracker_Semantic_TitleFactory $title_factory,
        \Tracker_Semantic_DescriptionFactory $description_factory,
        \Tracker_Semantic_StatusFactory $status_factory,
        SemanticTimeframeBuilder $timeframe_builder
    ) {
        $this->form_element_factory = $form_element_factory;
        $this->title_factory        = $title_factory;
        $this->description_factory  = $description_factory;
        $this->status_factory       = $status_factory;
        $this->timeframe_builder    = $timeframe_builder;
    }

    /**
     * @throws SynchronizedFieldRetrievalException
     */
    public function gather(\Tracker $contributor_milestone_tracker): SynchronizedFields
    {
        return new SynchronizedFields(
            $this->getArtifactLinkField($contributor_milestone_tracker),
            $this->getTitleField($contributor_milestone_tracker),
            $this->getDescriptionField($contributor_milestone_tracker),
            $this->getStatusField($contributor_milestone_tracker),
            $this->getTimeFrameFields($contributor_milestone_tracker)
        );
    }

    /**
     * @throws NoArtifactLinkFieldException
     */
    private function getArtifactLinkField(\Tracker $milestone_tracker): \Tracker_FormElement_Field_ArtifactLink
    {
        $artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($milestone_tracker);
        if (count($artifact_link_fields) > 0) {
            return $artifact_link_fields[0];
        }
        throw new NoArtifactLinkFieldException($milestone_tracker->getId());
    }

    /**
     * @throws NoTitleFieldException
     */
    private function getTitleField(\Tracker $milestone_tracker): \Tracker_FormElement_Field_Text
    {
        $title_field = $this->title_factory->getByTracker($milestone_tracker)->getField();
        if (! $title_field) {
            throw new NoTitleFieldException($milestone_tracker->getId());
        }
        return $title_field;
    }

    /**
     * @throws NoDescriptionFieldException
     */
    public function getDescriptionField(\Tracker $milestone_tracker): \Tracker_FormElement_Field_Text
    {
        $description_field = $this->description_factory->getByTracker($milestone_tracker)->getField();
        if (! $description_field) {
            throw new NoDescriptionFieldException($milestone_tracker->getId());
        }
        return $description_field;
    }

    /**
     * @throws NoStatusFieldException
     */
    private function getStatusField(\Tracker $milestone_tracker): \Tracker_FormElement_Field_List
    {
        $status_field = $this->status_factory->getByTracker($milestone_tracker)->getField();
        if (! $status_field) {
            throw new NoStatusFieldException($milestone_tracker->getId());
        }
        return $status_field;
    }

    /**
     * @throws MissingTimeFrameFieldException
     */
    private function getTimeFrameFields(\Tracker $milestone_tracker): TimeframeFields
    {
        $semantic         = $this->timeframe_builder->getSemantic($milestone_tracker);
        $start_date_field = $semantic->getStartDateField();
        if (! $start_date_field) {
            throw new MissingTimeFrameFieldException($milestone_tracker->getId(), 'start date');
        }
        $duration_field = $semantic->getDurationField();
        if ($duration_field !== null) {
            return TimeframeFields::fromStartDateAndDuration($start_date_field, $duration_field);
        }
        $end_date_field = $semantic->getEndDateField();
        if ($end_date_field !== null) {
            return TimeframeFields::fromStartAndEndDates($start_date_field, $end_date_field);
        }
        throw new MissingTimeFrameFieldException($milestone_tracker->getId(), 'end date or duration');
    }
}
