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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\Transition\Condition;

use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Workflow_Transition_Condition_CommentNotEmpty;
use Workflow_Transition_Condition_CommentNotEmpty_Dao;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentNotEmptyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private string $empty_data = '';
    private \PFUser $current_user;
    private Workflow_Transition_Condition_CommentNotEmpty_Dao&MockObject $dao;
    private Transition $transition;
    private Artifact $artifact;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->dao          = $this->createMock(Workflow_Transition_Condition_CommentNotEmpty_Dao::class);
        $this->transition   = new Transition(
            42,
            101,
            null,
            ListStaticValueBuilder::aStaticValue('Done')->build(),
        );
        $this->artifact     = ArtifactTestBuilder::anArtifact(101)->build();
        $this->current_user = UserTestBuilder::buildWithDefaults();
    }

    public function testItReturnsTrueIfCommentIsNotRequired(): void
    {
        $is_comment_required = false;
        $condition           = new Workflow_Transition_Condition_CommentNotEmpty(
            $this->transition,
            $this->dao,
            $is_comment_required
        );

        $this->assertTrue($condition->validate($this->empty_data, $this->artifact, 'coin', $this->current_user));
        $this->assertTrue($condition->validate($this->empty_data, $this->artifact, '', $this->current_user));
    }

    public function testItReturnsFalseIfCommentIsRequiredAndNoCommentIsProvided(): void
    {
        $is_comment_required = true;
        $condition           = new Workflow_Transition_Condition_CommentNotEmpty(
            $this->transition,
            $this->dao,
            $is_comment_required
        );

        $this->assertFalse($condition->validate($this->empty_data, $this->artifact, '', $this->current_user));
    }

    public function testItReturnsTrueIfCommentIsRequiredAndCommentIsProvided(): void
    {
        $is_comment_required = true;
        $condition           = new Workflow_Transition_Condition_CommentNotEmpty(
            $this->transition,
            $this->dao,
            $is_comment_required
        );

        $this->assertTrue($condition->validate($this->empty_data, $this->artifact, 'coin', $this->current_user));
    }
}
