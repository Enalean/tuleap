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

namespace Tuleap\Project;

class HeartbeatsEntry
{
    /**
     * @var int
     */
    private $updated_at;
    /**
     * @var string
     */
    private $html_message;
    /**
     * @var string
     */
    private $icon;

    public function __construct($updated_at, $icon, $html_message)
    {
        $this->updated_at   = (int)$updated_at;
        $this->icon         = $icon;
        $this->html_message = $html_message;
    }

    /**
     * @return int
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @return string
     */
    public function getHTMLMessage()
    {
        return $this->html_message;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }
}
