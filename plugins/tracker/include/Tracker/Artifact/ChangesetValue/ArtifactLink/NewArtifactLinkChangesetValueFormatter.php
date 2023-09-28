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

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

final class NewArtifactLinkChangesetValueFormatter
{
    public static function formatForWebUI(NewArtifactLinkChangesetValue $update_value): array
    {
        $field_data = [
            'new_values'     => implode(',', $update_value->getAddedValues()->getTargetArtifactIds()),
            'removed_values' => self::formatRemovedValues($update_value->getRemovedValues()),
        ];
        $update_value->getSubmittedValues()->apply(
            static function (CollectionOfForwardLinks $submitted_links) use (&$field_data) {
                $field_data['types'] = $submitted_links->getArtifactTypesByIds();
            }
        );
        $update_value->getParent()->apply(static function (NewParentLink $new_parent_link) use (&$field_data) {
            $field_data['parent'] = [$new_parent_link->getParentArtifactId()];
        });
        return $field_data;
    }

    /**
     * @psalm-return array<int, array<int>>
     */
    private static function formatRemovedValues(CollectionOfForwardLinks $removed_values): array
    {
        $formatted_values = [];
        foreach ($removed_values->getTargetArtifactIds() as $value_id) {
            $formatted_values[$value_id] = [$value_id];
        }
        return $formatted_values;
    }
}
