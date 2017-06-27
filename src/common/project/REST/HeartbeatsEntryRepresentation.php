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
use Tuleap\User\REST\MinimalUserRepresentation;

class HeartbeatsEntryRepresentation
{
    /**
     * @var int UNIX timestamp of the time of the last update of this entry {@type int} {@required true}
     */
    public $updated_at;
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
     * @var \Tuleap\User\REST\MinimalUserRepresentation The last user who updated or created the item {@type Tuleap\User\REST\MinimalUserRepresentation} {@required false)
     */
    public $updated_by = null;

    public function build(HeartbeatsEntry $entry)
    {
        $this->updated_at = JsonCast::toDate($entry->getUpdatedAt());
        $this->xref       = $entry->getXref();
        $this->html_url   = $entry->getLink();
        $this->title      = $entry->getTitle();
        $this->color_name = $entry->getColor();
        $this->icon       = $entry->getIcon();

        $updated_by = $entry->getUpdatedBy();
        if ($updated_by) {
            $this->updated_by = new MinimalUserRepresentation();
            $this->updated_by->build($updated_by);
        }
    }
}
