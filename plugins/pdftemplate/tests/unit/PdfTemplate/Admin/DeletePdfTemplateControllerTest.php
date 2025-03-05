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

use Psr\Log\NullLogger;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\PdfTemplate\Stubs\DeleteTemplateStub;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DeletePdfTemplateControllerTest extends TestCase
{
    public function testExceptionWhenNoUser(): void
    {
        $deletor = DeleteTemplateStub::build();

        $controller = new DeletePdfTemplateController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            new NullLogger(),
            $deletor,
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            new NoopSapiEmitter(),
        );

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);

        $controller->handle($request);
        self::assertFalse($deletor->isCalled());
    }

    public function testNotFoundWhenNoIdentifier(): void
    {
        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $deletor = DeleteTemplateStub::build();

        $controller = new DeletePdfTemplateController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            $deletor,
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);
        self::assertFalse($deletor->isCalled());
    }

    public function testNotFoundWhenIdentifierIsMalformed(): void
    {
        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $deletor = DeleteTemplateStub::build();

        $controller = new DeletePdfTemplateController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            $deletor,
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user)
            ->withParsedBody(['id' => 'invaliduuid']);

        $this->expectException(NotFoundException::class);

        $controller->handle($request);
        self::assertFalse($deletor->isCalled());
    }

    public function testDeletePdfTemplate(): void
    {
        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $deletor = DeleteTemplateStub::build();

        $identifier_factory = new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory());

        $controller = new DeletePdfTemplateController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            $deletor,
            $identifier_factory,
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user)
            ->withParsedBody(['id' => $identifier_factory->buildIdentifier()->toString()]);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($deletor->isCalled());
    }
}
