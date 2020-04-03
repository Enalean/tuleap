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
 */

namespace Tuleap\Tracker\Artifact\ActionButtons;

use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletionLimitRetriever;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactsDeletionLimitReachedException;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionOfArtifactsIsNotAllowedException;

require_once __DIR__ . '/../../bootstrap.php';

class ArtifactMoveButtonPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker
     */
    private $tracker;
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var ArtifactMoveButtonPresenterBuilder
     */
    private $move_button_builder;
    /**
     * @var ArtifactDeletionLimitRetriever
     */
    private $deletion_limit_retriever;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function setUp(): void
    {
        $this->deletion_limit_retriever = Mockery::mock(ArtifactDeletionLimitRetriever::class);
        $this->event_manager            = Mockery::mock(EventManager::class);

        $this->move_button_builder = new ArtifactMoveButtonPresenterBuilder(
            $this->deletion_limit_retriever,
            $this->event_manager
        );

        $this->user     = Mockery::mock(PFUser::class);
        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->tracker  = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getGroupId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);
    }

    public function testItDontCollectAnythingIfUserIsNotAdministrator()
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(false);

        $built_presenter = $this->move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertNull($built_presenter);
    }

    public function testItCollectsErrorWhenLimitIsSetToZero()
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->tracker->shouldReceive('hasSemanticsTitle')->andReturn(true);
        $this->artifact->shouldReceive('getLinkedAndReverseArtifacts')->andReturns([]);

        $this->deletion_limit_retriever->shouldReceive('getNumberOfArtifactsAllowedToDelete')->andThrow(
            new DeletionOfArtifactsIsNotAllowedException()
        );

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            ["Deletion of artifacts is not allowed"]
        );

        $built_presenter = $this->move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertEquals($built_presenter, $expected_presenter);
    }

    public function testItCollectsErrorWhenLimitIsReached()
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->tracker->shouldReceive('hasSemanticsTitle')->andReturn(true);
        $this->artifact->shouldReceive('getLinkedAndReverseArtifacts')->andReturns([]);

        $this->deletion_limit_retriever->shouldReceive('getNumberOfArtifactsAllowedToDelete')->andThrow(
            new ArtifactsDeletionLimitReachedException()
        );

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            ["The limit of artifacts deletions has been reached for the previous 24 hours."]
        );

        $built_presenter = $this->move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertEquals($built_presenter, $expected_presenter);
    }

    public function testItCollectsErrorWhenNoSemanticAreDefined()
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->tracker->shouldReceive('hasSemanticsTitle')->andReturn(false);
        $this->tracker->shouldReceive('hasSemanticsDescription')->andReturn(false);
        $this->tracker->shouldReceive('hasSemanticsStatus')->andReturn(false);
        $this->tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->artifact->shouldReceive('getLinkedAndReverseArtifacts')->andReturns([]);

        $this->deletion_limit_retriever->shouldReceive('getNumberOfArtifactsAllowedToDelete')->andReturn(10);

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            ["No semantic defined in this tracker."]
        );

        $built_presenter = $this->move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertEquals($built_presenter, $expected_presenter);
    }

    public function testItCollectErrorsWhenArtifactHasArtifactLinks()
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->tracker->shouldReceive('hasSemanticsTitle')->andReturn(true);
        $this->artifact->shouldReceive('getLinkedAndReverseArtifacts')->andReturns([\Mockery::mock(Tracker_Artifact::class)]);

        $this->deletion_limit_retriever->shouldReceive('getNumberOfArtifactsAllowedToDelete')->andReturn(10);

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            ["Artifacts with artifact links can not be moved."]
        );

        $built_presenter = $this->move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertEquals($built_presenter, $expected_presenter);
    }

    public function testItReturnAButtonWhenUserCanPerformTheMove()
    {
        $this->tracker->shouldReceive('userIsAdmin')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->tracker->shouldReceive('hasSemanticsTitle')->andReturn(true);
        $this->artifact->shouldReceive('getLinkedAndReverseArtifacts')->andReturns([]);

        $this->deletion_limit_retriever->shouldReceive('getNumberOfArtifactsAllowedToDelete')->andReturn(10);

        $expected_presenter = new ArtifactMoveButtonPresenter(
            dgettext('plugin-tracker', "Move this artifact"),
            []
        );

        $built_presenter = $this->move_button_builder->getMoveArtifactButton($this->user, $this->artifact);

        $this->assertEquals($built_presenter, $expected_presenter);
    }
}
