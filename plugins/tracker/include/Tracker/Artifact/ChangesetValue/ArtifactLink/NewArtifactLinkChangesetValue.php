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
     * @param Option<NewParentLink>            $new_parent_link
     * @param Option<CollectionOfReverseLinks> $submitted_reverse_links
     */
    private function __construct(
        private readonly ChangeForwardLinksCommand $forward_links_command,
        private readonly Option $new_parent_link,
        private readonly Option $submitted_reverse_links,
    ) {
    }

    /**
     * @param Option<NewParentLink>            $new_parent_link
     * @param Option<CollectionOfReverseLinks> $submitted_reverse_links
     */
    public static function fromParts(
        ChangeForwardLinksCommand $forward_links_command,
        Option $new_parent_link,
        Option $submitted_reverse_links,
    ): self {
        return new self($forward_links_command, $new_parent_link, $submitted_reverse_links);
    }

    public static function fromOnlyForwardLinks(ChangeForwardLinksCommand $forward_links_command): self
    {
        return new self(
            $forward_links_command,
            Option::nothing(NewParentLink::class),
            Option::nothing(CollectionOfReverseLinks::class)
        );
    }

    public function getChangeForwardLinksCommand(): ChangeForwardLinksCommand
    {
        return $this->forward_links_command;
    }

    /**
     * @return Option<NewParentLink>
     */
    public function getNewParentLink(): Option
    {
        return $this->new_parent_link;
    }

    /**
     * @return Option<CollectionOfReverseLinks>
     */
    public function getSubmittedReverseLinks(): Option
    {
        return $this->submitted_reverse_links;
    }
}
