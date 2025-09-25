<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

class RequestDataAugmentor
{
    public function augmentDataFromRequest(
        ArtifactLinkField $artifact_link_field,
        array &$fields_data,
    ): void {
        if ($artifact_link_field->getTracker()->isProjectAllowedToUseType()) {
            $this->invertParentRelationship($artifact_link_field, $fields_data);
            $this->addNewValuesInTypesArray($artifact_link_field, $fields_data);
        }
    }

    private function invertParentRelationship(
        ArtifactLinkField $artifact_link_field,
        array &$fields_data,
    ): void {
        if (! isset($fields_data[$artifact_link_field->getId()]['new_values'])) {
            return;
        }

        if (! isset($fields_data[$artifact_link_field->getId()]['type'])) {
            return;
        }

        $this->invertParentRelashionshipForNewLinks($artifact_link_field, $fields_data);
        $this->invertParentRelashionshipForExistingLinks($artifact_link_field, $fields_data);
        $this->removeDuplicatedParents($artifact_link_field, $fields_data);
    }

    private function invertParentRelashionshipForNewLinks(
        ArtifactLinkField $artifact_link_field,
        array &$fields_data,
    ): void {
        if ($fields_data[$artifact_link_field->getId()]['type'] === ArtifactLinkField::FAKE_TYPE_IS_PARENT) {
            $fields_data[$artifact_link_field->getId()][ArtifactLinkField::FIELDS_DATA_PARENT_KEY] = explode(',', $fields_data[$artifact_link_field->getId()]['new_values']);

            $fields_data[$artifact_link_field->getId()]['new_values'] = '';
            $fields_data[$artifact_link_field->getId()]['type']       = '';
        }
    }

    private function invertParentRelashionshipForExistingLinks(
        ArtifactLinkField $artifact_link_field,
        array &$fields_data,
    ): void {
        if (! isset($fields_data[$artifact_link_field->getId()]['types'])) {
            return;
        }

        foreach ($fields_data[$artifact_link_field->getId()]['types'] as $id => $requested_type) {
            if ($requested_type !== ArtifactLinkField::FAKE_TYPE_IS_PARENT) {
                continue;
            }

            if (isset($fields_data[$artifact_link_field->getId()]['removed_values'][$id])) {
                unset($fields_data[$artifact_link_field->getId()]['types'][$id]);
                continue;
            }

            if (! isset($fields_data[$artifact_link_field->getId()][ArtifactLinkField::FIELDS_DATA_PARENT_KEY])) {
                $fields_data[$artifact_link_field->getId()][ArtifactLinkField::FIELDS_DATA_PARENT_KEY] = [];
            }

            $fields_data[$artifact_link_field->getId()][ArtifactLinkField::FIELDS_DATA_PARENT_KEY][] = (int) $id;
            unset($fields_data[$artifact_link_field->getId()]['types'][$id]);
            $fields_data[$artifact_link_field->getId()]['removed_values'][$id] = [(int) $id];
        }
    }

    private function removeDuplicatedParents(
        ArtifactLinkField $artifact_link_field,
        array &$fields_data,
    ): void {
        if (! isset($fields_data[$artifact_link_field->getId()][ArtifactLinkField::FIELDS_DATA_PARENT_KEY])) {
            return;
        }

        $fields_data[$artifact_link_field->getId()][ArtifactLinkField::FIELDS_DATA_PARENT_KEY] = array_unique(
            $fields_data[$artifact_link_field->getId()][ArtifactLinkField::FIELDS_DATA_PARENT_KEY]
        );
    }

    private function addNewValuesInTypesArray(
        ArtifactLinkField $artifact_link_field,
        array &$fields_data,
    ): void {
        if (! isset($fields_data[$artifact_link_field->getId()]['new_values'])) {
            return;
        }

        $new_values = $fields_data[$artifact_link_field->getId()]['new_values'];

        if (! isset($fields_data[$artifact_link_field->getId()]['type'])) {
            $fields_data[$artifact_link_field->getId()]['type'] = ArtifactLinkField::DEFAULT_LINK_TYPE;
        }

        if (trim($new_values) != '') {
            $art_id_array = explode(',', $new_values);
            foreach ($art_id_array as $artifact_id) {
                $artifact_id = trim($artifact_id);
                if (! isset($fields_data[$artifact_link_field->getId()]['types'][$artifact_id])) {
                    $fields_data[$artifact_link_field->getId()]['types'][$artifact_id] = $fields_data[$artifact_link_field->getId()]['type'];
                }
            }
        }
    }
}
