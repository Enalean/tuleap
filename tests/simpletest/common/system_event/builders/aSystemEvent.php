<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class SysteEvent_For_Testing_Purpose extends SystemEvent
{
    public function verbalizeParameters($with_link)
    {
        return $this->parameters;
    }
}

function aSystemEvent($name = 'SysteEvent_For_Testing_Purpose')
{
    return new Test_SystemEvent_Builder($name);
}

class Test_SystemEvent_Builder
{
    private $name;
    private $parameters;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function withParameters($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function build()
    {
        $id = $type = $owner = $priority = $status = $create_date = $process_date = $end_date = $log = null;
        $klass = $this->name;
        return new $klass($id, $type, $owner, $this->parameters, $priority, $status, $create_date, $process_date, $end_date, $log);
    }
}
