<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

/**
 * @psalm-immutable
 */
final class CollectionOfReverseLinks
{
    /**
     * @param ReverseLink[] $links
     */
    public function __construct(public array $links)
    {
    }

    public function differenceById(CollectionOfReverseLinks $other_links): CollectionOfReverseLinks
    {
        $difference = array_udiff(
            $this->links,
            $other_links->links,
            static fn(
                ReverseLink $link_a,
                ReverseLink $link_b,
            ) => $link_a->getSourceArtifactId() - $link_b->getSourceArtifactId()
        );
        return new CollectionOfReverseLinks($difference);
    }
}
