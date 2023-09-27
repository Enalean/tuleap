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

use Tuleap\Option\Option;

/**
 * I am a command to change the forward links (and only them) for the Artifact Link field.
 * I hold the list of links to add, the list of links where the type is changed,
 * and the list of links to remove.
 * @psalm-immutable
 */
final class ChangeForwardLinksCommand
{
    private function __construct(
        private readonly int $field_id,
        private readonly CollectionOfForwardLinks $links_to_add,
        private readonly CollectionOfForwardLinks $links_to_change,
        private readonly CollectionOfForwardLinks $links_to_remove,
    ) {
    }

    /**
     * @param Option<CollectionOfForwardLinks> $submitted_links
     */
    public static function fromSubmittedAndExistingLinks(
        int $field_id,
        Option $submitted_links,
        CollectionOfForwardLinks $existing_links,
    ): self {
        return $submitted_links->mapOr(
            static fn(CollectionOfForwardLinks $submitted_links) => new self(
                $field_id,
                $existing_links->differenceById($submitted_links),
                $existing_links->getLinksThatHaveChangedType($submitted_links),
                $submitted_links->differenceById($existing_links)
            ),
            self::buildNoChange($field_id)
        );
    }

    public static function buildNoChange(int $field_id): self
    {
        return new self(
            $field_id,
            new CollectionOfForwardLinks([]),
            new CollectionOfForwardLinks([]),
            new CollectionOfForwardLinks([])
        );
    }

    public static function fromParts(
        int $field_id,
        CollectionOfForwardLinks $links_to_add,
        CollectionOfForwardLinks $links_to_change,
        CollectionOfForwardLinks $links_to_remove,
    ): self {
        return new self($field_id, $links_to_add, $links_to_change, $links_to_remove);
    }

    public function getFieldId(): int
    {
        return $this->field_id;
    }

    public function getLinksToAdd(): CollectionOfForwardLinks
    {
        return $this->links_to_add;
    }

    public function getLinksToChange(): CollectionOfForwardLinks
    {
        return $this->links_to_change;
    }

    public function getLinksToRemove(): CollectionOfForwardLinks
    {
        return $this->links_to_remove;
    }
}
