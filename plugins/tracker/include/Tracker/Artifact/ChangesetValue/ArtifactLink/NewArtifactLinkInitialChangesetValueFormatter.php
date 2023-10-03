<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

final class NewArtifactLinkInitialChangesetValueFormatter
{
    /**
     * @psalm-return array{
     *     new_values: string,
     *     types: array<int, string>,
     *     parent?: array{0: int}
     * }
     */
    public static function formatForWebUI(NewArtifactLinkInitialChangesetValue $value): array
    {
        $field_data = [
            'new_values' => implode(',', $value->getNewLinks()->getTargetArtifactIds()),
            'types'      => $value->getNewLinks()->getArtifactTypesByIds(),
        ];
        $value->getParent()->apply(static function (NewParentLink $parent_link) use (&$field_data) {
            $field_data['parent'] = [$parent_link->getParentArtifactId()];
        });
        return $field_data;
    }
}
