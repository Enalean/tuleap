<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class UserHistoryEntryRepresentation
{
    /**
     * @var int UNIX timestamp of the time of the visit of this entry {@type int} {@required true}
     */
    public $visit_time;
    /**
     * @var string Cross reference representing the entry {@type string} {@required true}
     */
    public $xref;
    /**
     * @var string Link to the entry {@type string} {@required true}
     */
    public $html_url;
    /**
     * @var string Title of the entry {@type string} {@required true}
     */
    public $title;
    /**
     * @var string Name of the color associated with the entry {@type string} {@required true}
     */
    public $color_name;
    /**
     * @var string SVG icon associated with the entry {@type string} {@required true}
     */
    public $icon;
    /**
     * @var string SVG icon (small size) associated with the entry {@type string} {@required true}
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

    public function build(HistoryEntry $entry)
    {
        $this->visit_time  = JsonCast::toDate($entry->getVisitTime());
        $this->xref        = $entry->getXref();
        $this->html_url    = $entry->getLink();
        $this->title       = $entry->getTitle();
        $this->color_name  = $entry->getColor();
        $this->icon_name   = $entry->getIconName();

        $glyph_small_icon = $entry->getSmallIcon();
        if ($glyph_small_icon !== null) {
            $this->small_icon = $glyph_small_icon->getInlineString();
        }
        $glyph_normal_icon = $entry->getNormalIcon();
        if ($glyph_normal_icon !== null) {
            $this->icon = $glyph_normal_icon->getInlineString();
        }

        $project_representation = new MinimalProjectRepresentation();
        $project_representation->buildMinimal($entry->getProject());
        $this->project = $project_representation;

        $this->quick_links = array();
        foreach ($entry->getQuickLinks() as $quick_link) {
            $quick_link_representation = new UserHistoryQuickLinkRepresentation();
            $quick_link_representation->build($quick_link);
            $this->quick_links[] = $quick_link_representation;
        }
    }
}
