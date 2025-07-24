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

namespace Tuleap\PdfTemplate\Image;

use Psr\Http\Message\ResponseInterface;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifierFactory;
use Tuleap\PdfTemplate\Stubs\RetrieveImageStub;
use Tuleap\Request\CaptureRequestHandler;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RetrieveImageMiddlewareTest extends TestCase
{
    private PdfTemplateImageIdentifierFactory $image_identifier_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->image_identifier_factory = new PdfTemplateImageIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    public function testNotFoundIfNoIdAttribute(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $middleware = new RetrieveImageMiddleware(
            $this->image_identifier_factory,
            RetrieveImageStub::withoutMatchingImage(),
        );

        $request = new NullServerRequest();

        $this->expectException(NotFoundException::class);
        $middleware->process($request, $handler);
    }

    public function testNotFoundIfNoInvalidIdentifier(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $middleware = new RetrieveImageMiddleware(
            $this->image_identifier_factory,
            RetrieveImageStub::withoutMatchingImage(),
        );

        $request = (new NullServerRequest())->withAttribute('id', 'invalid-uuid');

        $this->expectException(NotFoundException::class);
        $middleware->process($request, $handler);
    }

    public function testNotFoundIfNoMatchingImage(): void
    {
        $identifier = $this->image_identifier_factory->buildIdentifier();

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $middleware = new RetrieveImageMiddleware(
            $this->image_identifier_factory,
            RetrieveImageStub::withoutMatchingImage(),
        );

        $request = (new NullServerRequest())->withAttribute('id', $identifier->toString());

        $this->expectException(NotFoundException::class);
        $middleware->process($request, $handler);
    }

    public function testMatchingImage(): void
    {
        $identifier = $this->image_identifier_factory->buildIdentifier();

        $image = new PdfTemplateImage($identifier, 'logo.gif', 123, UserTestBuilder::buildWithDefaults(), new \DateTimeImmutable());

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $middleware = new RetrieveImageMiddleware(
            $this->image_identifier_factory,
            RetrieveImageStub::withMatchingImage($image),
        );

        $request = (new NullServerRequest())->withAttribute('id', $identifier->toString());

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
        self::assertSame(
            $image,
            $handler->getCapturedRequest()?->getAttribute(PdfTemplateImage::class)
        );
    }
}
