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

use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedDAO;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PostCreationProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker
     */
    private $tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PromotedTrackerDao
     */
    private $in_new_dropdown_dao;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerPrivateCommentUGroupEnabledDao
     */
    private $private_comment_dao;
    private MoveActionAllowedDAO&\PHPUnit\Framework\MockObject\MockObject $move_action_allowed_dao;
    private PostCreationProcessor $processor;

    protected function setUp(): void
    {
        $this->reference_manager       = $this->createMock(\ReferenceManager::class);
        $this->in_new_dropdown_dao     = $this->createMock(PromotedTrackerDao::class);
        $this->private_comment_dao     = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $this->move_action_allowed_dao = $this->createMock(MoveActionAllowedDAO::class);

        $this->processor = new PostCreationProcessor(
            $this->reference_manager,
            $this->in_new_dropdown_dao,
            $this->private_comment_dao,
            $this->move_action_allowed_dao,
        );

        $this->tracker = TrackerTestBuilder::aTracker()
            ->withId(10)
            ->withName("Bug")
            ->withShortName("bug")
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $this->reference_manager->expects(self::once())->method('createReference');
    }

    public function testItAddsReferences(): void
    {
        $settings = new TrackerCreationSettings(false, true, true);

        $this->processor->postCreationProcess($this->tracker, $settings);
    }

    public function testItAddsTrackerInDropDownIfSettingsSaidSo(): void
    {
        $settings = new TrackerCreationSettings(true, true, true);
        $this->in_new_dropdown_dao->expects(self::once())->method('insert')->with($this->tracker->getId());

        $this->processor->postCreationProcess($this->tracker, $settings);
    }

    public function testItDoesNotAddTrackerInDropdown(): void
    {
        $settings = new TrackerCreationSettings(false, true, true);
        $this->in_new_dropdown_dao->expects(self::never())->method('insert')->with($this->tracker->getId());

        $this->processor->postCreationProcess($this->tracker, $settings);
    }

    public function testItDisablesPrivateCommentOnTracker(): void
    {
        $settings = new TrackerCreationSettings(false, false, true);
        $this->private_comment_dao->expects(self::once())->method('disabledPrivateCommentOnTracker')->with($this->tracker->getId());

        $this->processor->postCreationProcess($this->tracker, $settings);
    }

    public function testItForbidsMoveOfArtifactsInTracker(): void
    {
        $settings = new TrackerCreationSettings(false, true, false);
        $this->move_action_allowed_dao->expects(self::once())->method('forbidMoveArtifactInTracker')->with($this->tracker->getId());

        $this->processor->postCreationProcess($this->tracker, $settings);
    }
}
