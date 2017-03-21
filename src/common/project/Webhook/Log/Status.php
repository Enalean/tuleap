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

namespace Tuleap\Project\Webhook\Log;

class Status
{
    /**
     * @var string
     */
    private $status;
    /**
     * @var int
     */
    private $created_on;

    public function __construct($status, $created_on)
    {
        $this->status     = $status;
        $this->created_on = $created_on;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isInError()
    {
        if (empty($this->status)) {
            return true;
        }

        return $this->status[0] !== '2';
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->created_on);
    }
}
