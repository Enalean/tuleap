<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

use Tracker_HierarchyFactory;
use TrackerFactory;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Tracker;

class MissingRequirementRetriever
{
    /**
     * @var TrackerFactory
     */
    private $hierarchy_factory;
    /**
     * @var SemanticDoneFactory
     */
    private $semantic_done_factory;
    /**
     * @var \AgileDashboard_Semantic_InitialEffortFactory
     */
    private $initial_effort_factory;
    /**
     * @var BacklogRequiredTrackerCollectionFormatter
     */
    private $velocity_factory;
    /**
     * @var BacklogRequiredTrackerCollectionFormatter
     */
    private $formatter;

    public function __construct(
        Tracker_HierarchyFactory $hierarchy_factory,
        SemanticDoneFactory $semantic_done_factory,
        \AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory,
        SemanticVelocityFactory $velocity_factory,
        BacklogRequiredTrackerCollectionFormatter $formatter,
    ) {
        $this->hierarchy_factory      = $hierarchy_factory;
        $this->semantic_done_factory  = $semantic_done_factory;
        $this->initial_effort_factory = $initial_effort_factory;
        $this->velocity_factory       = $velocity_factory;
        $this->formatter              = $formatter;
    }

    /**
     * @param Tracker[] $backlog_trackers
     *
     * @return BacklogRequiredTrackerCollection
     */
    public function buildCollectionFromBacklogTrackers(array $backlog_trackers)
    {
        $collection = new BacklogRequiredTrackerCollection($this->formatter);

        foreach ($backlog_trackers as $tracker) {
            $semantic_done                      = $this->semantic_done_factory->getInstanceByTracker($tracker);
            $is_done_semantic_missing           = (! $semantic_done->isSemanticDefined());
            $initial_effort                     = $this->initial_effort_factory->getByTracker($tracker);
            $is_initial_effort_semantic_missing = ($initial_effort->getFieldId() === 0);

            $required_tracker = new BacklogRequiredTracker(
                $tracker,
                $is_done_semantic_missing,
                $is_initial_effort_semantic_missing
            );
            $collection->addBacklogRequiredTracker($required_tracker);
            $collection->setIsATopBacklogPlanning($this->hasParentTrackers($tracker));
        }

        return $collection;
    }

    public function hasParentTrackers(Tracker $tracker)
    {
        return $this->hierarchy_factory->getParent($tracker) !== null;
    }

    public function getChildrenTrackers(Tracker $tracker)
    {
        return $this->hierarchy_factory->getChildren($tracker->getId());
    }

    public function getChildrenTrackersWithoutVelocitySemantic(Tracker $tracker)
    {
        /**
         * If this isn't a top-level milestone (for example, Release > Sprint and $tracker is a Sprint)
         * Then we don't check whether children have a velocity or not
         */
        $children_trackers = [];
        if (! $this->hasParentTrackers($tracker)) {
            $children_trackers = $this->getChildrenTrackers($tracker);
        }

        return $this->velocity_factory->extractAndFormatMisconfiguredVelocity($children_trackers);
    }
}
