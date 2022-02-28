<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

final class ArtifactLinksFieldUpdateValueFormatter
{
    public static function formatForWebUI(ArtifactLinksFieldUpdateValue $update_value): array
    {
        $field_data          = [];
        $artifact_links_diff = $update_value->getArtifactLinksDiff();
        $submitted_values    = $update_value->getSubmittedValues();

        if ($artifact_links_diff !== null && $submitted_values !== null) {
            $field_data = [
                'new_values'     => implode(',', $artifact_links_diff->getNewValues()),
                'removed_values' => self::formatRemovedValues($artifact_links_diff->getRemovedValues()),
                'types'          => $submitted_values->getArtifactTypesByIds(),
            ];
        }

        $parent_link = $update_value->getParentArtifactLink();
        if ($parent_link !== null) {
            $field_data['parent'] = [$parent_link->id];
        }

        return $field_data;
    }

    /**
     * @psalm-return array<int, array<int>>
     */
    private static function formatRemovedValues(array $removed_values): array
    {
        $formatted_values = [];
        foreach ($removed_values as $value_id) {
            $formatted_values[(int) $value_id] = [(int) $value_id];
        }
        return $formatted_values;
    }
}
