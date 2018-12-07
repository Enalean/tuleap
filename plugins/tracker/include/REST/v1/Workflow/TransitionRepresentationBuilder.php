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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

use Transition;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Workflow\Transition\Condition\Visitor;
use Workflow_Transition_Condition_CommentNotEmpty;
use Workflow_Transition_Condition_FieldNotEmpty;
use Workflow_Transition_Condition_Permissions;

class TransitionRepresentationBuilder implements Visitor
{
    /**
     * @var Transition
     */
    private $transition;

    /**
     * @var string[] Ids of authorized user groups {@type string}
     */
    private $authorized_user_group_ids;

    /**
     * @var int[] Ids of not empty fields {@type int}
     */
    private $not_empty_field_ids;

    /**
     * @var bool
     */
    private $is_comment_required;

    public function __construct(Transition $transition)
    {
        $this->transition = $transition;
    }

    /**
     * @throws OrphanTransitionException
     */
    public function build()
    {
        $id = JsonCast::toInt($this->transition->getId());
        $from_id = JsonCast::toInt($this->transition->getIdFrom());
        $to_id = JsonCast::toInt($this->transition->getIdTo());

        $conditions = $this->transition->getConditions()->getConditions();
        foreach ($conditions as &$condition) {
            $condition->accept($this);
        }

        return new TransitionRepresentation(
            $id,
            $from_id,
            $to_id,
            $this->authorized_user_group_ids,
            $this->not_empty_field_ids,
            $this->is_comment_required
        );
    }

    /**
     * @throws OrphanTransitionException
     */
    public function visitPermissions(Workflow_Transition_Condition_Permissions $condition)
    {
        $project_id = $this->getProjectId();
        $this->authorized_user_group_ids = array_map(
            function ($group) use ($project_id) {
                return UserGroupRepresentation::getRESTIdForProject($project_id, $group['ugroup_id']);
            },
            $condition->getAuthorizedUGroupsAsArray()
        );
    }

    public function visitFieldNotEmpty(Workflow_Transition_Condition_FieldNotEmpty $condition)
    {
        $this->not_empty_field_ids = JsonCast::toArrayOfInts($condition->getFieldIds());
    }

    public function visitCommentNotEmpty(Workflow_Transition_Condition_CommentNotEmpty $condition)
    {
        $this->is_comment_required = $condition->isCommentRequired();
    }

    /**
     * @return int
     * @throws OrphanTransitionException
     */
    private function getProjectId()
    {
        $workflow = $this->transition->getWorkflow();
        if ($workflow === null) {
            throw new OrphanTransitionException($this->transition);
        }
        return $workflow->getTracker()->getProject()->getID();
    }
}
