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

namespace Tuleap\Velocity;

use Tuleap\REST\JsonCast;

class VelocityRepresentation
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $start_date;
    /**
     * @var Float
     */
    public $duration;
    /**
     * @var Float
     */
    public $velocity;
    /**
     * @var int
     */
    public $id;

    public function __construct($id, $name, $start_date, $duration, $velocity)
    {
        $this->id         = JsonCast::ToInt($id);
        $this->name       = $name;
        $this->start_date = ($start_date) ? JsonCast::toDate($start_date) : "";
        $this->duration   = JsonCast::toFloat($duration);
        $this->velocity   = JsonCast::toFloat($velocity);
    }
}
