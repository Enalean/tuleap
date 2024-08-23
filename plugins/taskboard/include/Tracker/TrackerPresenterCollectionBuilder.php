<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Tracker;

use PFUser;
use Planning_Milestone;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;

class TrackerPresenterCollectionBuilder
{
    /** @var TrackerCollectionRetriever */
    private $trackers_retriever;
    /** @var MappedFieldRetriever */
    private $mapped_field_retriever;
    /** @var AddInPlaceRetriever */
    private $add_in_place_retriever;

    public function __construct(
        TrackerCollectionRetriever $trackers_retriever,
        MappedFieldRetriever $mapped_field_retriever,
        AddInPlaceRetriever $add_in_place_tracker_retriever,
    ) {
        $this->trackers_retriever     = $trackers_retriever;
        $this->mapped_field_retriever = $mapped_field_retriever;
        $this->add_in_place_retriever = $add_in_place_tracker_retriever;
    }

    /**
     * @return TrackerPresenter[]
     */
    public function buildCollection(Planning_Milestone $milestone, PFUser $user): array
    {
        $tracker_collection       = $this->trackers_retriever->getTrackersForMilestone($milestone);
        $mapped_fields_collection = $this->getMappedFieldsIndexedByTrackerId($tracker_collection);

        return $tracker_collection->map(
            function (TaskboardTracker $taskboard_tracker) use ($user, $mapped_fields_collection) {
                $mapped_field = null;
                $tracker      = $taskboard_tracker->getTracker();
                if ($mapped_fields_collection->hasKey($tracker)) {
                    $mapped_field = $mapped_fields_collection->get($tracker);
                }
                $title_field     = $this->getTitleField($taskboard_tracker, $user);
                $add_in_place    = $this->add_in_place_retriever->retrieveAddInPlace(
                    $taskboard_tracker,
                    $user,
                    $mapped_fields_collection
                );
                $assign_to_field = $this->getAssignToField($taskboard_tracker, $user);

                $add_in_place_presenter  = $add_in_place ? new AddInPlacePresenter($add_in_place) : null;
                $can_update_mapped_field = $mapped_field ? $mapped_field->userCanUpdate($user) : false;

                return new TrackerPresenter(
                    $taskboard_tracker,
                    $can_update_mapped_field,
                    $title_field,
                    $add_in_place_presenter,
                    $assign_to_field
                );
            }
        );
    }

    private function getTitleField(TaskboardTracker $taskboard_tracker, \PFUser $user): ?TitleFieldPresenter
    {
        $field_title = \Tracker_Semantic_Title::load($taskboard_tracker->getTracker())->getField();

        return ($field_title !== null && $field_title->userCanUpdate($user))
            ? new TitleFieldPresenter($field_title)
            : null;
    }

    private function getAssignToField(TaskboardTracker $taskboard_tracker, \PFUser $user): ?AssignedToFieldPresenter
    {
        $field_contributor = \Tracker_Semantic_Contributor::load($taskboard_tracker->getTracker())->getField();

        return ($field_contributor !== null && $field_contributor->userCanUpdate($user))
            ? new AssignedToFieldPresenter($field_contributor)
            : null;
    }

    private function getMappedFieldsIndexedByTrackerId(TrackerCollection $tracker_collection): MappedFieldsCollection
    {
        return $tracker_collection->reduce(
            function (MappedFieldsCollection $collection, TaskboardTracker $taskboard_tracker) {
                $this->mapped_field_retriever->getField($taskboard_tracker)->apply(
                    static fn($mapped_field) => $collection->put($taskboard_tracker->getTracker(), $mapped_field)
                );
                return $collection;
            },
            new MappedFieldsCollection()
        );
    }
}
