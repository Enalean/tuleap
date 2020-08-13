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
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;

class SynchronizedFieldCollectionBuilder
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
     * @throws TimeframeBrokenConfigurationException
     */
    public function buildFromMilestoneTrackers(
        MilestoneTrackerCollection $milestone_tracker_collection,
        \PFUser $user
    ): SynchronizedFieldCollection {
        $fields = [];
        foreach ($milestone_tracker_collection->getMilestoneTrackers() as $milestone_tracker) {
            $fields[] = $this->addArtifactLinkField($milestone_tracker, $user);
            $fields[] = $this->addTitleField($milestone_tracker);
            $fields[] = $this->addDescriptionField($milestone_tracker);
            $fields[] = $this->addStatusField($milestone_tracker);
            foreach ($this->getTimeFrameFields($milestone_tracker) as $time_frame_field) {
                $fields[] = $time_frame_field;
            }
        }
        return new SynchronizedFieldCollection($fields);
    }

    /**
     * @throws NoArtifactLinkFieldException
     */
    private function addArtifactLinkField(\Tracker $milestone_tracker, \PFUser $user): \Tracker_FormElement_Field
    {
        $artifact_link_field = $this->form_element_factory->getAnArtifactLinkField($user, $milestone_tracker);
        if (! $artifact_link_field) {
            throw new NoArtifactLinkFieldException((int) $milestone_tracker->getId());
        }
        return $artifact_link_field;
    }

    /**
     * @throws NoTitleFieldException
     */
    private function addTitleField(\Tracker $milestone_tracker): \Tracker_FormElement_Field
    {
        $title_field = $this->title_factory->getByTracker($milestone_tracker)->getField();
        if (! $title_field) {
            throw new NoTitleFieldException((int) $milestone_tracker->getId());
        }
        return $title_field;
    }

    /**
     * @throws NoDescriptionFieldException
     */
    public function addDescriptionField(\Tracker $milestone_tracker): \Tracker_FormElement_Field
    {
        $description_field = $this->description_factory->getByTracker($milestone_tracker)->getField();
        if (! $description_field) {
            throw new NoDescriptionFieldException((int) $milestone_tracker->getId());
        }
        return $description_field;
    }

    /**
     * @throws NoStatusFieldException
     */
    public function addStatusField(\Tracker $milestone_tracker): \Tracker_FormElement_Field
    {
        $status_field = $this->status_factory->getByTracker($milestone_tracker)->getField();
        if (! $status_field) {
            throw new NoStatusFieldException((int) $milestone_tracker->getId());
        }
        return $status_field;
    }

    /**
     * @return \Tracker_FormElement_Field[]
     * @throws TimeframeBrokenConfigurationException
     */
    public function getTimeFrameFields(\Tracker $milestone_tracker): array
    {
        $semantic         = $this->timeframe_builder->getSemantic($milestone_tracker);
        $start_date_field = $semantic->getStartDateField();
        if (! $start_date_field) {
            throw new MissingTimeFrameFieldException((int) $milestone_tracker->getId(), 'start date');
        }
        $other_field = $semantic->getDurationField() ?: $semantic->getEndDateField();
        if (! $other_field) {
            throw new MissingTimeFrameFieldException((int) $milestone_tracker->getId(), 'end date or duration');
        }
        return [$start_date_field, $other_field];
    }
}
