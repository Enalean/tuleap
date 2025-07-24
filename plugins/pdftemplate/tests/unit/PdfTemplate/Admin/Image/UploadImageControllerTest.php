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

namespace Tuleap\PdfTemplate\Admin\Image;

use Psr\Log\NullLogger;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifierFactory;
use Tuleap\PdfTemplate\Stubs\CreateImageStub;
use Tuleap\PdfTemplate\Stubs\StorePdfTemplateImageStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UploadImageControllerTest extends TestCase
{
    private PdfTemplateImageIdentifierFactory $image_identifier_factory;
    private array $backup;

    #[\Override]
    protected function setUp(): void
    {
        $this->image_identifier_factory = new PdfTemplateImageIdentifierFactory(new DatabaseUUIDV7Factory());
        $this->backup                   = $_FILES;
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_FILES = $this->backup;
    }

    public function testExceptionIfNoUser(): void
    {
        $creator = CreateImageStub::build();

        $controller = new UploadImageController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            $creator,
            $this->image_identifier_factory,
            StorePdfTemplateImageStub::shouldNotBeCalled(),
            new NullLogger(),
            new NoopSapiEmitter(),
        );

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);

        $controller->handle($request);
        self::assertFalse($creator->isCreated());
    }

    public function testExceptionIfNoFileEntry(): void
    {
        $_FILES = [];

        $creator = CreateImageStub::build();

        $controller = new UploadImageController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            $creator,
            $this->image_identifier_factory,
            StorePdfTemplateImageStub::shouldNotBeCalled(),
            new NullLogger(),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user);

        $this->expectException(ForbiddenException::class);

        $controller->handle($request);
        self::assertFalse($creator->isCreated());
    }

    public function testErrorIfNoFileUploaded(): void
    {
        $_FILES = [
            'image' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
                'size' => 0,
            ],
        ];

        $creator = CreateImageStub::build();

        $controller = new UploadImageController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            $creator,
            $this->image_identifier_factory,
            StorePdfTemplateImageStub::shouldNotBeCalled(),
            new NullLogger(),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user);

        $this->expectException(ForbiddenException::class);

        $controller->handle($request);
        self::assertFalse($creator->isCreated());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getErrorStatus')]
    public function testErrorIfFileUploadIsInError(int $error): void
    {
        $_FILES = [
            'image' => [
                'name' => 'image.jpeg',
                'type' => 'image/jpeg',
                'tmp_name' => '',
                'size' => 0,
                'error' => $error,
            ],
        ];

        $creator = CreateImageStub::build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new UploadImageController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $creator,
            $this->image_identifier_factory,
            StorePdfTemplateImageStub::shouldNotBeCalled(),
            new NullLogger(),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($creator->isCreated());
    }

    public static function getErrorStatus(): array
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
            [UPLOAD_ERR_PARTIAL],
            [UPLOAD_ERR_NO_TMP_DIR],
            [UPLOAD_ERR_CANT_WRITE],
            [UPLOAD_ERR_EXTENSION],
        ];
    }

    public function testErrorIfFileCannotBeStored(): void
    {
        $_FILES = [
            'image' => [
                'name' => 'image.jpeg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/name',
                'size' => 1000,
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $creator = CreateImageStub::build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new UploadImageController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $creator,
            $this->image_identifier_factory,
            StorePdfTemplateImageStub::withFailingUpload(),
            new NullLogger(),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($creator->isCreated());
    }

    public function testHappyPath(): void
    {
        $_FILES = [
            'image' => [
                'name' => 'image.jpeg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/name',
                'size' => 1000,
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $creator = CreateImageStub::build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new UploadImageController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $creator,
            $this->image_identifier_factory,
            StorePdfTemplateImageStub::withSuccessfulUpload(),
            new NullLogger(),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($creator->isCreated());
    }
}
