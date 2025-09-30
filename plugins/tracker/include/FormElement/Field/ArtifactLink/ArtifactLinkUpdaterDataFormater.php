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

class ArtifactLinkUpdaterDataFormater
{
    public function formatFieldData(
        ArtifactLinkField $artifactlink_field,
        array $elements_to_be_linked,
        array $elements_to_be_unlinked,
        string $type,
    ): array {
        $field_datas                                                 = [];
        $field_datas[$artifactlink_field->getId()]['new_values']     = $this->formatLinkedElementForNewChangeset(
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

    private function augmentFieldDatasRegardingArtifactLinkTypeUsage(
        ArtifactLinkField $artifactlink_field,
        array $elements_to_be_linked,
        array &$field_datas,
        string $type,
    ): void {
        $tracker = $artifactlink_field->getTracker();

        if (! $tracker->isProjectAllowedToUseType()) {
            return;
        }

        foreach ($elements_to_be_linked as $artifact_id) {
            $field_datas[$artifactlink_field->getId()]['types'][$artifact_id] = $type;
        }
    }
}
