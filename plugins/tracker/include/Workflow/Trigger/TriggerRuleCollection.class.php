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

class Tracker_Workflow_Trigger_TriggerRuleCollection implements Iterator, Countable, Tracker_IProvideJsonFormatOfMyself
{
    /**
     * @var Tracker_Workflow_Trigger_TriggerRule[]
     */
    private $trigger_rules = array();

    public function fetchFormattedForJson()
    {
        $json = array();
        foreach ($this->trigger_rules as $rule) {
            $json[] = $rule->fetchFormattedForJson();
        }
        return $json;
    }

    public function push(Tracker_Workflow_Trigger_TriggerRule $row)
    {
        $this->trigger_rules[] = $row;
    }

    /**
     * @return Tracker_Workflow_Trigger_TriggerRule
     */
    public function current()
    {
        return current($this->trigger_rules);
    }

    public function key()
    {
        return key($this->trigger_rules);
    }

    public function next()
    {
        next($this->trigger_rules);
    }

    public function rewind()
    {
        reset($this->trigger_rules);
    }

    public function valid()
    {
        return current($this->trigger_rules) !== false;
    }

    public function count()
    {
        return count($this->trigger_rules);
    }
}
