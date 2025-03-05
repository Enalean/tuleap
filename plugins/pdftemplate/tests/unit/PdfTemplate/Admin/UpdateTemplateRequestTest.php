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

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Test\Builders\Export\Pdf\Template\PdfTemplateTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UpdateTemplateRequestTest extends TestCase
{
    public function testChangedFields(): void
    {
        $identifier_factory = new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory());
        $identifier         = $identifier_factory->buildIdentifier();

        self::assertEquals(
            [],
            (new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->build(),
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->build(),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['label'],
            (new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withLabel('label')->build(),
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withLabel('update label')->build(),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['description'],
            (new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withDescription('description')->build(),
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withDescription('update description')->build(),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['style'],
            (new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withStyle('style')->build(),
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withStyle('update style')->build(),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['label', 'style'],
            (new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withLabel('label')->withStyle('style')->build(),
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withLabel('update label')->withStyle('update style')->build(),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['header_content'],
            (new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withHeaderContent('<span>Header</span>')->build(),
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withHeaderContent('<span>Updated header</span>')->build(),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['footer_content'],
            (new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withFooterContent('<span>Footer</span>')->build(),
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withFooterContent('<span>Updated Footer</span>')->build(),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['title_page_content'],
            (new UpdateTemplateRequest(
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withTitlePageContent('<span>Title</span>')->build(),
                PdfTemplateTestBuilder::aTemplate()->withIdentifier($identifier)->withTitlePageContent('<h1>Title</h1>')->build(),
            ))->getChangedFields(),
        );
    }
}
