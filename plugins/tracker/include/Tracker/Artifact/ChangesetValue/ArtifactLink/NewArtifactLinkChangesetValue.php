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

use Tuleap\Option\Option;

/**
 * I hold a new changeset value for the Artifact Link field.
 * @psalm-immutable
 */
final class NewArtifactLinkChangesetValue
{
    /**
     * @param Option<CollectionOfForwardLinks> $submitted_values
     * @param Option<NewParentLink>            $parent
     */
    private function __construct(
        private readonly int $field_id,
        private readonly CollectionOfForwardLinks $added_values,
        private readonly CollectionOfForwardLinks $removed_values,
        private readonly Option $submitted_values,
        private readonly Option $parent,
        private readonly CollectionOfReverseLinks $submitted_reverse_links,
    ) {
    }

    /**
     * @param Option<CollectionOfForwardLinks> $submitted_values
     * @param Option<NewParentLink>            $parent
     */
    public static function fromParts(
        int $field_id,
        CollectionOfForwardLinks $existing_links,
        Option $submitted_values,
        Option $parent,
        CollectionOfReverseLinks $submitted_reverse_links,
    ): self {
        return $submitted_values->mapOr(
            static fn(CollectionOfForwardLinks $submitted_links) => new self(
                $field_id,
                $existing_links->differenceById($submitted_links),
                $submitted_links->differenceById($existing_links),
                $submitted_values,
                $parent,
                $submitted_reverse_links
            ),
            // No added or removed values when $submitted_values is Nothing
            new self(
                $field_id,
                new CollectionOfForwardLinks([]),
                new CollectionOfForwardLinks([]),
                $submitted_values,
                $parent,
                $submitted_reverse_links
            )
        );
    }

    public static function fromAddedAndUpdatedTypeValues(
        int $field_id,
        CollectionOfForwardLinks $submitted_values,
    ): self {
        return new self(
            $field_id,
            $submitted_values,
            new CollectionOfForwardLinks([]),
            Option::fromValue($submitted_values),
            Option::nothing(NewParentLink::class),
            new CollectionOfReverseLinks([])
        );
    }

    public static function fromRemovedValues(int $field_id, CollectionOfForwardLinks $values_to_remove): self
    {
        return new self(
            $field_id,
            new CollectionOfForwardLinks([]),
            $values_to_remove,
            Option::fromValue(new CollectionOfForwardLinks([])),
            Option::nothing(NewParentLink::class),
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

    /**
     * @return Option<NewParentLink>
     */
    public function getParent(): Option
    {
        return $this->parent;
    }

    /**
     * @return Option<CollectionOfForwardLinks>
     */
    public function getSubmittedValues(): Option
    {
        return $this->submitted_values;
    }

    public function getSubmittedReverseLinks(): CollectionOfReverseLinks
    {
        return $this->submitted_reverse_links;
    }
}
