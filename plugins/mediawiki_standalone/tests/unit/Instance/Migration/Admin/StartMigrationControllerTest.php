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

namespace Tuleap\MediawikiStandalone\Instance\Migration\Admin;

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\MediawikiStandalone\Instance\Migration\MigrateInstanceTask;
use Tuleap\MediawikiStandalone\Instance\OngoingInitializationsStateStub;
use Tuleap\Plugin\IsProjectAllowedToUsePluginStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\EnqueueTaskStub;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StartMigrationControllerTest extends TestCase
{
    public function testStartMigration(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $enqueue_task                  = new EnqueueTaskStub();
        $ongoing_initializations_state = OngoingInitializationsStateStub::buildSelf();

        $controller = new StartMigrationController(
            $token_provider,
            ProjectReadyToBeMigratedVerifierStub::projectIsReady(),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            ProjectByIDFactoryStub::buildWith($project),
            $enqueue_task,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $ongoing_initializations_state,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildSiteAdministrator())
            ->withParsedBody(
                [
                    'project' => (string) $project->getID(),
                ]
            );

        $response = $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertEquals(302, $response->getStatusCode());
        self::assertInstanceOf(MigrateInstanceTask::class, $enqueue_task->queue_task);
        if ($enqueue_task->queue_task instanceof MigrateInstanceTask) {
            self::assertEquals((int) $project->getID(), $enqueue_task->queue_task->getPayload()['project_id']);
        }
        self::assertTrue($ongoing_initializations_state->isStarted());
    }

    public function testExceptionWhenBodyIsNotAnArray(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $enqueue_task                  = new EnqueueTaskStub();
        $ongoing_initializations_state = OngoingInitializationsStateStub::buildSelf();

        $controller = new StartMigrationController(
            $token_provider,
            ProjectReadyToBeMigratedVerifierStub::projectIsReady(),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            ProjectByIDFactoryStub::buildWith($project),
            $enqueue_task,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $ongoing_initializations_state,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildSiteAdministrator())
            ->withParsedBody(null);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertNull($enqueue_task->queue_task);
        self::assertFalse($ongoing_initializations_state->isStarted());
    }

    public function testExceptionWhenProjectIdIsNotInTheRequest(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $enqueue_task                  = new EnqueueTaskStub();
        $ongoing_initializations_state = OngoingInitializationsStateStub::buildSelf();

        $controller = new StartMigrationController(
            $token_provider,
            ProjectReadyToBeMigratedVerifierStub::projectIsReady(),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            ProjectByIDFactoryStub::buildWith($project),
            $enqueue_task,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $ongoing_initializations_state,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildSiteAdministrator())
            ->withParsedBody([]);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertNull($enqueue_task->queue_task);
        self::assertTrue($ongoing_initializations_state->isFinished());
    }

    public function testExceptionWhenProjectIsNotFound(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $enqueue_task                  = new EnqueueTaskStub();
        $ongoing_initializations_state = OngoingInitializationsStateStub::buildSelf();

        $controller = new StartMigrationController(
            $token_provider,
            ProjectReadyToBeMigratedVerifierStub::projectIsReady(),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            ProjectByIDFactoryStub::buildWithoutProject(),
            $enqueue_task,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $ongoing_initializations_state,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildSiteAdministrator())
            ->withParsedBody(
                [
                    'project' => (string) $project->getID(),
                ]
            );

        $this->expectException(NotFoundException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertNull($enqueue_task->queue_task);
        self::assertFalse($ongoing_initializations_state->isStarted());
    }

    public function testExceptionWhenProjectIsNotAllowedToUsePlugin(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $enqueue_task                  = new EnqueueTaskStub();
        $ongoing_initializations_state = OngoingInitializationsStateStub::buildSelf();

        $controller = new StartMigrationController(
            $token_provider,
            ProjectReadyToBeMigratedVerifierStub::projectIsReady(),
            IsProjectAllowedToUsePluginStub::projectIsNotAllowed(),
            ProjectByIDFactoryStub::buildWith($project),
            $enqueue_task,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $ongoing_initializations_state,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildSiteAdministrator())
            ->withParsedBody(
                [
                    'project' => (string) $project->getID(),
                ]
            );

        $this->expectException(ForbiddenException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertNull($enqueue_task->queue_task);
        self::assertFalse($ongoing_initializations_state->isStarted());
    }

    public function testExceptionWhenProjectIsNotReady(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $feedback_serializer = $this->createStub(FeedbackSerializer::class);
        $feedback_serializer->method('serialize');

        $token          = CSRFSynchronizerTokenStub::buildSelf();
        $token_provider = $this->createMock(CSRFSynchronizerTokenProvider::class);
        $token_provider->method('getCSRF')->willReturn($token);

        $enqueue_task                  = new EnqueueTaskStub();
        $ongoing_initializations_state = OngoingInitializationsStateStub::buildSelf();

        $controller = new StartMigrationController(
            $token_provider,
            ProjectReadyToBeMigratedVerifierStub::projectIsNotReady(),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            ProjectByIDFactoryStub::buildWith($project),
            $enqueue_task,
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $ongoing_initializations_state,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildSiteAdministrator())
            ->withParsedBody(
                [
                    'project' => (string) $project->getID(),
                ]
            );

        $this->expectException(ForbiddenException::class);

        $controller->handle($request);

        self::assertTrue($token->hasBeenChecked());
        self::assertNull($enqueue_task->queue_task);
        self::assertFalse($ongoing_initializations_state->isStarted());
    }
}
