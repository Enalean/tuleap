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
    public $link;
    /**
     * @var string Title of the entry {@type string} {@required true}
     */
    public $title;

    public function build(HistoryEntry $entry)
    {
        $this->visit_time = $entry->getVisitTime();
        $this->xref       = $entry->getXref();
        $this->link       = $entry->getLink();
        $this->title      = $entry->getTitle();
    }
}
