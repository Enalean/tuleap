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
 * I am a command to add new reverse links to an artifact.
 * @psalm-immutable
 */
final class AddReverseLinksCommand
{
    private function __construct(
        private readonly Artifact $target_artifact,
        private readonly CollectionOfReverseLinks $links_to_add,
    ) {
    }

    public static function fromParts(Artifact $target_artifact, CollectionOfReverseLinks $links_to_add): self
    {
        return new self($target_artifact, $links_to_add);
    }

    public function getTargetArtifact(): Artifact
    {
        return $this->target_artifact;
    }

    public function getLinksToAdd(): CollectionOfReverseLinks
    {
        return $this->links_to_add;
    }
}
