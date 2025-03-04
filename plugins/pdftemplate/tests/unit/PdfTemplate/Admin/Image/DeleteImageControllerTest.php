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
use Tuleap\PdfTemplate\Image\PdfTemplateImage;
use Tuleap\PdfTemplate\Stubs\DeleteImageFromStorageStub;
use Tuleap\PdfTemplate\Stubs\DeleteImageStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DeleteImageControllerTest extends TestCase
{
    public function testExceptionIfNoUser(): void
    {
        $deletor = DeleteImageStub::build();
        $storage = DeleteImageFromStorageStub::build();

        $controller = new DeleteImageController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            $storage,
            $deletor,
            new NullLogger(),
            new NoopSapiEmitter(),
        );

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);

        $controller->handle($request);
        self::assertFalse($deletor->isDeleted());
        self::assertFalse($storage->isDeleted());
    }

    public function testExceptionIfNoImage(): void
    {
        $alice = UserTestBuilder::buildWithDefaults();

        $deletor = DeleteImageStub::build();
        $storage = DeleteImageFromStorageStub::build();

        $controller = new DeleteImageController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            $storage,
            $deletor,
            new NullLogger(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $alice);

        $this->expectException(\LogicException::class);

        $controller->handle($request);
        self::assertFalse($deletor->isDeleted());
        self::assertFalse($storage->isDeleted());
    }

    public function testHappyPath(): void
    {
        $alice = UserTestBuilder::buildWithDefaults();
        $image = new PdfTemplateImage(
            (new PdfTemplateImageIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier(),
            'logo.png',
            123,
            $alice,
            new \DateTimeImmutable(),
        );

        $deletor = DeleteImageStub::build();
        $storage = DeleteImageFromStorageStub::build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $controller = new DeleteImageController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            $storage,
            $deletor,
            new NullLogger(),
            new NoopSapiEmitter(),
        );

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $alice)
            ->withAttribute(PdfTemplateImage::class, $image);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($deletor->isDeleted());
        self::assertTrue($storage->isDeleted());
    }
}
