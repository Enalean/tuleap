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

namespace Tuleap\TrackerCCE\Administration;

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\TrackerCCE\Stub\Administration\LogModuleActivatedStub;
use Tuleap\TrackerCCE\Stub\Administration\LogModuleDeactivatedStub;
use Tuleap\TrackerCCE\Stub\Administration\UpdateModuleActivationStub;

final class ActivateModuleControllerTest extends TestCase
{
    public function testExceptionWhenNoTracker(): void
    {
        $controller = new ActivateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            UpdateModuleActivationStub::build(),
            LogModuleActivatedStub::build(),
            LogModuleDeactivatedStub::build(),
            new NoopSapiEmitter(),
        );

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);

        $controller->handle($request);
    }

    public function testExceptionWhenNoUser(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $controller = new ActivateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            UpdateModuleActivationStub::build(),
            LogModuleActivatedStub::build(),
            LogModuleDeactivatedStub::build(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker);

        $this->expectException(\LogicException::class);

        $controller->handle($request);
    }

    public function testItActivatesTheModule(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $update_module_activation = UpdateModuleActivationStub::build();

        $activated_logs   = LogModuleActivatedStub::build();
        $deactivated_logs = LogModuleDeactivatedStub::build();

        $controller = new ActivateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $update_module_activation,
            $activated_logs,
            $deactivated_logs,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user)
            ->withParsedBody(['activate-module' => '1']);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($activated_logs->isLogged());
        self::assertFalse($deactivated_logs->isLogged());
        self::assertTrue($update_module_activation->hasBeenActivated());
        self::assertFalse($update_module_activation->hasBeenDeactivated());
    }

    public function testItDeactivatesTheModule(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $update_module_activation = UpdateModuleActivationStub::build();

        $activated_logs   = LogModuleActivatedStub::build();
        $deactivated_logs = LogModuleDeactivatedStub::build();

        $controller = new ActivateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $update_module_activation,
            $activated_logs,
            $deactivated_logs,
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user)
            ->withParsedBody(['activate-module' => '0']);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($activated_logs->isLogged());
        self::assertTrue($deactivated_logs->isLogged());
        self::assertFalse($update_module_activation->hasBeenActivated());
        self::assertTrue($update_module_activation->hasBeenDeactivated());
    }
}
