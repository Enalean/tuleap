<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use AgileDashboard_FirstScrumCreator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\ISerializeFeedback;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Plugin\IsProjectAllowedToUsePlugin;
use Tuleap\Plugin\IsProjectAllowedToUsePluginStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\FeedbackSerializerStub;

final class CreateBacklogControllerTest extends TestCase
{
    public function testExceptionWhenProjectIsNotAllowed(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();

        $this->expectException(ForbiddenException::class);

        $this->getController(
            FeedbackSerializerStub::buildSelf(),
            IsProjectAllowedToUsePluginStub::projectIsNotAllowed(),
            $this->createStub(AgileDashboard_FirstScrumCreator::class),
            $this->createStub(ScrumForMonoMilestoneChecker::class),
            EventDispatcherStub::withIdentityCallback(),
        )->handle((new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, $user));
    }

    public function testExceptionWhenServiceIsNotActivated(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()
            ->withoutServices()
            ->build();

        $this->expectException(ForbiddenException::class);

        $this->getController(
            FeedbackSerializerStub::buildSelf(),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            $this->createStub(AgileDashboard_FirstScrumCreator::class),
            $this->createStub(ScrumForMonoMilestoneChecker::class),
            EventDispatcherStub::withIdentityCallback(),
        )->handle((new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, $user));
    }

    public function testNoScrumCreationWhenMonoMilestone(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();
        $project->addUsedServices([\AgileDashboardPlugin::PLUGIN_SHORTNAME, $this->createStub(AgileDashboardService::class)]);

        $mono_milestone_checker = $this->createMock(ScrumForMonoMilestoneChecker::class);
        $mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(true);

        $first_scrum_creator = $this->createMock(AgileDashboard_FirstScrumCreator::class);
        $first_scrum_creator->expects(self::never())->method('createFirstScrum');

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $this->getController(
            $feedback_serializer,
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            $first_scrum_creator,
            $mono_milestone_checker,
            EventDispatcherStub::withIdentityCallback(),
        )->handle((new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, $user));

        self::assertEquals(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    public function testNoScrumCreationWhenAdministrationIsDelegated(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();
        $project->addUsedServices([\AgileDashboardPlugin::PLUGIN_SHORTNAME, $this->createStub(AgileDashboardService::class)]);

        $mono_milestone_checker = $this->createMock(ScrumForMonoMilestoneChecker::class);
        $mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $first_scrum_creator = $this->createMock(AgileDashboard_FirstScrumCreator::class);
        $first_scrum_creator->expects(self::never())->method('createFirstScrum');

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $this->getController(
            $feedback_serializer,
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            $first_scrum_creator,
            $mono_milestone_checker,
            EventDispatcherStub::withCallback(static function (object $event): object {
                if ($event instanceof PlanningAdministrationDelegation) {
                    $event->enablePlanningAdministrationDelegation();
                }
                return $event;
            }),
        )->handle((new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, $user));

        self::assertEquals(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    public function testScrumCreation(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();
        $project->addUsedServices([\AgileDashboardPlugin::PLUGIN_SHORTNAME, $this->createStub(AgileDashboardService::class)]);

        $mono_milestone_checker = $this->createMock(ScrumForMonoMilestoneChecker::class);
        $mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $first_scrum_creator = $this->createMock(AgileDashboard_FirstScrumCreator::class);
        $first_scrum_creator->expects(self::once())->method('createFirstScrum')->willReturn(NewFeedback::success('yay!'));

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $this->getController(
            $feedback_serializer,
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            $first_scrum_creator,
            $mono_milestone_checker,
            EventDispatcherStub::withIdentityCallback(),
        )->handle((new NullServerRequest())
            ->withAttribute(\Project::class, $project)
            ->withAttribute(\PFUser::class, $user));

        $feedback = $feedback_serializer->getCapturedFeedbacks()[0];
        self::assertEquals(\Feedback::SUCCESS, $feedback->getLevel());
        self::assertEquals('yay!', $feedback->getMessage());
    }

    private function getController(
        ISerializeFeedback $feedback_serializer,
        IsProjectAllowedToUsePlugin $plugin,
        AgileDashboard_FirstScrumCreator $first_scrum_creator,
        ScrumForMonoMilestoneChecker $mono_milestone_checker,
        EventDispatcherInterface $event_dispatcher,
    ): CreateBacklogController {
        return new CreateBacklogController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $plugin,
            $first_scrum_creator,
            $mono_milestone_checker,
            $event_dispatcher,
            new NoopSapiEmitter(),
        );
    }
}
