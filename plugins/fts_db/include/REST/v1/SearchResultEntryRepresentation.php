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

namespace Tuleap\FullTextSearchDB\REST\v1;

use Tuleap\Project\REST\MinimalProjectRepresentation;
use Tuleap\QuickLink\REST\v1\SwitchToQuickLinkRepresentation;
use Tuleap\QuickLink\SwitchToQuickLink;
use Tuleap\Search\SearchResultEntry;

/**
 * @psalm-immutable
 */
final class SearchResultEntryRepresentation
{
    /**
     * @param SwitchToQuickLinkRepresentation[] $quick_links
     */
    private function __construct(
        public ?string $xref,
        public string $html_url,
        public ?string $title,
        public string $color_name,
        public string $icon_name,
        public ?string $small_icon,
        public ?string $icon,
        public MinimalProjectRepresentation $project,
        public array $quick_links,
    ) {
    }

    public static function fromSearchResultEntry(SearchResultEntry $entry): self
    {
        $quick_links = array_map(
            static fn (SwitchToQuickLink $quick_link): SwitchToQuickLinkRepresentation => SwitchToQuickLinkRepresentation::build($quick_link),
            $entry->quick_links
        );

        return new self(
            $entry->xref,
            $entry->link,
            $entry->title,
            $entry->color,
            $entry->icon_name,
            $entry->small_icon?->getInlineString(),
            $entry->normal_icon?->getInlineString(),
            new MinimalProjectRepresentation($entry->project),
            $quick_links
        );
    }
}
