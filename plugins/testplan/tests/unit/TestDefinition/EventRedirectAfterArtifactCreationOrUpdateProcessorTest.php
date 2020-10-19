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

namespace Tuleap\TestPlan\TestDefinition;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Redirect;
use Tracker_ArtifactFactory;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

final class EventRedirectAfterArtifactCreationOrUpdateProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactLinkUpdater
     */
    private $artifact_link_updater;
    /**
     * @var EventRedirectAfterArtifactCreationOrUpdateProcessor
     */
    private $processor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RedirectParameterInjector
     */
    private $redirect_parameter_injector;

    public function setUp(): void
    {
        $this->user = Mockery::mock(\PFUser::class);

        $this->artifact_factory            = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->artifact_link_updater       = Mockery::mock(ArtifactLinkUpdater::class);
        $this->redirect_parameter_injector = Mockery::mock(RedirectParameterInjector::class);

        $this->processor = new EventRedirectAfterArtifactCreationOrUpdateProcessor(
            $this->artifact_factory,
            $this->artifact_link_updater,
            $this->redirect_parameter_injector,
        );
    }

    public function testItDoesNotDoAnythingIfThereIsNoBacklogItemIdInTheRequest(): void
    {
        $request  = $this->aRequest([]);
        $redirect = new Tracker_Artifact_Redirect();
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->processor->process($request, $redirect, $artifact);

        $this->assertEquals('', $redirect->base_url);
        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNotDoAnythingIfThereIsNoMilestoneIdInTheRequest(): void
    {
        $request  = $this->aRequest(
            [
                'ttm_backlog_item_id' => "123",
            ]
        );
        $redirect = new Tracker_Artifact_Redirect();
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->processor->process($request, $redirect, $artifact);

        $this->assertEquals('', $redirect->base_url);
        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNothingIfBacklogItemCannotBeInstanciated(): void
    {
        $request  = $this->aRequest(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42",
            ]
        );
        $redirect = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with("123")
            ->once()
            ->andReturnNull();

        $this->processor->process($request, $redirect, $artifact);

        $this->assertEquals('', $redirect->base_url);
        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNothingIfBacklogItemDoesNotHaveAnArtifactLinkField(): void
    {
        $request  = $this->aRequest(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42",
            ]
        );
        $redirect = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive(['getId' => 1001]);

        $backlog_item = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->shouldReceive('getId')->andReturn(123);
        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with("123")
            ->once()
            ->andReturn($backlog_item);

        $this->artifact_link_updater
            ->shouldReceive('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            )->once()
            ->andThrow(\Tracker_NoArtifactLinkFieldException::class);

        $this->processor->process($request, $redirect, $artifact);

        $this->assertEquals('', $redirect->base_url);
        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNothingIfBacklogItemCanotBeLinkedToNewArtifact(): void
    {
        $request  = $this->aRequest(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42",
            ]
        );
        $redirect = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive(['getId' => 1001]);

        $backlog_item = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->shouldReceive('getId')->andReturn(123);
        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with("123")
            ->once()
            ->andReturn($backlog_item);

        $this->artifact_link_updater
            ->shouldReceive('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            )->once()
            ->andThrow(\Tracker_Exception::class);
        $GLOBALS['Response']
            ->shouldReceive('addFeedback')
            ->with('warning', 'Unable to link the backlog item to the new artifact')
            ->once();

        $this->processor->process($request, $redirect, $artifact);

        $this->assertEquals('', $redirect->base_url);
        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItRedirectsToTestPlanOfTheMilestone(): void
    {
        $request  = $this->aRequest(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42",
            ]
        );
        $redirect = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive([
            'getId' => 1001,
            'getTracker' => Mockery::mock(\Tracker::class)
                ->shouldReceive([
                    'getProject' => Mockery::mock(\Project::class)
                        ->shouldReceive(['getUnixNameMixedCase' => 'my-project'])
                        ->getMock()
                ])->getMock()
        ]);

        $backlog_item = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->shouldReceive('getId')->andReturn(123);
        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with("123")
            ->once()
            ->andReturn($backlog_item);

        $this->artifact_link_updater
            ->shouldReceive('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            )->once();

        $this->processor->process($request, $redirect, $artifact);

        $this->assertEquals('/testplan/my-project/42/backlog_item/123/test/1001', $redirect->base_url);
        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItRedirectsToTestPlanOfTheMilestoneWhenABacklogItemIsEdited(): void
    {
        $request  = $this->aRequest(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42",
            ]
        );
        $redirect = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive(
            [
                'getId'      => 123,
                'getTracker' => Mockery::mock(\Tracker::class)
                    ->shouldReceive(
                        [
                            'getProject' => Mockery::mock(\Project::class)
                                ->shouldReceive(['getUnixNameMixedCase' => 'my-project'])
                                ->getMock()
                        ]
                    )->getMock()
            ]
        );

        $backlog_item = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->shouldReceive('getId')->andReturn(123);
        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with("123")
            ->once()
            ->andReturn($backlog_item);

        $this->artifact_link_updater->shouldNotReceive('updateArtifactLinks');

        $this->processor->process($request, $redirect, $artifact);

        $this->assertEquals('/testplan/my-project/42/backlog_item/123', $redirect->base_url);
        $this->assertEquals([], $redirect->query_parameters);
    }

    public function testItInjectsRedirectParametersIfWeChooseToContinue(): void
    {
        $request  = $this->aRequest(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42",
            ]
        );
        $redirect = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_CONTINUE;
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive([
            'getId' => 1001,
            'getTracker' => Mockery::mock(\Tracker::class)
                ->shouldReceive([
                    'getProject' => Mockery::mock(\Project::class)
                        ->shouldReceive(['getUnixNameMixedCase' => 'my-project'])
                        ->getMock()
                ])->getMock()
        ]);

        $backlog_item = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->shouldReceive('getId')->andReturn(123);
        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with("123")
            ->once()
            ->andReturn($backlog_item);

        $this->artifact_link_updater
            ->shouldReceive('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            )->once();

        $this->redirect_parameter_injector->shouldReceive('injectParameters')->with($redirect, "123", "42");

        $this->processor->process($request, $redirect, $artifact);
    }

    public function testItDoesNotInjectAnythingIfWeChooseToStayInTracker(): void
    {
        $request  = $this->aRequest(
            [
                'ttm_backlog_item_id' => "123",
                'ttm_milestone_id'    => "42",
            ]
        );
        $redirect = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_STAY;
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive([
            'getId' => 1001,
            'getTracker' => Mockery::mock(\Tracker::class)
                ->shouldReceive([
                    'getProject' => Mockery::mock(\Project::class)
                        ->shouldReceive(['getUnixNameMixedCase' => 'my-project'])
                        ->getMock()
                ])->getMock()
        ]);

        $backlog_item = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->shouldReceive('getId')->andReturn(123);
        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with("123")
            ->once()
            ->andReturn($backlog_item);

        $this->artifact_link_updater
            ->shouldReceive('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            )->once();

        $this->processor->process($request, $redirect, $artifact);

        $this->assertEquals('', $redirect->base_url);
        $this->assertEquals([], $redirect->query_parameters);
    }

    private function aRequest(array $params): \Codendi_Request
    {
        $request = new \Codendi_Request($params, \Mockery::spy(\ProjectManager::class));
        $request->setCurrentUser($this->user);

        return $request;
    }
}
