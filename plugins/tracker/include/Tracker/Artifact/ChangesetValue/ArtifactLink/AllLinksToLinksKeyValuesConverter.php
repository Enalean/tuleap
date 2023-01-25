<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;

final class AllLinksToLinksKeyValuesConverter
{
    /**
     * @param ArtifactValuesRepresentation[] $values
     * @return ArtifactValuesRepresentation[]
     */
    public static function convertIfNeeded(array $values): array
    {
        foreach ($values as $value) {
            if ($value->links === null && $value->all_links !== null) {
                return array_map(
                    function (ArtifactValuesRepresentation $value) {
                        $value->links     = array_map(
                            function (LinkWithDirectionRepresentation $link) {
                                return (array) $link;
                            },
                            ($value->all_links) ?? []
                        );
                        $value->all_links = null;
                        return $value;
                    },
                    $values
                );
            }
        }
        return $values;
    }
}
