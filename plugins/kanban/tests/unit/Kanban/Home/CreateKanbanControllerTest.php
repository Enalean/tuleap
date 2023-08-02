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

namespace Tuleap\Kanban\Home;

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Kanban\KanbanManager;
use Tuleap\Layout\Feedback\ISerializeFeedback;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CreateKanbanControllerTest extends TestCase
{
    public function testCreateKanban(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $kanban_manager = $this->createMock(KanbanManager::class);
        $kanban_manager->method('doesKanbanExistForTracker')->willReturn(false);
        $kanban_manager->method('createKanban')->willReturn(1001);

        $response = $this->getController($feedback_serializer, $kanban_manager, $tracker)
            ->handle((new NullServerRequest())
                ->withAttribute(\Project::class, $project)
                ->withAttribute(\PFUser::class, $user)
                ->withParsedBody([
                    'kanban-name' => 'My Kanban',
                    'tracker-kanban' => 123,
                ]));

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    public function testExceptionWhenNoKanbanNameProvided(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $kanban_manager = $this->createMock(KanbanManager::class);

        $this->expectException(ForbiddenException::class);

        $this->getController($feedback_serializer, $kanban_manager, $tracker)
            ->handle((new NullServerRequest())
                ->withAttribute(\Project::class, $project)
                ->withAttribute(\PFUser::class, $user)
                ->withParsedBody([
                    'kanban-name' => 'My Kanban',
                ]));
    }

    public function testExceptionWhenNoTrackerProvided(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $kanban_manager = $this->createMock(KanbanManager::class);

        $this->expectException(ForbiddenException::class);

        $this->getController($feedback_serializer, $kanban_manager, $tracker)
            ->handle((new NullServerRequest())
                ->withAttribute(\Project::class, $project)
                ->withAttribute(\PFUser::class, $user)
                ->withParsedBody([
                    'tracker-kanban' => 123,
                ]));
    }

    public function testExceptionWhenTrackerDoesNotExist(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $user    = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();
        $tracker = null;

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $kanban_manager = $this->createMock(KanbanManager::class);

        $this->expectException(ForbiddenException::class);

        $this->getController($feedback_serializer, $kanban_manager, $tracker)
            ->handle((new NullServerRequest())
                ->withAttribute(\Project::class, $project)
                ->withAttribute(\PFUser::class, $user)
                ->withParsedBody([
                    'tracker-kanban' => 123,
                ]));
    }

    public function testExceptionWhenTrackerBelongsToAnotherProject(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(101)->build();
        $user            = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();
        $another_project = ProjectTestBuilder::aProject()->withId(102)->build();
        $tracker         = TrackerTestBuilder::aTracker()->withProject($another_project)->build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $kanban_manager = $this->createMock(KanbanManager::class);

        $this->expectException(ForbiddenException::class);

        $this->getController($feedback_serializer, $kanban_manager, $tracker)
            ->handle((new NullServerRequest())
                ->withAttribute(\Project::class, $project)
                ->withAttribute(\PFUser::class, $user)
                ->withParsedBody([
                    'tracker-kanban' => 123,
                ]));
    }

    public function testErrorWhenKanbanAlreadyExists(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $kanban_manager = $this->createMock(KanbanManager::class);
        $kanban_manager->method('doesKanbanExistForTracker')->willReturn(true);

        $response = $this->getController($feedback_serializer, $kanban_manager, $tracker)
            ->handle((new NullServerRequest())
                ->withAttribute(\Project::class, $project)
                ->withAttribute(\PFUser::class, $user)
                ->withParsedBody([
                    'kanban-name' => 'My Kanban',
                    'tracker-kanban' => 123,
                ]));

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
    }

    private function getController(
        ISerializeFeedback $feedback_serializer,
        KanbanManager $kanban_manager,
        ?\Tracker $tracker,
    ): CreateKanbanController {
        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturn($tracker);

        return new CreateKanbanController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $kanban_manager,
            $tracker_factory,
            new NoopSapiEmitter(),
        );
    }
}
