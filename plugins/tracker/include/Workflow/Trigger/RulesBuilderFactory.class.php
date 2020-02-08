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

class Tracker_Workflow_Trigger_RulesBuilderFactory
{

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory)
    {
        $this->formelement_factory = $formelement_factory;
    }

    /**
     * Return the trigger RulesBuilder data for a tracker
     *
     *
     * @return Tracker_Workflow_Trigger_RulesBuilderData
     */
    public function getForTracker(Tracker $tracker)
    {
        return new Tracker_Workflow_Trigger_RulesBuilderData(
            $this->formelement_factory->getUsedStaticSbFields($tracker),
            $this->getTriggeringFields($tracker)
        );
    }

    private function getTriggeringFields(Tracker $target_tracker)
    {
        return array_map(array($this, 'getTriggeringFieldForTracker'), $target_tracker->getChildren());
    }

    public function getTriggeringFieldForTracker(Tracker $tracker)
    {
        return new Tracker_Workflow_Trigger_RulesBuilderTriggeringFields(
            $tracker,
            $this->formelement_factory->getUsedStaticSbFields($tracker)
        );
    }
}
