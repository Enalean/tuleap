<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use SystemEvent;
use SystemEventManager;
use Tuleap\AgileDashboard\FormElement\SystemEvent\SystemEvent_BURNUP_GENERATE;
use Tuleap\Tracker\Artifact\Artifact;

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

    public function isCacheBurnupAlreadyAsked(Artifact $artifact)
    {
        return $this->system_event_manager->areThereMultipleEventsQueuedMatchingFirstParameter(
            SystemEvent_BURNUP_GENERATE::class,
            $artifact->getId()
        );
    }

    public function forceBurnupCacheGeneration(Artifact $artifact)
    {
        if ($this->isCacheBurnupAlreadyAsked($artifact)) {
            return;
        }
        $this->system_event_manager->createEvent(
            SystemEvent_BURNUP_GENERATE::class,
            $artifact->getId(),
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );
    }
}
