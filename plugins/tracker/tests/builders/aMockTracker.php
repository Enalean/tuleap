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

require_once __DIR__ . '/../bootstrap.php';

class MockTrackerBuilder
{

    private $id;

    public function __construct($tracker)
    {
        $this->tracker = $tracker;
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withProjectId($id)
    {
        stub($this->tracker)->getGroupId()->returns($id);
        return $this;
    }

    public function withProject(Project $project)
    {
        stub($this->tracker)->getProject()->returns($project);
        return $this;
    }

    public function withName($name)
    {
        stub($this->tracker)->getName()->returns($name);
        return $this;
    }

    public function withItemName($item_name)
    {
        stub($this->tracker)->getItemName()->returns($item_name);
        return $this;
    }

    public function withStatusField($field)
    {
        stub($this->tracker)->getStatusField()->returns($field);
        return $this;
    }

    public function withParent(Tracker $tracker)
    {
        stub($this->tracker)->getParent()->returns($tracker);
        return $this;
    }

    public function havingFormElementWithNameAndType($name, $type_or_types)
    {
        stub($this->tracker)->hasFormElementWithNameAndType($name, $type_or_types)->returns(true);
        return $this;
    }

    public function havingNoFormElement($name)
    {
        stub($this->tracker)->hasFormElementWithNameAndType($name, '*')->returns(false);
        return $this;
    }

    public function build()
    {
        stub($this->tracker)->getId()->returns($this->id);
        stub($this->tracker)->__toString()->returns('Tracker #' . $this->id);
        return $this->tracker;
    }
}

function aMockTracker()
{
    return new MockTrackerBuilder(mock(Tracker::class));
}

function aMockeryTracker()
{
    return new MockTrackerBuilder(\Mockery::spy(Tracker::class));
}
