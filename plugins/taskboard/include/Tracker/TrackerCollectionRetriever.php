<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Taskboard\Tracker;

use Cardwall_OnTop_ConfigFactory;
use Planning_Milestone;
use Tracker_FormElementFactory;
use TrackerFactory;

class TrackerCollectionRetriever
{
    /** @var Cardwall_OnTop_ConfigFactory */
    private $config_factory;

    public function __construct(Cardwall_OnTop_ConfigFactory $config_factory)
    {
        $this->config_factory = $config_factory;
    }

    public static function build(): self
    {
        return new self(
            new Cardwall_OnTop_ConfigFactory(
                TrackerFactory::instance(),
                Tracker_FormElementFactory::instance()
            )
        );
    }

    public function getTrackersForMilestone(Planning_Milestone $milestone): TrackerCollection
    {
        $config = $this->config_factory->getOnTopConfigByPlanning($milestone->getPlanning());
        if ($config === null) {
            return new TrackerCollection([]);
        }
        $trackers = [];
        foreach ($config->getTrackers() as $tracker) {
            $trackers[] = new TaskboardTracker($milestone->getArtifact()->getTracker(), $tracker);
        }
        return new TrackerCollection($trackers);
    }
}
