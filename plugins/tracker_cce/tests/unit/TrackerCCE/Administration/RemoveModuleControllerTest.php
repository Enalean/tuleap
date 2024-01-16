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
use Tuleap\TrackerCCE\Stub\Administration\LogModuleRemovedStub;
use Tuleap\TrackerCCE\WASM\FindWASMModulePath;

final class RemoveModuleControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testExceptionWhenNoTracker(): void
    {
        $controller = new RemoveModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            LogModuleRemovedStub::build(),
            new FindWASMModulePath(),
            new NoopSapiEmitter(),
        );

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);

        $controller->handle($request);
    }

    public function testExceptionWhenNoUser(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $controller = new RemoveModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            LogModuleRemovedStub::build(),
            new FindWASMModulePath(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker);

        $this->expectException(\LogicException::class);

        $controller->handle($request);
    }

    public function testErrorWhenModuleDoesNotExist(): void
    {
        \ForgeConfig::set('sys_data_dir', vfsStream::setup('/')->url());

        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new RemoveModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            LogModuleRemovedStub::build(),
            new FindWASMModulePath(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
    }

    public function testItRemovesTheModule(): void
    {
        \ForgeConfig::set('sys_data_dir', vfsStream::setup('/')->url());
        mkdir(\ForgeConfig::get('sys_data_dir') . '/tracker_cce/101', 0700, true);
        touch(\ForgeConfig::get('sys_data_dir') . '/tracker_cce/101/post-action.wasm');
        self::assertTrue(is_file(\ForgeConfig::get('sys_data_dir') . '/tracker_cce/101/post-action.wasm'));

        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $project_history = LogModuleRemovedStub::build();

        $controller = new RemoveModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $project_history,
            new FindWASMModulePath(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user);

        $response = $controller->handle($request);

        self::assertFalse(is_file(\ForgeConfig::get('sys_data_dir') . '/tracker_cce/101/post-action.wasm'));
        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($project_history->isLogged());
    }
}
