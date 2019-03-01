<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use CSRFSynchronizerToken;
use Tracker;
use Tuleap\AgileDashboard\Semantic\SemanticDone;

class SemanticVelocityAdminPresenterBuilder
{
    /**
     * @var MissingRequirementRetriever
     */
    private $missing_requirement_retriever;
    /**
     * @var BacklogRetriever
     */
    private $backlog_retriever;
    /**
     * @var VelocitySemanticChecker
     */
    private $semantic_checker;

    public function __construct(
        MissingRequirementRetriever $missing_requirement_retriever,
        BacklogRetriever $backlog_retriever,
        VelocitySemanticChecker $semantic_checker
    ) {
        $this->missing_requirement_retriever = $missing_requirement_retriever;
        $this->backlog_retriever             = $backlog_retriever;
        $this->semantic_checker              = $semantic_checker;
    }

    public function build(
        Tracker $tracker,
        CSRFSynchronizerToken $csrf,
        SemanticDone $semantic_done,
        array $possible_fields,
        $semantic_velocity_field_id
    ) {
        $backlog_trackers = $this->backlog_retriever->getBacklogTrackers($tracker);
        $backlog_required_trackers_collection = $this->missing_requirement_retriever->buildCollectionFromBacklogTrackers(
            $backlog_trackers
        );

        $children_required_trackers_collection = $this->missing_requirement_retriever->getChildrenTrackersWithoutVelocitySemantic(
            $tracker
        );

        $has_at_least_on_tracker_correctly_configured = $this->semantic_checker->hasAtLeastOneTrackerCorrectlyConfigured(
            $backlog_required_trackers_collection,
            $children_required_trackers_collection
        );

        return new SemanticVelocityAdminPresenter(
            $possible_fields,
            $csrf,
            $tracker,
            $semantic_done->isSemanticDefined(),
            $semantic_velocity_field_id,
            $backlog_required_trackers_collection,
            $children_required_trackers_collection,
            $has_at_least_on_tracker_correctly_configured
        );
    }
}
