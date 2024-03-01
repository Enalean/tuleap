<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders;

use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElement_Field;

final class ChangesetValueArtifactLinkTestBuilder
{
    /**
     * @var array<int, Tracker_ArtifactLinkInfo>
     */
    private array $links = [];

    private function __construct(
        private readonly int $id,
        private readonly Tracker_Artifact_Changeset $changeset,
        private readonly Tracker_FormElement_Field $field,
    ) {
    }

    public static function aValue(int $id, Tracker_Artifact_Changeset $changeset, Tracker_FormElement_Field $field): self
    {
        return new self($id, $changeset, $field);
    }

    /**
     * @param array<int, Tracker_ArtifactLinkInfo> $links
     */
    public function withLinks(array $links): self
    {
        $this->links = $links;

        return $this;
    }

    public function build(): Tracker_Artifact_ChangesetValue_ArtifactLink
    {
        return new Tracker_Artifact_ChangesetValue_ArtifactLink(
            $this->id,
            $this->changeset,
            $this->field,
            true,
            $this->links,
            [],
        );
    }
}
