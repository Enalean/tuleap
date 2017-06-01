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

namespace Tuleap\User\History;

class HistoryEntry
{
    /**
     * @var int
     */
    private $visit_time;
    /**
     * @var string
     */
    private $xref;
    /**
     * @var string
     */
    private $link;
    /**
     * @var string
     */
    private $title;

    public function __construct($visit_time, $xref, $link, $title)
    {
        $this->visit_time = (int) $visit_time;
        $this->xref       = $xref;
        $this->link       = $link;
        $this->title      = $title;
    }

    /**
     * @return int
     */
    public function getVisitTime()
    {
        return $this->visit_time;
    }

    /**
     * @return string
     */
    public function getXref()
    {
        return $this->xref;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
