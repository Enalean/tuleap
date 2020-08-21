<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\User\REST\v1;

use Tuleap\Project\REST\MinimalProjectRepresentation;
use Tuleap\REST\JsonCast;
use Tuleap\User\History\HistoryEntry;

/**
 * @psalm-immutable
 */
class UserHistoryEntryRepresentation
{
    /**
     * @var string Date of the visit of this entry {@type int} {@required true}
     */
    public $visit_time;
    /**
     * @var string | null Cross reference representing the entry {@type string} {@required true}
     */
    public $xref;
    /**
     * @var string Link to the entry {@type string} {@required true}
     */
    public $html_url;
    /**
     * @var string | null Title of the entry {@type string} {@required true}
     */
    public $title;
    /**
     * @var string Name of the color associated with the entry {@type string} {@required true}
     */
    public $color_name;
    /**
     * @var string SVG icon associated with the entry {@type string} {@required true}
     * @psalm-var string|null
     */
    public $icon;
    /**
     * @var string SVG icon (small size) associated with the entry {@type string} {@required true}
     * @psalm-var string|null
     */
    public $small_icon;
    /**
     * @var MinimalProjectRepresentation Project to which this user's history entry belongs {@required true}
     */
    public $project;
    /**
     * @var UserHistoryQuickLinkRepresentation[] Quick links to related information {@required true}
     */
    public $quick_links;
    /**
     * @var string The name of the icon {@required true}
     */
    public $icon_name;

    /**
     * @param UserHistoryQuickLinkRepresentation[] $quick_links
     */
    private function __construct(
        string $visit_time,
        ?string $xref,
        string $html_url,
        ?string $title,
        string $color_name,
        string $icon_name,
        ?string $small_icon,
        ?string $icon,
        MinimalProjectRepresentation $project,
        array $quick_links
    ) {
        $this->visit_time  = $visit_time;
        $this->xref        = $xref;
        $this->html_url    = $html_url;
        $this->title       = $title;
        $this->color_name  = $color_name;
        $this->icon_name   = $icon_name;
        $this->small_icon  = $small_icon;
        $this->icon        = $icon;
        $this->project     = $project;
        $this->quick_links = $quick_links;
    }

    public static function build(HistoryEntry $entry): self
    {
        $small_icon = null;
        $glyph_small_icon = $entry->getSmallIcon();
        if ($glyph_small_icon !== null) {
            $small_icon = $glyph_small_icon->getInlineString();
        }
        $icon = null;
        $glyph_normal_icon = $entry->getNormalIcon();
        if ($glyph_normal_icon !== null) {
            $icon = $glyph_normal_icon->getInlineString();
        }

        $quick_links = [];
        foreach ($entry->getQuickLinks() as $quick_link) {
            $quick_link_representation = UserHistoryQuickLinkRepresentation::build($quick_link);
            $quick_links[] = $quick_link_representation;
        }

        return new self(
            JsonCast::toDate($entry->getVisitTime()),
            $entry->getXref(),
            $entry->getLink(),
            $entry->getTitle(),
            $entry->getColor(),
            $entry->getIconName(),
            $small_icon,
            $icon,
            new MinimalProjectRepresentation($entry->getProject()),
            $quick_links
        );
    }
}
