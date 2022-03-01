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

/**
 * @psalm-immutable
 */
final class CollectionOfArtifactLinks
{
    /**
     * @param ArtifactLink[] $artifact_links
     */
    public function __construct(private array $artifact_links)
    {
    }

    /**
     * @return ArtifactLink[]
     */
    public function getArtifactLinks(): array
    {
        return $this->artifact_links;
    }

    /**
     * @return int[]
     */
    public function getArtifactLinksIds(): array
    {
        return array_map(static fn(ArtifactLink $artifact_link) => $artifact_link->id, $this->artifact_links);
    }

    public function getArtifactTypesByIds(): array
    {
        $types_by_links = [];
        foreach ($this->artifact_links as $artifact_link) {
            if ($artifact_link->type === null) {
                continue;
            }

            $types_by_links[$artifact_link->id] = $artifact_link->type;
        }
        return $types_by_links;
    }
}
