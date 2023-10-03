<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

/**
 * I am a command to change the reverse links (and only them) of a given artifact.
 * I hold the list of links to add, the list of links where the type is changed,
 * and the list of links to remove.
 * @psalm-immutable
 */
final class ChangeReverseLinksCommand
{
    private function __construct(
        private readonly Artifact $target_artifact,
        private readonly CollectionOfReverseLinks $links_to_add,
        private readonly CollectionOfReverseLinks $links_to_change,
        private readonly CollectionOfReverseLinks $links_to_remove,
    ) {
    }

    public static function fromSubmittedAndExistingLinks(
        Artifact $target_artifact,
        CollectionOfReverseLinks $submitted_links,
        CollectionOfReverseLinks $existing_links,
    ): self {
        return new self(
            $target_artifact,
            $existing_links->differenceById($submitted_links),
            $existing_links->getLinksThatHaveChangedType($submitted_links),
            $submitted_links->differenceById($existing_links)
        );
    }

    public static function buildNoChange(Artifact $target_artifact): self
    {
        return new self(
            $target_artifact,
            new CollectionOfReverseLinks([]),
            new CollectionOfReverseLinks([]),
            new CollectionOfReverseLinks([])
        );
    }

    public function getTargetArtifact(): Artifact
    {
        return $this->target_artifact;
    }

    public function getLinksToAdd(): CollectionOfReverseLinks
    {
        return $this->links_to_add;
    }

    public function getLinksToChange(): CollectionOfReverseLinks
    {
        return $this->links_to_change;
    }

    public function getLinksToRemove(): CollectionOfReverseLinks
    {
        return $this->links_to_remove;
    }
}
