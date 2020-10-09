<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Workflow;

use Tracker_FormElement_Field;
use Transition;
use Transition_PostAction;
use Transition_PostActionSubFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Workflow;

class AddToTopBacklogPostActionFactory implements Transition_PostActionSubFactory
{
    /**
     * @var AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    /**
     * @var UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var array<int, array<int, int>>
     */
    private $cache = [];

    public function __construct(
        AddToTopBacklogPostActionDao $add_to_top_backlog_post_action_dao,
        UnplannedArtifactsAdder $unplanned_artifacts_adder,
        ExplicitBacklogDao $explicit_backlog_dao
    ) {
        $this->add_to_top_backlog_post_action_dao = $add_to_top_backlog_post_action_dao;
        $this->unplanned_artifacts_adder          = $unplanned_artifacts_adder;
        $this->explicit_backlog_dao               = $explicit_backlog_dao;
    }

    public function warmUpCacheForWorkflow(Workflow $workflow): void
    {
        $workflow_id = (int) $workflow->getId();
        if (isset($this->cache[$workflow_id])) {
            return;
        }
        $this->cache[$workflow_id] = [];
        if (! $this->explicit_backlog_dao->isProjectUsingExplicitBacklog((int) $workflow->getTracker()->getGroupId())) {
            return;
        }
        foreach ($this->add_to_top_backlog_post_action_dao->searchByWorkflow($workflow) as $row) {
            $this->cache[$workflow_id][$row['transition_id']] = $row['id'];
        }
    }

    /**
     * @return AddToTopBacklog[]
     * @throws \Tuleap\Tracker\Workflow\Transition\OrphanTransitionException
     */
    public function loadPostActions(Transition $transition): array
    {
        $workflow_id = (int) $transition->getWorkflow()->getId();
        if (isset($this->cache[$workflow_id])) {
            $transition_id = (int) $transition->getId();
            if (isset($this->cache[$workflow_id][$transition_id])) {
                return [
                    new AddToTopBacklog(
                        $transition,
                        $this->cache[$workflow_id][$transition_id],
                        $this->unplanned_artifacts_adder
                    )
                ];
            }
            return [];
        }


        $project_id = (int) $transition->getGroupId();
        if (! $this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            return [];
        }

        $row = $this->add_to_top_backlog_post_action_dao->searchByTransitionId((int) $transition->getId());
        if ($row !== null) {
            return [
                new AddToTopBacklog(
                    $transition,
                    (int) $row['id'],
                    $this->unplanned_artifacts_adder
                )
            ];
        }

        return [];
    }

    public function saveObject(Transition_PostAction $post_action)
    {
        $to_transition_id = (int) $post_action->getTransition()->getId();

        $this->add_to_top_backlog_post_action_dao->createPostActionForTransitionId(
            $to_transition_id
        );
    }

    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field)
    {
        //Does nothing
        return false;
    }

    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping)
    {
        $postactions = $this->loadPostActions($from_transition);
        if (count($postactions) > 0) {
            $this->add_to_top_backlog_post_action_dao->createPostActionForTransitionId(
                (int) $to_transition_id
            );
        }
    }

    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition)
    {
        return new AddToTopBacklog(
            $transition,
            0,
            $this->unplanned_artifacts_adder
        );
    }
}
