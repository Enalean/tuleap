<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

class AddInPlaceRetriever
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(\Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    public function retrieveAddInPlace(
        TaskboardTracker $taskboard_tracker,
        \PFUser $user,
        MappedFieldsCollection $mapped_fields_collection
    ): ?AddInPlace {
        $tracker        = $taskboard_tracker->getTracker();
        $child_trackers = $tracker->getChildren();

        if (count($child_trackers) !== 1) {
            return null;
        }

        $child_tracker = $child_trackers[0];
        if (! $mapped_fields_collection->hasKey($child_tracker)) {
            return null;
        }
        $mapped_field = $mapped_fields_collection->get($child_tracker);
        if (! $mapped_field->userCanSubmit($user)) {
            return null;
        }

        $field_title = \Tracker_Semantic_Title::load($child_tracker)->getField();
        if (! $field_title || ! $field_title->userCanSubmit($user)) {
            return null;
        }

        if (! $this->areTitleAndMappedFieldTheOnlyRequiredField($child_tracker, $field_title, $mapped_field)) {
            return null;
        }

        $parent_artifact_link_field = $this->form_element_factory->getAnArtifactLinkField($user, $tracker);
        if (! $parent_artifact_link_field || ! $parent_artifact_link_field->userCanUpdate($user)) {
            return null;
        }

        return new AddInPlace(
            $child_tracker,
            $parent_artifact_link_field
        );
    }

    private function areTitleAndMappedFieldTheOnlyRequiredField(
        \Tracker $tracker,
        \Tracker_FormElement_Field $field_title,
        \Tracker_FormElement_Field_Selectbox $mapped_field
    ): bool {
        $title_field_id  = $field_title->getId();
        $mapped_field_id = $mapped_field->getId();
        $tracker_fields  = $this->form_element_factory->getUsedFields($tracker);

        foreach ($tracker_fields as $field) {
            \assert($field instanceof \Tracker_FormElement_Field);
            if ($field->getId() === $title_field_id) {
                continue;
            }
            if ($field->getId() === $mapped_field_id) {
                continue;
            }
            if ($field->isRequired()) {
                return false;
            }
        }

        return true;
    }
}
