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

namespace Tuleap\Agiledashboard\FormElement;

use SystemEvent;
use SystemEventManager;
use Tracker_Artifact;
use Tuleap\AgileDashboard\FormElement\SystemEvent\SystemEvent_BURNUP_GENERATE;

class BurnupCacheGenerator
{
    /**
     * @var SystemEventManager
     */
    private $system_event_manager;

    public function __construct(SystemEventManager $system_event_manager)
    {
        $this->system_event_manager = $system_event_manager;
    }

    public function isCacheBurnupAlreadyAsked(Tracker_Artifact $artifact)
    {
        return $this->system_event_manager->areThereMultipleEventsQueuedMatchingFirstParameter(
            'Tuleap\\Agiledashboard\\FormElement\\SystemEvent\\' . SystemEvent_BURNUP_GENERATE::NAME,
            $artifact->getId()
        );
    }

    public function forceBurnupCacheGeneration($artifact_id)
    {
        $this->system_event_manager->createEvent(
            'Tuleap\\Agiledashboard\\FormElement\\SystemEvent\\' . SystemEvent_BURNUP_GENERATE::NAME,
            $artifact_id,
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );
    }
}
