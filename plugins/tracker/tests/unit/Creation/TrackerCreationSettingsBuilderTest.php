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

namespace Tuleap\Tracker\Creation;

use Tracker;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedDAO;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerCreationSettingsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PromotedTrackerDao
     */
    private $in_new_dropdown_dao;
    /**
     * @var TrackerCreationSettingsBuilder
     */
    private $builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerPrivateCommentUGroupEnabledDao
     */
    private $private_comment_dao;
    private Tracker $tracker;
    private MoveActionAllowedDAO&\PHPUnit\Framework\MockObject\MockObject $move_action_allowed_dao;

    protected function setUp(): void
    {
        $this->in_new_dropdown_dao     = $this->createMock(PromotedTrackerDao::class);
        $this->private_comment_dao     = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $this->move_action_allowed_dao = $this->createMock(MoveActionAllowedDAO::class);
        $this->tracker                 = TrackerTestBuilder::aTracker()->withId(10)->build();

        $this->builder = new TrackerCreationSettingsBuilder(
            $this->in_new_dropdown_dao,
            $this->private_comment_dao,
            $this->move_action_allowed_dao,
        );
    }

    public function testItBuildTrackerCreationSettings(): void
    {
        $this->in_new_dropdown_dao->method('isContaining')->with(10)->willReturn(true);
        $this->private_comment_dao->method('isTrackerEnabledPrivateComment')->with(10)->willReturn(true);
        $this->move_action_allowed_dao->method('isMoveActionAllowedInTracker')->with(10)->willReturn(true);

        $expected = new TrackerCreationSettings(true, true, true);
        $result   = $this->builder->build($this->tracker);

        self::assertEquals($expected->isDisplayedInNewDropdown(), $result->isDisplayedInNewDropdown());
        self::assertEquals($expected->isPrivateCommentUsed(), $result->isPrivateCommentUsed());
        self::assertEquals($expected->isMoveArtifactsEnabled(), $result->isMoveArtifactsEnabled());
    }
}
