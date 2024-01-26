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

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\TrackerFunctions\Stubs\Administration\LogFunctionActivatedStub;
use Tuleap\TrackerFunctions\Stubs\Administration\LogFunctionDeactivatedStub;
use Tuleap\TrackerFunctions\Stubs\Administration\UpdateFunctionActivationStub;

final class ActivateFunctionControllerTest extends TestCase
{
    public function testExceptionWhenNoTracker(): void
    {
        $controller = new ActivateFunctionController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            UpdateFunctionActivationStub::build(),
            LogFunctionActivatedStub::build(),
            LogFunctionDeactivatedStub::build(),
            new NoopSapiEmitter(),
        );

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);

        $controller->handle($request);
    }

    public function testExceptionWhenNoUser(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $controller = new ActivateFunctionController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            UpdateFunctionActivationStub::build(),
            LogFunctionActivatedStub::build(),
            LogFunctionDeactivatedStub::build(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker);

        $this->expectException(\LogicException::class);

        $controller->handle($request);
    }

    public function testItActivatesTheFunction(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $update_function_activation = UpdateFunctionActivationStub::build();

        $activated_logs   = LogFunctionActivatedStub::build();
        $deactivated_logs = LogFunctionDeactivatedStub::build();

        $controller = new ActivateFunctionController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $update_function_activation,
            $activated_logs,
            $deactivated_logs,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user)
            ->withParsedBody(['activate-function' => '1']);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($activated_logs->isLogged());
        self::assertFalse($deactivated_logs->isLogged());
        self::assertTrue($update_function_activation->hasBeenActivated());
        self::assertFalse($update_function_activation->hasBeenDeactivated());
    }

    public function testItDeactivatesTheFunction(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $update_function_activation = UpdateFunctionActivationStub::build();

        $activated_logs   = LogFunctionActivatedStub::build();
        $deactivated_logs = LogFunctionDeactivatedStub::build();

        $controller = new ActivateFunctionController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $update_function_activation,
            $activated_logs,
            $deactivated_logs,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user)
            ->withParsedBody(['activate-function' => '0']);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($activated_logs->isLogged());
        self::assertTrue($deactivated_logs->isLogged());
        self::assertFalse($update_function_activation->hasBeenActivated());
        self::assertTrue($update_function_activation->hasBeenDeactivated());
    }
}
