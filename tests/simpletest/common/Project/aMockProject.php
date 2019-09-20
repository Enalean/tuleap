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

function aMockProject()
{
    return new MockProjectBuilder();
}

class MockProjectBuilder
{

    private $project;
    private $id        = false;
    private $unix_name = false;
    private $is_public = false;

    public function __construct()
    {
        $this->project   = mock('Project');
        $this->id        = uniqid();
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withUnixName($name)
    {
        $this->unix_name = $name;
        return $this;
    }

    public function isPublic()
    {
        $this->is_public = true;
        return $this;
    }

    public function build()
    {
        stub($this->project)->getId()->returns($this->id);
        stub($this->project)->getUnixName()->returns($this->unix_name);
        stub($this->project)->isPublic()->returns($this->is_public);
        return $this->project;
    }
}
