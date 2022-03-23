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

/**
 * I hold a new changeset value for the Artifact Link field.
 * @psalm-immutable
 */
final class NewArtifactLinkChangesetValue
{
    private function __construct(
        private int $field_id,
        private ?ArtifactLinksDiff $artifact_links_diff,
        private ?CollectionOfForwardLinks $submitted_values,
        private ?NewParentLink $parent,
    ) {
    }

    public static function fromParts(
        int $field_id,
        CollectionOfForwardLinks $existing_links,
        ?CollectionOfForwardLinks $submitted_values,
        ?NewParentLink $parent,
    ): self {
        $diff = $submitted_values !== null ? ArtifactLinksDiff::build($submitted_values, $existing_links) : null;
        return new self(
            $field_id,
            $diff,
            $submitted_values,
            $parent
        );
    }

    public function getFieldId(): int
    {
        return $this->field_id;
    }

    public function getArtifactLinksDiff(): ?ArtifactLinksDiff
    {
        return $this->artifact_links_diff;
    }

    public function getParent(): ?NewParentLink
    {
        return $this->parent;
    }

    public function getSubmittedValues(): ?CollectionOfForwardLinks
    {
        return $this->submitted_values;
    }
}
