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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Link\ForwardLinkProxy;

/**
 * I hold a collection of ForwardLink
 * @see ForwardLink
 * @psalm-immutable
 */
final class CollectionOfForwardLinks
{
    /**
     * @param ForwardLink[] $artifact_links
     */
    public function __construct(private array $artifact_links)
    {
    }

    public static function fromReverseLink(Artifact $source, ReverseLink $reverse_link): self
    {
        return new self([ForwardLinkProxy::buildFromData($source->getId(), $reverse_link->getType())]);
    }

    /**
     * @return ForwardLink[]
     */
    public function getArtifactLinks(): array
    {
        return $this->artifact_links;
    }

    /**
     * @return int[]
     */
    public function getTargetArtifactIds(): array
    {
        return array_map(
            static fn(ForwardLink $artifact_link) => $artifact_link->getTargetArtifactId(),
            $this->artifact_links
        );
    }

    public function getArtifactTypesByIds(): array
    {
        $types_by_links = [];
        foreach ($this->artifact_links as $artifact_link) {
            $types_by_links[$artifact_link->getTargetArtifactId()] = $artifact_link->getType();
        }
        return $types_by_links;
    }

    public function differenceById(self $other_links): self
    {
        $values_not_present_here = [];
        foreach ($other_links->artifact_links as $link) {
            if (! $this->contains($link)) {
                $values_not_present_here[] = $link;
            }
        }
        return new self($values_not_present_here);
    }

    public function differenceByIdAndType(self $other_links): self
    {
        $values_not_present_here = [];
        foreach ($other_links->artifact_links as $link) {
            if (! $this->containsWithType($link)) {
                $values_not_present_here[] = $link;
            }
        }
        return new self($values_not_present_here);
    }

    private function contains(ForwardLink $link): bool
    {
        foreach ($this->artifact_links as $our_link) {
            if ($our_link->getTargetArtifactId() === $link->getTargetArtifactId()) {
                return true;
            }
        }
        return false;
    }

    private function containsWithType(ForwardLink $link): bool
    {
        foreach ($this->artifact_links as $our_link) {
            if ($our_link->getTargetArtifactId() === $link->getTargetArtifactId() && $our_link->getType() === $link->getType()) {
                return true;
            }
        }
        return false;
    }
}
