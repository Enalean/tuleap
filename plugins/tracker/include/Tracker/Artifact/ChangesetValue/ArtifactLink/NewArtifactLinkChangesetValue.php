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
        private CollectionOfForwardLinks $added_values,
        private CollectionOfForwardLinks $removed_values,
        private ?CollectionOfForwardLinks $submitted_values,
        private ?NewParentLink $parent,
        private CollectionOfReverseLinks $submitted_reverse_links,
    ) {
    }

    public static function fromParts(
        int $field_id,
        CollectionOfForwardLinks $existing_links,
        ?CollectionOfForwardLinks $submitted_values,
        ?NewParentLink $parent,
        CollectionOfReverseLinks $submitted_reverse_links,
    ): self {
        $added_values   = $submitted_values
            ? $existing_links->differenceById($submitted_values)
            : new CollectionOfForwardLinks([]);
        $removed_values = $submitted_values
            ? $submitted_values->differenceById($existing_links)
            : new CollectionOfForwardLinks([]);
        return new self(
            $field_id,
            $added_values,
            $removed_values,
            $submitted_values,
            $parent,
            $submitted_reverse_links
        );
    }

    public static function fromAddedValues(int $field_id, CollectionOfForwardLinks $submitted_values): self
    {
        return new self(
            $field_id,
            $submitted_values,
            new CollectionOfForwardLinks([]),
            $submitted_values,
            null,
            new CollectionOfReverseLinks([])
        );
    }

    public function getFieldId(): int
    {
        return $this->field_id;
    }

    public function getAddedValues(): CollectionOfForwardLinks
    {
        return $this->added_values;
    }

    public function getRemovedValues(): CollectionOfForwardLinks
    {
        return $this->removed_values;
    }

    public function getParent(): ?NewParentLink
    {
        return $this->parent;
    }

    public function getSubmittedValues(): ?CollectionOfForwardLinks
    {
        return $this->submitted_values;
    }

    public function getSubmittedReverseLinks(): CollectionOfReverseLinks
    {
        return $this->submitted_reverse_links;
    }
}
