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

use Tuleap\Option\Option;

final class ChangeForwardLinksCommandFormatter
{
    /**
     * @param Option<NewParentLink> $parent
     * @psalm-return array{
     *     new_values: string,
     *     removed_values: array<int, array{0: int}>,
     *     types?: array<int, string>,
     *     parent?: array{0: int}
     * }
     */
    public static function formatForWebUI(ChangeForwardLinksCommand $command, Option $parent): array
    {
        $field_data = [
            'new_values'     => implode(',', $command->getLinksToAdd()->getTargetArtifactIds()),
            'removed_values' => self::formatRemovedValues($command->getLinksToRemove()),
        ];
        $types      = self::formatTypes($command);
        if (! empty($types)) {
            $field_data['types'] = $types;
        }
        $parent->apply(static function (NewParentLink $parent_link) use (&$field_data) {
            $field_data['parent'] = [$parent_link->getParentArtifactId()];
        });
        return $field_data;
    }

    /**
     * @psalm-return array<int, array{0: int}>
     */
    private static function formatTypes(ChangeForwardLinksCommand $command): array
    {
        $added_types   = $command->getLinksToAdd()->getArtifactTypesByIds();
        $changed_types = $command->getLinksToChange()->getArtifactTypesByIds();
        return $added_types + $changed_types; // merge associative arrays
    }

    /**
     * @psalm-return array<int, array{0: int}>
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
