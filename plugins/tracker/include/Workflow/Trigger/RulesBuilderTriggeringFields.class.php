<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Workflow_Trigger_RulesBuilderTriggeringFields
{

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var Tracker_FormElement_Field_List[]
     */
    private $fields;

    public function __construct(Tracker $tracker, Iterator $fields)
    {
        $this->tracker = $tracker;
        $this->fields  = $fields;
    }

    public function getTracker()
    {
        return $this->tracker;
    }

    public function getFields()
    {
        return $this->fields;
    }
}
