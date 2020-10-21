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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields;

use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\MissingTimeFrameFieldException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Description\NoDescriptionFieldException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Status\NoStatusFieldException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Title\NoTitleFieldException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Title\TitleFieldHasIncorrectTypeException;
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
    public function gather(\Tracker $source_tracker): SynchronizedFields
    {
        return new SynchronizedFields(
            $this->getArtifactLinkField($source_tracker),
            $this->getTitleField($source_tracker),
            $this->getDescriptionField($source_tracker),
            $this->getStatusField($source_tracker),
            $this->getTimeFrameFields($source_tracker)
        );
    }

    /**
     * @throws NoArtifactLinkFieldException
     */
    private function getArtifactLinkField(\Tracker $source_tracker): \Tracker_FormElement_Field_ArtifactLink
    {
        $artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($source_tracker);
        if (count($artifact_link_fields) > 0) {
            return $artifact_link_fields[0];
        }
        throw new NoArtifactLinkFieldException($source_tracker->getId());
    }

    /**
     * @throws NoTitleFieldException
     * @throws TitleFieldHasIncorrectTypeException
     */
    private function getTitleField(\Tracker $source_tracker): \Tracker_FormElement_Field_Text
    {
        $title_field = $this->title_factory->getByTracker($source_tracker)->getField();
        if (! $title_field) {
            throw new NoTitleFieldException($source_tracker->getId());
        }

        if (! $title_field instanceof \Tracker_FormElement_Field_String) {
            throw new TitleFieldHasIncorrectTypeException((int) $source_tracker->getId(), (int) $title_field->getId());
        }
        return $title_field;
    }

    /**
     * @throws NoDescriptionFieldException
     */
    public function getDescriptionField(\Tracker $source_tracker): \Tracker_FormElement_Field_Text
    {
        $description_field = $this->description_factory->getByTracker($source_tracker)->getField();
        if (! $description_field) {
            throw new NoDescriptionFieldException($source_tracker->getId());
        }
        return $description_field;
    }

    /**
     * @throws NoStatusFieldException
     */
    private function getStatusField(\Tracker $source_tracker): \Tracker_FormElement_Field_List
    {
        $status_field = $this->status_factory->getByTracker($source_tracker)->getField();
        if (! $status_field) {
            throw new NoStatusFieldException($source_tracker->getId());
        }
        return $status_field;
    }

    /**
     * @throws MissingTimeFrameFieldException
     */
    private function getTimeFrameFields(\Tracker $source_tracker): TimeframeFields
    {
        $semantic         = $this->timeframe_builder->getSemantic($source_tracker);
        $start_date_field = $semantic->getStartDateField();
        if (! $start_date_field) {
            throw new MissingTimeFrameFieldException($source_tracker->getId(), 'start date');
        }
        $duration_field = $semantic->getDurationField();
        if ($duration_field !== null) {
            return TimeframeFields::fromStartDateAndDuration($start_date_field, $duration_field);
        }
        $end_date_field = $semantic->getEndDateField();
        if ($end_date_field !== null) {
            return TimeframeFields::fromStartAndEndDates($start_date_field, $end_date_field);
        }
        throw new MissingTimeFrameFieldException($source_tracker->getId(), 'end date or duration');
    }
}
