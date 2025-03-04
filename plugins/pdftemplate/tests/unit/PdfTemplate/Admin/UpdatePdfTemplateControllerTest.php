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
use Tuleap\PdfTemplate\Stubs\UpdateTemplateStub;
use Tuleap\PdfTemplate\Variable\VariableMisusageCollector;
use Tuleap\PdfTemplate\Variable\VariableMisusageInTemplateDetector;
use Tuleap\Test\Builders\Export\Pdf\Template\PdfTemplateTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\FeedbackSerializerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UpdatePdfTemplateControllerTest extends TestCase
{
    public function testNoChanges(): void
    {
        $identifier_factory = new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory());
        $identifier         = $identifier_factory->buildIdentifier();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $updator = UpdateTemplateStub::build();

        $controller = new UpdatePdfTemplateController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            $updator,
            new VariableMisusageInTemplateDetector(new VariableMisusageCollector()),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user)
            ->withAttribute(UpdateTemplateRequest::class, new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withLabel('label')->build(),
                PdfTemplateTestBuilder::aTemplate()->withLabel('label')->build(),
            ));

        $response = $controller->handle($request);

        self::assertSame(\Feedback::INFO, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($updator->isCalled());
    }

    public function testAtLeastOneChange(): void
    {
        $identifier_factory = new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory());
        $identifier         = $identifier_factory->buildIdentifier();

        $feedback_serializer = FeedbackSerializerStub::buildSelf();

        $updator = UpdateTemplateStub::build();

        $controller = new UpdatePdfTemplateController(
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), $feedback_serializer),
            new NullLogger(),
            $updator,
            new VariableMisusageInTemplateDetector(new VariableMisusageCollector()),
            new NoopSapiEmitter(),
        );

        $user = UserTestBuilder::buildWithDefaults();

        $request = (new NullServerRequest())
            ->withAttribute(\PFUser::class, $user)
            ->withAttribute(UpdateTemplateRequest::class, new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withLabel('label')->build(),
                PdfTemplateTestBuilder::aTemplate()->withLabel('updated label')->build(),
            ));

        $response = $controller->handle($request);

        self::assertSame(\Feedback::SUCCESS, $feedback_serializer->getCapturedFeedbacks()[0]->getLevel());
        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($updator->isCalled());
    }
}
