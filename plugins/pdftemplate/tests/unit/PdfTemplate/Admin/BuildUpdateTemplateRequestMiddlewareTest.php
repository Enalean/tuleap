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

namespace Tuleap\PdfTemplate\Admin;

use Psr\Http\Message\ResponseInterface;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\PdfTemplate\Stubs\RetrieveTemplateStub;
use Tuleap\Request\CaptureRequestHandler;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\Export\Pdf\Template\PdfTemplateTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;

final class BuildUpdateTemplateRequestMiddlewareTest extends TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    public function testProcessAttachesTemplatesToRequest(): void
    {
        $alice = UserTestBuilder::aUser()->withId(101)->build();
        $bob   = UserTestBuilder::aUser()->withId(102)->build();

        $template = PdfTemplateTestBuilder::aTemplate()
            ->withDescription('Description')
            ->withLastUpdatedBy($alice)
            ->withLastUpdatedDate((new \DateTimeImmutable())->setTimestamp(123))
            ->build();

        $middleware = new BuildUpdateTemplateRequestMiddleware(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withMatchingTemplate($template),
            ProvideUserAvatarUrlStub::build(),
        );

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $bob)
            ->withAttribute('id', $template->identifier->toString())
            ->withParsedBody(['label' => $template->label, 'description' => 'updated description']);

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
        $wrapper = $handler->getCapturedRequest()?->getAttribute(UpdateTemplateRequest::class);
        self::assertInstanceOf(UpdateTemplateRequest::class, $wrapper);
        self::assertSame($template, $wrapper->original);
        self::assertEquals('updated description', $wrapper->submitted->description);
        self::assertSame($bob, $wrapper->submitted->last_updated_by);
        self::assertNotSame(123, $wrapper->submitted->last_updated_date->getTimestamp());
    }

    public function testNotFoundWhenNoIdentifier(): void
    {
        $template = PdfTemplateTestBuilder::aTemplate()->build();

        $middleware = new BuildUpdateTemplateRequestMiddleware(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withMatchingTemplate($template),
            ProvideUserAvatarUrlStub::build(),
        );

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildWithDefaults())
            ->withParsedBody(['label' => $template->label, 'description' => 'updated description']);

        $this->expectException(NotFoundException::class);

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
    }

    public function testNotFoundWhenIdentifierIsMalformed(): void
    {
        $template = PdfTemplateTestBuilder::aTemplate()->build();

        $middleware = new BuildUpdateTemplateRequestMiddleware(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withMatchingTemplate($template),
            ProvideUserAvatarUrlStub::build(),
        );

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildWithDefaults())
            ->withAttribute('id', 'invaliduuid')
            ->withParsedBody(['label' => $template->label, 'description' => 'updated description']);

        $this->expectException(NotFoundException::class);

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
    }

    public function testNotFoundWhenTemplateCannotBeFound(): void
    {
        $template = PdfTemplateTestBuilder::aTemplate()->build();

        $middleware = new BuildUpdateTemplateRequestMiddleware(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withoutMatchingTemplate(),
            ProvideUserAvatarUrlStub::build(),
        );

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildWithDefaults())
            ->withAttribute('id', $template->identifier->toString())
            ->withParsedBody(['label' => $template->label, 'description' => 'updated description']);

        $this->expectException(NotFoundException::class);

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
    }

    public function testExceptionWhenNoUser(): void
    {
        $template = PdfTemplateTestBuilder::aTemplate()->build();

        $middleware = new BuildUpdateTemplateRequestMiddleware(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withMatchingTemplate($template),
            ProvideUserAvatarUrlStub::build(),
        );

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())
            ->withAttribute('id', $template->identifier->toString())
            ->withParsedBody(['label' => $template->label, 'description' => 'updated description']);

        $this->expectException(\LogicException::class);

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
    }

    public function testErrorWhenNoLabel(): void
    {
        $template = PdfTemplateTestBuilder::aTemplate()->build();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $middleware = new BuildUpdateTemplateRequestMiddleware(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withMatchingTemplate($template),
            ProvideUserAvatarUrlStub::build(),
        );

        $response = $this->createMock(ResponseInterface::class);

        $handler = CaptureRequestHandler::withResponse($response);

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, UserTestBuilder::buildWithDefaults())
            ->withAttribute('id', $template->identifier->toString())
            ->withParsedBody(['label' => '', 'description' => 'updated description']);

        $new_response = $middleware->process($request, $handler);

        $this->assertNotSame($response, $new_response);

        self::assertSame(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $new_response->getStatusCode());
    }
}
