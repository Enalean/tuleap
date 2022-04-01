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

/**
 * I hold the initial changeset value for the Artifact Link field.
 * Since it is the first changeset, there are no existing values to consider, so no diff.
 * @see NewArtifactLinkChangesetValue
 * @psalm-immutable
 */
final class NewArtifactLinkInitialChangesetValue
{
    private function __construct(
        private int $field_id,
        private CollectionOfForwardLinks $new_links,
        private ?NewParentLink $parent,
    ) {
    }

    public static function fromParts(
        int $field_id,
        CollectionOfForwardLinks $new_links,
        ?NewParentLink $parent,
    ): self {
        return new self($field_id, $new_links, $parent);
    }

    public function getFieldId(): int
    {
        return $this->field_id;
    }

    public function getParent(): ?NewParentLink
    {
        return $this->parent;
    }

    public function getNewLinks(): CollectionOfForwardLinks
    {
        return $this->new_links;
    }
}
