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

namespace Tuleap\Project\REST;

use Tuleap\Project\HeartbeatsEntry;
use Tuleap\REST\JsonCast;

class HeartbeatsEntryRepresentation
{
    /**
     * @var int UNIX timestamp of the time of the last update of this entry {@type int} {@required true}
     */
    public $updated_at;
    /**
     * @var string Title of the entry {@type string} {@required true}
     */
    public $html_message;
    /**
     * @var string SVG icon associated with the entry {@type string} {@required true}
     */
    public $icon;
    /**
     * @var string SVG icon (small size) associated with the entry {@type string} {@required true}
     */
    public $small_icon;

    public function build(HeartbeatsEntry $entry)
    {
        $this->updated_at   = JsonCast::toDate($entry->getUpdatedAt());
        $this->html_message = $entry->getHTMLMessage();
        $this->icon         = $entry->getNormalIcon()->getInlineString();
        $this->small_icon   = $entry->getSmallIcon()->getInlineString();
    }
}
