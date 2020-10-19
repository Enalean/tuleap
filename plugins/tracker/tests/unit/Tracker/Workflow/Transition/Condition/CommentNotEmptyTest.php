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

namespace Tuleap\Tracker\Workflow\Transition\Condition;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Transition;
use Tuleap\Tracker\Artifact\Artifact;
use Workflow_Transition_Condition_CommentNotEmpty;
use Workflow_Transition_Condition_CommentNotEmpty_Dao;

require_once __DIR__ . '/../../../../bootstrap.php';

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class CommentNotEmpty_validateTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $empty_data = '';
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $current_user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao          = Mockery::mock(Workflow_Transition_Condition_CommentNotEmpty_Dao::class);
        $this->transition   = Mockery::mock(Transition::class);
        $this->artifact     = Mockery::mock(Artifact::class);
        $this->current_user = \Mockery::spy(\PFUser::class);

        $this->transition->shouldReceive('getId')->andReturn(42);

        $GLOBALS['Response'] = \Mockery::spy(\Layout::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Response']);

        parent::tearDown();
    }

    public function testItReturnsTrueIfCommentIsNotRequired()
    {
        $is_comment_required = false;
        $condition = new Workflow_Transition_Condition_CommentNotEmpty(
            $this->transition,
            $this->dao,
            $is_comment_required
        );

        $this->assertTrue($condition->validate($this->empty_data, $this->artifact, 'coin', $this->current_user));
        $this->assertTrue($condition->validate($this->empty_data, $this->artifact, '', $this->current_user));
    }

    public function testItReturnsFalseIfCommentIsRequiredAndNoCommentIsProvided()
    {
        $is_comment_required = true;
        $condition = new Workflow_Transition_Condition_CommentNotEmpty(
            $this->transition,
            $this->dao,
            $is_comment_required
        );

        $this->assertFalse($condition->validate($this->empty_data, $this->artifact, '', $this->current_user));
    }

    public function testItReturnsTrueIfCommentIsRequiredAndCommentIsProvided()
    {
        $is_comment_required = true;
        $condition = new Workflow_Transition_Condition_CommentNotEmpty(
            $this->transition,
            $this->dao,
            $is_comment_required
        );

        $this->assertTrue($condition->validate($this->empty_data, $this->artifact, 'coin', $this->current_user));
    }
}
