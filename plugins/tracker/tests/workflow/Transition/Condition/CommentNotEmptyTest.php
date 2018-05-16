<?php

/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once __DIR__.'/../../../bootstrap.php';

class CommentNotEmpty_validateTest extends TuleapTestCase
{
    private $empty_data = '';

    public function setUp()
    {
        parent::setUp();
        $this->dao        = mock('Workflow_Transition_Condition_CommentNotEmpty_Dao');
        $this->transition = stub('Transition')->getId()->returns(42);
        $this->artifact   = mock('Tracker_Artifact');
    }

    public function itReturnsTrueIfCommentIsNotRequired()
    {
        $is_comment_required = false;
        $condition = new Workflow_Transition_Condition_CommentNotEmpty(
            $this->transition,
            $this->dao,
            $is_comment_required
        );

        $this->assertTrue($condition->validate($this->empty_data, $this->artifact, 'coin'));
        $this->assertTrue($condition->validate($this->empty_data, $this->artifact, ''));
    }

    public function itReturnsFalseIfCommentIsRequiredAndNoCommentIsProvided()
    {
        $is_comment_required = true;
        $condition = new Workflow_Transition_Condition_CommentNotEmpty(
            $this->transition,
            $this->dao,
            $is_comment_required
        );

        $this->assertFalse($condition->validate($this->empty_data, $this->artifact, ''));
    }

    public function itReturnsTrueIfCommentIsRequiredAndCommentIsProvided()
    {
        $is_comment_required = true;
        $condition = new Workflow_Transition_Condition_CommentNotEmpty(
            $this->transition,
            $this->dao,
            $is_comment_required
        );

        $this->assertTrue($condition->validate($this->empty_data, $this->artifact, 'coin'));
    }
}
