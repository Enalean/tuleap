<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Timetracking\Time;

class Time
{
    /**
     * @var int
     */
    private $user_id;

    /**
     * @var int
     */
    private $artifact_id;

    /**
     * @var string
     */
    private $day;

    /**
     * @var int
     */
    private $minutes;

    /**
     * @var string
     */
    private $step;

    /**
     * @var int
     */
    private $id;

    public function __construct($id, $user_id, $artifact_id, $day, $minutes, $step)
    {
        $this->id          = $id;
        $this->user_id     = $user_id;
        $this->artifact_id = $artifact_id;
        $this->day         = $day;
        $this->minutes     = $minutes;
        $this->step        = $step;
    }

    /**
     * @return int
     */
    public function getMinutes()
    {
        return $this->minutes;
    }

    /**
     * @return string
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * @return string
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}