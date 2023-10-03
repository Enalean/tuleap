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
 * I hold a collection of ReverseLink
 * @see ReverseLink
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
        $values_not_present_here = [];
        foreach ($other_links->links as $other_link) {
            if (! $this->contains($other_link)) {
                $values_not_present_here[] = $other_link;
            }
        }
        return new self($values_not_present_here);
    }

    public function getLinksThatHaveChangedType(self $other_links): self
    {
        $links_that_have_changed_type = [];
        foreach ($other_links->links as $other_link) {
            foreach ($this->links as $our_link) {
                if (
                    $our_link->getSourceArtifactId() === $other_link->getSourceArtifactId()
                    && $our_link->getType() !== $other_link->getType()
                ) {
                    $links_that_have_changed_type[] = $other_link;
                }
            }
        }
        return new self($links_that_have_changed_type);
    }

    private function contains(ReverseLink $link): bool
    {
        foreach ($this->links as $our_link) {
            if ($our_link->getSourceArtifactId() === $link->getSourceArtifactId()) {
                return true;
            }
        }
        return false;
    }
}
