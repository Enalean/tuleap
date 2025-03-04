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

use Tracker_Artifact_Redirect;
use Tracker_ArtifactFactory;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EventRedirectAfterArtifactCreationOrUpdateProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var ArtifactLinkUpdater&\PHPUnit\Framework\MockObject\MockObject
     */
    private $artifact_link_updater;
    /**
     * @var RedirectParameterInjector&\PHPUnit\Framework\MockObject\MockObject
     */
    private $redirect_parameter_injector;

    private EventRedirectAfterArtifactCreationOrUpdateProcessor $processor;

    public function setUp(): void
    {
        $this->user = $this->createMock(\PFUser::class);

        $this->artifact_factory            = $this->createMock(Tracker_ArtifactFactory::class);
        $this->artifact_link_updater       = $this->createMock(ArtifactLinkUpdater::class);
        $this->redirect_parameter_injector = $this->createMock(RedirectParameterInjector::class);

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
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->processor->process($request, $redirect, $artifact);

        self::assertEquals('', $redirect->base_url);
        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNotDoAnythingIfThereIsNoMilestoneIdInTheRequest(): void
    {
        $request  = $this->aRequest(
            [
                'ttm_backlog_item_id' => '123',
            ]
        );
        $redirect = new Tracker_Artifact_Redirect();
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->processor->process($request, $redirect, $artifact);

        self::assertEquals('', $redirect->base_url);
        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNothingIfBacklogItemCannotBeInstanciated(): void
    {
        $request        = $this->aRequest(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ]
        );
        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact       = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactById')
            ->with('123')
            ->willReturn(null);

        $this->processor->process($request, $redirect, $artifact);

        self::assertEquals('', $redirect->base_url);
        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNothingIfBacklogItemDoesNotHaveAnArtifactLinkField(): void
    {
        $request        = $this->aRequest(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ]
        );
        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact       = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn(1001);

        $backlog_item = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->method('getId')->willReturn(123);
        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactById')
            ->with('123')
            ->willReturn($backlog_item);

        $this->artifact_link_updater
            ->expects(self::once())
            ->method('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            )
            ->willThrowException(new \Tracker_NoArtifactLinkFieldException());

        $this->processor->process($request, $redirect, $artifact);

        self::assertEquals('', $redirect->base_url);
        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItDoesNothingIfBacklogItemCanotBeLinkedToNewArtifact(): void
    {
        $request        = $this->aRequest(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ]
        );
        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact       = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn(1001);

        $backlog_item = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->method('getId')->willReturn(123);
        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactById')
            ->with('123')
            ->willReturn($backlog_item);

        $this->artifact_link_updater
            ->expects(self::once())
            ->method('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            )
            ->willThrowException(new \Tracker_Exception());
        $GLOBALS['Response']
            ->expects(self::once())
            ->method('addFeedback')
            ->with('warning', 'Unable to link the backlog item to the new artifact');

        $this->processor->process($request, $redirect, $artifact);

        self::assertEquals('', $redirect->base_url);
        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItRedirectsToTestPlanOfTheMilestone(): void
    {
        $request        = $this->aRequest(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ]
        );
        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact       = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn(1001);

        $tracker = $this->createMock(\Tracker::class);
        $project = $this->createMock(\Project::class);

        $project->method('getUnixNameMixedCase')->willReturn('my-project');
        $tracker->method('getProject')->willReturn($project);
        $artifact->method('getTracker')->willReturn($tracker);

        $backlog_item = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->method('getId')->willReturn(123);
        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactById')
            ->with('123')
            ->willReturn($backlog_item);

        $this->artifact_link_updater
            ->expects(self::once())
            ->method('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            );

        $this->processor->process($request, $redirect, $artifact);

        self::assertEquals('/testplan/my-project/42/backlog_item/123/test/1001', $redirect->base_url);
        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItRedirectsToTestPlanOfTheMilestoneWhenABacklogItemIsEdited(): void
    {
        $request        = $this->aRequest(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ]
        );
        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $artifact       = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn(123);

        $tracker = $this->createMock(\Tracker::class);
        $project = $this->createMock(\Project::class);

        $project->method('getUnixNameMixedCase')->willReturn('my-project');
        $tracker->method('getProject')->willReturn($project);
        $artifact->method('getTracker')->willReturn($tracker);

        $backlog_item = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->method('getId')->willReturn(123);
        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactById')
            ->with('123')
            ->willReturn($backlog_item);

        $this->artifact_link_updater->expects(self::never())->method('updateArtifactLinks');

        $this->processor->process($request, $redirect, $artifact);

        self::assertEquals('/testplan/my-project/42/backlog_item/123', $redirect->base_url);
        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItInjectsRedirectParametersIfWeChooseToContinue(): void
    {
        $request        = $this->aRequest(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ]
        );
        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_CONTINUE;
        $artifact       = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn(1001);

        $tracker = $this->createMock(\Tracker::class);
        $project = $this->createMock(\Project::class);

        $project->method('getUnixNameMixedCase')->willReturn('my-project');
        $tracker->method('getProject')->willReturn($project);
        $artifact->method('getTracker')->willReturn($tracker);

        $backlog_item = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->method('getId')->willReturn(123);
        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactById')
            ->with('123')
            ->willReturn($backlog_item);

        $this->artifact_link_updater
            ->expects(self::once())
            ->method('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            );

        $this->redirect_parameter_injector->method('injectParameters')->with($redirect, '123', '42');

        $this->processor->process($request, $redirect, $artifact);
    }

    public function testItDoesNotInjectAnythingIfWeChooseToStayInTracker(): void
    {
        $request        = $this->aRequest(
            [
                'ttm_backlog_item_id' => '123',
                'ttm_milestone_id'    => '42',
            ]
        );
        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_STAY;
        $artifact       = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn(1001);

        $tracker = $this->createMock(\Tracker::class);
        $project = $this->createMock(\Project::class);

        $project->method('getUnixNameMixedCase')->willReturn('my-project');
        $tracker->method('getProject')->willReturn($project);
        $artifact->method('getTracker')->willReturn($tracker);

        $backlog_item = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $backlog_item->method('getId')->willReturn(123);
        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactById')
            ->with('123')
            ->willReturn($backlog_item);

        $this->artifact_link_updater
            ->expects(self::once())
            ->method('updateArtifactLinks')
            ->with(
                $this->user,
                $backlog_item,
                [1001],
                [],
                '_covered_by'
            );

        $this->processor->process($request, $redirect, $artifact);

        self::assertEquals('', $redirect->base_url);
        self::assertEquals([], $redirect->query_parameters);
    }

    private function aRequest(array $params): \Codendi_Request
    {
        $request = new \Codendi_Request($params, $this->createMock(\ProjectManager::class));
        $request->setCurrentUser($this->user);

        return $request;
    }
}
