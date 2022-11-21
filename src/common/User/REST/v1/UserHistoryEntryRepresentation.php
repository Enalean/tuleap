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

declare(strict_types=1);

namespace Tuleap\User\REST\v1;

use Tuleap\Project\REST\MinimalProjectRepresentation;
use Tuleap\QuickLink\REST\v1\SwitchToQuickLinkRepresentation;
use Tuleap\REST\JsonCast;
use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryEntryBadge;

/**
 * @psalm-immutable
 */
final class UserHistoryEntryRepresentation
{
    /**
     * @var string Date of the visit of this entry
     */
    public string $visit_time;
    /**
     * @var string | null Cross-reference representing the entry
     */
    public ?string $xref;
    /**
     * @var string Link to the entry
     */
    public string $html_url;
    /**
     * @var string Title of the entry
     */
    public string $title;
    /**
     * @var string Name of the color associated with the entry
     */
    public string $color_name;
    /**
     * @var string Type of the entry
     */
    public string $type;
    /**
     * @var int ID of the entry respective to its $type
     */
    public int $per_type_id;
    /**
     * @var string SVG icon associated with the entry
     * @psalm-var string|null
     */
    public ?string $icon;
    /**
     * @var string SVG icon (small size) associated with the entry
     * @psalm-var string|null
     */
    public ?string $small_icon;
    /**
     * @var MinimalProjectRepresentation Project to which this user's history entry belongs
     */
    public MinimalProjectRepresentation $project;
    /**
     * @var SwitchToQuickLinkRepresentation[] Quick links to related information
     */
    public array $quick_links;
    /**
     * @var string The name of the icon
     */
    public string $icon_name;
    /**
     * @var HistoryEntryBadge[] The badges for the item
     */
    public array $badges;

    private function __construct()
    {
    }

    public static function build(HistoryEntry $entry): self
    {
        $quick_links = [];
        foreach ($entry->getQuickLinks() as $quick_link) {
            $quick_link_representation = SwitchToQuickLinkRepresentation::build($quick_link);
            $quick_links[]             = $quick_link_representation;
        }

        $representation              = new self();
        $representation->visit_time  = JsonCast::toDate($entry->getVisitTime());
        $representation->xref        = $entry->getXref();
        $representation->html_url    = $entry->getLink();
        $representation->title       = $entry->getTitle();
        $representation->color_name  = $entry->getColor();
        $representation->icon_name   = $entry->getIconName();
        $representation->type        = $entry->getType();
        $representation->per_type_id = $entry->getPerTypeId();
        $representation->small_icon  = $entry->getSmallIcon()?->getInlineString();
        $representation->icon        = $entry->getNormalIcon()?->getInlineString();
        $representation->project     = new MinimalProjectRepresentation($entry->getProject());
        $representation->quick_links = $quick_links;
        $representation->badges      = $entry->getBadges();

        return $representation;
    }
}
