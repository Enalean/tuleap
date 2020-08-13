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

    public function __construct(
        \Tracker_FormElementFactory $form_element_factory,
        \Tracker_Semantic_TitleFactory $title_factory
    ) {
        $this->form_element_factory = $form_element_factory;
        $this->title_factory        = $title_factory;
    }

    /**
     * @throws NoArtifactLinkFieldException
     */
    public function buildFromMilestoneTrackers(
        MilestoneTrackerCollection $milestone_tracker_collection,
        \PFUser $user
    ): SynchronizedFieldCollection {
        $fields = [];
        foreach ($milestone_tracker_collection->getMilestoneTrackers() as $milestone_tracker) {
            $artifact_link_field = $this->form_element_factory->getAnArtifactLinkField($user, $milestone_tracker);
            if (! $artifact_link_field) {
                throw new NoArtifactLinkFieldException((int) $milestone_tracker->getId());
            }
            $fields[] = $artifact_link_field;
            $title_field = $this->title_factory->getByTracker($milestone_tracker)->getField();
            if (! $title_field) {
                throw new NoTitleFieldException((int) $milestone_tracker->getId());
            }
            $fields[] = $title_field;
        }
        return new SynchronizedFieldCollection($fields);
    }
}
