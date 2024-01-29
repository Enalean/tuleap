<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Administration;

use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\TrackerFunctions\Stubs\Administration\LogFunctionRemovedStub;
use Tuleap\TrackerFunctions\Stubs\Administration\UpdateFunctionActivationStub;
use Tuleap\TrackerFunctions\Stubs\Logs\DeleteLogsPerTrackerStub;
use Tuleap\TrackerFunctions\WASM\FindWASMFunctionPath;

final class RemoveFunctionControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testExceptionWhenNoTracker(): void
    {
        $controller = new RemoveFunctionController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            LogFunctionRemovedStub::build(),
            new FindWASMFunctionPath(),
            UpdateFunctionActivationStub::build(),
            DeleteLogsPerTrackerStub::build(),
            new NoopSapiEmitter(),
        );

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);

        $controller->handle($request);
    }

    public function testExceptionWhenNoUser(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $controller = new RemoveFunctionController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            LogFunctionRemovedStub::build(),
            new FindWASMFunctionPath(),
            UpdateFunctionActivationStub::build(),
            DeleteLogsPerTrackerStub::build(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker);

        $this->expectException(\LogicException::class);

        $controller->handle($request);
    }

    public function testErrorWhenFunctionDoesNotExist(): void
    {
        \ForgeConfig::set('sys_data_dir', vfsStream::setup('/')->url());

        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new RemoveFunctionController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            LogFunctionRemovedStub::build(),
            new FindWASMFunctionPath(),
            UpdateFunctionActivationStub::build(),
            DeleteLogsPerTrackerStub::build(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
    }

    public function testItRemovesTheFunction(): void
    {
        \ForgeConfig::set('sys_data_dir', vfsStream::setup('/')->url());
        mkdir(\ForgeConfig::get('sys_data_dir') . '/tracker_functions/101', 0700, true);
        touch(\ForgeConfig::get('sys_data_dir') . '/tracker_functions/101/post-action.wasm');
        self::assertTrue(is_file(\ForgeConfig::get('sys_data_dir') . '/tracker_functions/101/post-action.wasm'));

        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $project_history = LogFunctionRemovedStub::build();

        $update_function_activation = UpdateFunctionActivationStub::build();
        $delete_logs                = DeleteLogsPerTrackerStub::build();

        $controller = new RemoveFunctionController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $project_history,
            new FindWASMFunctionPath(),
            $update_function_activation,
            $delete_logs,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user);

        $response = $controller->handle($request);

        self::assertFalse(is_file(\ForgeConfig::get('sys_data_dir') . '/tracker_functions/101/post-action.wasm'));
        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($project_history->isLogged());
        self::assertFalse($update_function_activation->hasBeenActivated());
        self::assertTrue($update_function_activation->hasBeenDeactivated());
        self::assertTrue($delete_logs->hasBeenCalled());
    }
}
