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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use EventManager;
use PFUser;
use Tracker;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedChecker;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedDAO;
use Tuleap\Tracker\Artifact\Artifact;

final class ArtifactMoveButtonPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private PFUser $user;
    private EventManager&\PHPUnit\Framework\MockObject\MockObject $event_manager;
    private Artifact&\PHPUnit\Framework\MockObject\MockObject $artifact;
    private Tracker&\PHPUnit\Framework\MockObject\MockObject $tracker;

    public function setUp(): void
    {
        $this->event_manager = $this->createMock(EventManager::class);

        $this->user     = UserTestBuilder::anActiveUser()->build();
        $this->artifact = $this->createMock(Artifact::class);
        $this->tracker  = $this->createMock(Tracker::class);

        $this->tracker->method('getGroupId')->willReturn(101);
        $this->tracker->method('getId')->willReturn(999);
        $this->tracker->method('getName')->willReturn('tracker01');
        $this->artifact->method('getTracker')->willReturn($this->tracker);
    }

    public function testItDontCollectAnythingIfUserIsNotAdministrator(): void
    {
        $this->tracker->method('userIsAdmin')->willReturn(false);

        $move_button_builder = new ArtifactMoveButtonPresenterBuilder(
            $this->event_manager,
            new MoveActionAllowedChecker($this->createMock(MoveActionAllowedDAO::class)),
        );

        $built_presenter = $move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        self::assertNull($built_presenter);
    }

    public function testItCollectsErrorWhenMoveActionIsForbidden(): void
    {
        $this->tracker->method('userIsAdmin')->willReturn(true);
        $this->event_manager->method('processEvent');

        $move_button_builder = new ArtifactMoveButtonPresenterBuilder(
            $this->event_manager,
            new MoveActionAllowedChecker(
                new class extends MoveActionAllowedDAO {
                    public function isMoveActionAllowedInTracker(int $tracker_id): bool
                    {
                        return false;
                    }
                }
            ),
        );

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            ["Move action is not enabled for tracker tracker01"],
        );

        $built_presenter = $move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        self::assertEquals($built_presenter, $expected_presenter);
    }

    public function testItReturnAButtonWhenUserCanPerformTheMove(): void
    {
        $this->tracker->method('userIsAdmin')->willReturn(true);
        $this->event_manager->method('processEvent');
        $this->tracker->method('hasSemanticsTitle')->willReturn(true);
        $this->artifact->method('getLinkedAndReverseArtifacts')->willReturn([]);

        $move_button_builder = new ArtifactMoveButtonPresenterBuilder(
            $this->event_manager,
            new MoveActionAllowedChecker(
                new class extends MoveActionAllowedDAO {
                    public function isMoveActionAllowedInTracker(int $tracker_id): bool
                    {
                        return true;
                    }
                }
            ),
        );

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            []
        );

        $built_presenter = $move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        self::assertEquals($built_presenter, $expected_presenter);
    }

    public function testItReturnAButtonWhenUserCanPerformTheMoveBasedOnDuckTypingEvenIfNoSemanticIsDefined(): void
    {
        $this->tracker->method('userIsAdmin')->willReturn(true);
        $this->event_manager->method('processEvent');
        $this->tracker->method('hasSemanticsTitle')->willReturn(false);
        $this->tracker->method('hasSemanticsDescription')->willReturn(false);
        $this->tracker->method('hasSemanticsStatus')->willReturn(false);
        $this->tracker->method('getContributorField')->willReturn(null);
        $this->artifact->method('getLinkedAndReverseArtifacts')->willReturn([]);

        $move_button_builder = new ArtifactMoveButtonPresenterBuilder(
            $this->event_manager,
            new MoveActionAllowedChecker(
                new class extends MoveActionAllowedDAO {
                    public function isMoveActionAllowedInTracker(int $tracker_id): bool
                    {
                        return true;
                    }
                }
            ),
        );

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            []
        );
        $built_presenter    = $move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        self::assertEquals($built_presenter, $expected_presenter);
    }
}
