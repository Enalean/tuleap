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
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\PdfTemplate\Stubs\CreateTemplateStub;
use Tuleap\PdfTemplate\Variable\VariableMisusageCollector;
use Tuleap\PdfTemplate\Variable\VariableMisusageInTemplateDetector;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CreatePdfTemplateControllerTest extends TestCase
{
    public function testExceptionWhenNoUser(): void
    {
        $creator = CreateTemplateStub::build();

        $controller = new CreatePdfTemplateController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), FeedbackSerializerStub::buildSelf()),
            new NullLogger(),
            $creator,
            new VariableMisusageInTemplateDetector(new VariableMisusageCollector()),
            new NoopSapiEmitter(),
        );

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);

        $controller->handle($request);
        self::assertFalse($creator->isCalled());
    }

    public function testErrorWhenNoLabel(): void
    {
        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $creator = CreateTemplateStub::build();

        $controller = new CreatePdfTemplateController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            $creator,
            new VariableMisusageInTemplateDetector(new VariableMisusageCollector()),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::ERROR, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($creator->isCalled());
    }

    public function testCreatePdfTemplate(): void
    {
        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $creator = CreateTemplateStub::build();

        $controller = new CreatePdfTemplateController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            $creator,
            new VariableMisusageInTemplateDetector(new VariableMisusageCollector()),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user)
            ->withParsedBody(['label' => 'toto']);

        $response = $controller->handle($request);

        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($creator->isCalled());
    }
}
