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

namespace Tuleap\PdfTemplate;

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\GetPdfTemplatesEvent;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\PdfTemplate\Stubs\RetrieveAllTemplatesStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PdfTemplateForUserRetrieverTest extends TestCase
{
    public function testAnonymousCannotSeeTemplates(): void
    {
        $identifier = (new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        $templates = [
            new PdfTemplate($identifier, 'Blue template', '', 'body { color: blue }'),
        ];

        $retriever = new PdfTemplateForUserRetriever(RetrieveAllTemplatesStub::withTemplates($templates));

        $event = new GetPdfTemplatesEvent(UserTestBuilder::anAnonymousUser()->build());

        $retriever->injectTemplates($event);

        self::assertNull($event->getTemplates());
    }

    public function testLoggedInUserCanSeeTemplates(): void
    {
        $identifier = (new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        $templates = [
            new PdfTemplate($identifier, 'Blue template', '', 'body { color: blue }'),
        ];

        $retriever = new PdfTemplateForUserRetriever(RetrieveAllTemplatesStub::withTemplates($templates));

        $event = new GetPdfTemplatesEvent(UserTestBuilder::anActiveUser()->build());

        $retriever->injectTemplates($event);

        self::assertEquals($templates, $event->getTemplates());
    }
}
