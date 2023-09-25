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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Stub\RetrieveActionDeletionLimitStub;

require_once __DIR__ . '/../../bootstrap.php';

final class ArtifactMoveButtonPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    private PFUser $user;
    /**
     * @var EventManager|(EventManager&Mockery\LegacyMockInterface)|(EventManager&Mockery\MockInterface)|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface $event_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact|(Artifact&Mockery\LegacyMockInterface)|(Artifact&Mockery\MockInterface)
     */
    private Artifact|Mockery\MockInterface|Mockery\LegacyMockInterface $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker|(Tracker&Mockery\LegacyMockInterface)|(Tracker&Mockery\MockInterface)
     */
    private Tracker|Mockery\MockInterface|Mockery\LegacyMockInterface $tracker;

    public function setUp(): void
    {
        $this->event_manager = Mockery::mock(EventManager::class);

        $this->user     = UserTestBuilder::anActiveUser()->build();
        $this->artifact = Mockery::mock(Artifact::class);
        $this->tracker  = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getGroupId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);
    }

    public function testItDontCollectAnythingIfUserIsNotAdministrator(): void
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(false);

        $deletion_limit_retriever = RetrieveActionDeletionLimitStub::retrieveRandomLimit();
        $move_button_builder      = new ArtifactMoveButtonPresenterBuilder(
            $deletion_limit_retriever,
            $this->event_manager
        );

        $built_presenter = $move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertNull($built_presenter);
    }

    public function testItCollectsErrorWhenLimitIsSetToZero(): void
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->tracker->shouldReceive('hasSemanticsTitle')->andReturn(true);
        $this->artifact->shouldReceive('getLinkedAndReverseArtifacts')->andReturns([]);

        $deletion_limit_retriever = RetrieveActionDeletionLimitStub::andThrowDeletionIsNotAllowed();
        $move_button_builder      = new ArtifactMoveButtonPresenterBuilder(
            $deletion_limit_retriever,
            $this->event_manager
        );

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            ["Deletion of artifacts is not allowed"]
        );

        $built_presenter = $move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertEquals($built_presenter, $expected_presenter);
    }

    public function testItCollectsErrorWhenLimitIsReached(): void
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->tracker->shouldReceive('hasSemanticsTitle')->andReturn(true);
        $this->artifact->shouldReceive('getLinkedAndReverseArtifacts')->andReturns([]);

        $deletion_limit_retriever = RetrieveActionDeletionLimitStub::andThrowLimitIsReached();
        $move_button_builder      = new ArtifactMoveButtonPresenterBuilder(
            $deletion_limit_retriever,
            $this->event_manager
        );

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            ["The limit of artifacts deletions has been reached for the previous 24 hours."]
        );

        $built_presenter = $move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertEquals($built_presenter, $expected_presenter);
    }

    public function testItReturnAButtonWhenUserCanPerformTheMove(): void
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->tracker->shouldReceive('hasSemanticsTitle')->andReturn(true);
        $this->artifact->shouldReceive('getLinkedAndReverseArtifacts')->andReturns([]);

        $deletion_limit_retriever = RetrieveActionDeletionLimitStub::retrieveRandomLimit();
        $move_button_builder      = new ArtifactMoveButtonPresenterBuilder(
            $deletion_limit_retriever,
            $this->event_manager
        );

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            []
        );

        $built_presenter = $move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertEquals($built_presenter, $expected_presenter);
    }

    public function testItReturnAButtonWhenUserCanPerformTheMoveBasedOnDuckTypingEvenIfNoSemanticIsDefined(): void
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->tracker->shouldReceive('hasSemanticsTitle')->andReturn(false);
        $this->tracker->shouldReceive('hasSemanticsDescription')->andReturn(false);
        $this->tracker->shouldReceive('hasSemanticsStatus')->andReturn(false);
        $this->tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->artifact->shouldReceive('getLinkedAndReverseArtifacts')->andReturns([]);

        $deletion_limit_retriever = RetrieveActionDeletionLimitStub::retrieveRandomLimit();
        $move_button_builder      = new ArtifactMoveButtonPresenterBuilder(
            $deletion_limit_retriever,
            $this->event_manager
        );

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            []
        );
        $built_presenter    = $move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertEquals($built_presenter, $expected_presenter);
    }
}
