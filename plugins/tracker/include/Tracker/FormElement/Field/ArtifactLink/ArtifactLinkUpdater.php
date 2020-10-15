<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Exception;
use PFUser;
use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tracker_Artifact_PriorityManager;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_NoArtifactLinkFieldException;
use Tracker_NoChangeException;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactLinkUpdater
{
    /**
     * @var Tracker_Artifact_PriorityManager
     */
    private $priority_manager;

    public function __construct(Tracker_Artifact_PriorityManager $priority_manager)
    {
        $this->priority_manager = $priority_manager;
    }

    public function update(
        array $new_linked_artifact_ids,
        Artifact $artifact,
        PFUser $current_user,
        IFilterValidElementsToUnkink $filter,
        string $type
    ): void {
        $artlink_field = $artifact->getAnArtifactLinkField($current_user);
        if (! $artlink_field) {
            return;
        }

        $fields_data = $this->getFieldsDataForNewChangeset(
            $artlink_field,
            $artifact,
            $current_user,
            $filter,
            $new_linked_artifact_ids,
            $type
        );

        $this->unlinkAndLinkElements($artifact, $fields_data, $current_user, $new_linked_artifact_ids);
    }

    private function getFieldsDataForNewChangeset(
        Tracker_FormElement_Field_ArtifactLink $artlink_field,
        Artifact $artifact,
        PFUser $current_user,
        IFilterValidElementsToUnkink $filter,
        array $new_linked_artifact_ids,
        string $type
    ): array {
        $artifact_ids_already_linked = $this->getElementsAlreadyLinkedToArtifact($artifact, $current_user);

        $artifact_ids_to_be_unlinked = $this->getAllArtifactsToBeRemoved(
            $current_user,
            $filter,
            $artifact_ids_already_linked,
            $new_linked_artifact_ids
        );

        $artifact_ids_to_be_linked = $this->getElementsToLink(
            $artifact_ids_already_linked,
            $new_linked_artifact_ids
        );

        return $this->formatFieldDatas($artlink_field, $artifact_ids_to_be_linked, $artifact_ids_to_be_unlinked, $type);
    }

    /**
     * @return array<int>
     */
    private function getAllArtifactsToBeRemoved(
        PFUser $user,
        IFilterValidElementsToUnkink $filter,
        array $elements_already_linked,
        array $new_ids
    ): array {
        $artifacts_to_be_removed = $this->getAllLinkedArtifactsThatShouldBeRemoved($elements_already_linked, $new_ids);

        return $filter->filter($user, $artifacts_to_be_removed);
    }

    private function getAllLinkedArtifactsThatShouldBeRemoved(array $elements_already_linked, array $new_ids): array
    {
        return array_diff($elements_already_linked, $new_ids);
    }

    private function getElementsToLink(array $elements_already_linked, array $new_linked_artifact_ids): array
    {
        return array_diff($new_linked_artifact_ids, $elements_already_linked);
    }

    /**
     * @throws Tracker_NoArtifactLinkFieldException
     * @throws \Tracker_Exception
     */
    public function updateArtifactLinks(
        PFUser $user,
        Artifact $artifact,
        array $to_add,
        array $to_remove,
        string $type
    ): void {
        $artifact_link_field = $artifact->getAnArtifactLinkField($user);
        if (! $artifact_link_field) {
            throw new Tracker_NoArtifactLinkFieldException('Missing artifact link field');
        }

        try {
            $fields_data = $this->formatFieldDatas(
                $artifact_link_field,
                $to_add,
                $to_remove,
                $type
            );
            $artifact->createNewChangeset($fields_data, '', $user, false);
        } catch (Tracker_NoChangeException $exception) {
        }
    }

    private function unlinkAndLinkElements(
        Artifact $artifact,
        array $fields_data,
        PFUser $current_user,
        array $linked_artifact_ids
    ): void {
        try {
            $artifact->createNewChangeset($fields_data, '', $current_user, false);
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing. Just need to reorder the items
        } catch (Exception $exception) {
            return;
        }

        $this->setOrderWithoutHistoryChangeLogging($linked_artifact_ids);
    }

    public function getElementsAlreadyLinkedToArtifact(Artifact $artifact, PFUser $user): array
    {
        return array_map(
            function (Artifact $artifact) {
                return $artifact->getId();
            },
            $artifact->getLinkedArtifacts($user)
        );
    }

    private function setOrderWithoutHistoryChangeLogging(array $linked_artifact_ids): void
    {
        $predecessor = null;

        foreach ($linked_artifact_ids as $linked_artifact_id) {
            if (isset($predecessor)) {
                try {
                    $this->priority_manager->moveArtifactAfter($linked_artifact_id, $predecessor);
                } catch (Tracker_Artifact_Exception_CannotRankWithMyself $exception) {
                    throw new ItemListedTwiceException($linked_artifact_id);
                }
            }
            $predecessor = $linked_artifact_id;
        }
    }

    public function setOrderWithHistoryChangeLogging(array $linked_artifact_ids, int $context_id, int $project_id): void
    {
        $predecessor = null;

        foreach ($linked_artifact_ids as $linked_artifact_id) {
            if (isset($predecessor)) {
                try {
                    $this->priority_manager->moveArtifactAfterWithHistoryChangeLogging(
                        $linked_artifact_id,
                        $predecessor,
                        $context_id,
                        $project_id
                    );
                } catch (Tracker_Artifact_Exception_CannotRankWithMyself $exception) {
                    throw new ItemListedTwiceException($linked_artifact_id);
                }
            }
            $predecessor = $linked_artifact_id;
        }
    }

    private function formatFieldDatas(
        Tracker_FormElement_Field_ArtifactLink $artifactlink_field,
        array $elements_to_be_linked,
        array $elements_to_be_unlinked,
        string $type
    ): array {
        $field_datas = [];

        $field_datas[$artifactlink_field->getId()]['new_values'] = $this->formatLinkedElementForNewChangeset(
            $elements_to_be_linked
        );
        $field_datas[$artifactlink_field->getId()]['removed_values'] = $this->formatElementsToBeUnlinkedForNewChangeset(
            $elements_to_be_unlinked
        );

        $this->augmentFieldDatasRegardingArtifactLinkTypeUsage(
            $artifactlink_field,
            $elements_to_be_linked,
            $field_datas,
            $type
        );

        return $field_datas;
    }

    private function augmentFieldDatasRegardingArtifactLinkTypeUsage(
        Tracker_FormElement_Field_ArtifactLink $artifactlink_field,
        array $elements_to_be_linked,
        array &$field_datas,
        string $type
    ): void {
        $tracker = $artifactlink_field->getTracker();
        if (! $tracker) {
            return;
        }

        if (! $tracker->isProjectAllowedToUseNature()) {
            return;
        }

        foreach ($elements_to_be_linked as $artifact_id) {
            $field_datas[$artifactlink_field->getId()]['natures'][$artifact_id] = $type;
        }
    }

    private function formatLinkedElementForNewChangeset(array $linked_elements): string
    {
        return implode(',', $linked_elements);
    }

    private function formatElementsToBeUnlinkedForNewChangeset(array $elements_to_be_unlinked): array
    {
        $formated_elements = [];

        foreach ($elements_to_be_unlinked as $element_to_be_unlinked) {
            $formated_elements[$element_to_be_unlinked] = 1;
        }

        return $formated_elements;
    }
}
