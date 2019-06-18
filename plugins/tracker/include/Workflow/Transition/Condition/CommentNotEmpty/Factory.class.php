<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class Workflow_Transition_Condition_CommentNotEmpty_Factory //phpcs:ignore
{
    /** @var Workflow_Transition_Condition_CommentNotEmpty_Dao */
    private $dao;

    public function __construct(Workflow_Transition_Condition_CommentNotEmpty_Dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return Workflow_Transition_Condition_CommentNotEmpty|null
     */
    public function getCommentNotEmpty(Transition $transition)
    {
        if ($transition->getIdFrom() === '') {
            return null;
        }

        $row = $this->dao->searchByTransitionId($transition->getId());
        $is_comment_required = $row && $row['is_comment_required'];

        return new Workflow_Transition_Condition_CommentNotEmpty($transition, $this->dao, $is_comment_required);
    }

    /**
     * @return Workflow_Transition_Condition_CommentNotEmpty|null
     */
    public function getInstanceFromXML(SimpleXMLElement $xml, Transition $transition)
    {
        if ($transition->getIdFrom() === '') {
            return null;
        }

        $xml_attributes      = $xml->attributes();
        $is_comment_required = false;
        if (isset($xml_attributes['is_comment_required'])) {
            $is_comment_required = (string) $xml_attributes['is_comment_required'];
        }

        return new Workflow_Transition_Condition_CommentNotEmpty($transition, $this->dao, $is_comment_required);
    }

    /**
     * Duplicate the conditions
     */
    public function duplicate(
        Transition $from_transition,
        $new_transition_id,
        $field_mapping,
        $ugroup_mapping,
        $duplicate_type
    ) {
        $this->dao->duplicate($from_transition->getId(), $new_transition_id);
    }
}
