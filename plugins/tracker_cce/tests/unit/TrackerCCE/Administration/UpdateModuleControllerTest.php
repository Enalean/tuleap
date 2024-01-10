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
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\TrackerCCE\Stubs\Administration\UploadedFileStub;
use Tuleap\TrackerCCE\WASM\FindWASMModulePath;

final class UpdateModuleControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testExceptionWhenNoTracker(): void
    {
        $controller = new UpdateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            new NullLogger(),
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

        $controller = new UpdateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            new NullLogger(),
            new FindWASMModulePath(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker);

        $this->expectException(\LogicException::class);

        $controller->handle($request);
    }

    public function testErrorWhenNoModuleParameter(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new UpdateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
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

    /**
     * @dataProvider getUploadErrors
     */
    public function testErrorWhenErrorDuringUpload(int $error): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new UpdateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            new FindWASMModulePath(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user)
            ->withUploadedFiles(['wasm-module' => UploadedFileStub::buildWithError($error)]);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
    }

    private function getUploadErrors(): array
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
            [UPLOAD_ERR_PARTIAL],
            [UPLOAD_ERR_NO_FILE],
            [UPLOAD_ERR_NO_TMP_DIR],
            [UPLOAD_ERR_CANT_WRITE],
            [UPLOAD_ERR_EXTENSION],
        ];
    }

    public function testErrorWhenTheMoveToDestinationFails(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new UpdateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            new FindWASMModulePath(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user)
            ->withUploadedFiles(['wasm-module' => UploadedFileStub::buildWithExceptionOnMove()]);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
    }

    public function testMoveModuleToDestination(): void
    {
        \ForgeConfig::set('sys_data_dir', vfsStream::setup('/')->url());

        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new UpdateModuleController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            new FindWASMModulePath(),
            new NoopSapiEmitter(),
        );

        $uploaded_file = UploadedFileStub::buildGreatSuccess();

        $request = (new NullServerRequest())
            ->withAttribute(\Tracker::class, $tracker)
            ->withAttribute(\PFUser::class, $user)
            ->withUploadedFiles(['wasm-module' => $uploaded_file]);

        $response = $controller->handle($request);

        self::assertTrue(is_dir(\ForgeConfig::get('sys_data_dir') . '/tracker_cce/101'));
        self::assertStringEndsWith('tracker_cce/101/post-action.wasm', (string) $uploaded_file->getCapturedMovedToPath());
        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
    }
}
