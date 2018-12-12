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
 *
 */

namespace Tuleap\REST\v1;

use Tuleap\Timetracking\Time\Time;

class ArtifactTimeRepresentation
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $date;

    /**
     * @var int
     */
    public $minutes;

    /**
     * @var string
     */
    public $step;

    public static function build(Time $time)
    {
        $object = new self();
        $object->date    = $time->getDay();
        $object->minutes = $time->getMinutes();
        $object->id      = $time->getId();
        $object->step    = $time->getStep();
        $object->user_id = $time->getUserId();
        return $object;
    }
}
