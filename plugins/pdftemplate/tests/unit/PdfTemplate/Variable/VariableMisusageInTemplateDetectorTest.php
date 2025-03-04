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

namespace Tuleap\PdfTemplate\Variable;

use Tuleap\Test\Builders\Export\Pdf\Template\PdfTemplateTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VariableMisusageInTemplateDetectorTest extends TestCase
{
    public function testNothingWhenNoMisusage(): void
    {
        $detector = new VariableMisusageInTemplateDetector(new VariableMisusageCollector());

        $template = PdfTemplateTestBuilder::aTemplate()
            ->withTitlePageContent('title ipsum')
            ->withHeaderContent('header ipsum')
            ->withFooterContent('footer ipsum')
            ->build();

        self::assertEmpty($detector->detectVariableMisusages($template));
    }

    public function testMisusageInTitlePage(): void
    {
        $detector = new VariableMisusageInTemplateDetector(new VariableMisusageCollector());

        $template = PdfTemplateTestBuilder::aTemplate()
            ->withTitlePageContent('title ipsum ${UNKNOWN}')
            ->withHeaderContent('header ipsum')
            ->withFooterContent('footer ipsum')
            ->build();

        self::assertCount(1, $detector->detectVariableMisusages($template));
    }

    public function testMisusageInHeader(): void
    {
        $detector = new VariableMisusageInTemplateDetector(new VariableMisusageCollector());

        $template = PdfTemplateTestBuilder::aTemplate()
            ->withTitlePageContent('title ipsum')
            ->withHeaderContent('header ipsum ${UNKNOWN}')
            ->withFooterContent('footer ipsum')
            ->build();

        self::assertCount(1, $detector->detectVariableMisusages($template));
    }

    public function testMisusageInFooter(): void
    {
        $detector = new VariableMisusageInTemplateDetector(new VariableMisusageCollector());

        $template = PdfTemplateTestBuilder::aTemplate()
            ->withTitlePageContent('title ipsum')
            ->withHeaderContent('header ipsum')
            ->withFooterContent('footer ipsum ${UNKNOWN}')
            ->build();

        self::assertCount(1, $detector->detectVariableMisusages($template));
    }

    public function testMisusagesEverywhere(): void
    {
        $detector = new VariableMisusageInTemplateDetector(new VariableMisusageCollector());

        $template = PdfTemplateTestBuilder::aTemplate()
            ->withTitlePageContent('title ipsum ${UNKNOWN}')
            ->withHeaderContent('header ipsum ${UNKNOWN}')
            ->withFooterContent('footer ipsum ${UNKNOWN}')
            ->build();

        self::assertCount(3, $detector->detectVariableMisusages($template));
    }
}
