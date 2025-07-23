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

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifierFactory;
use Tuleap\PdfTemplate\Image\PdfTemplateImage;
use Tuleap\PdfTemplate\Image\PdfTemplateImageHrefBuilder;
use Tuleap\PdfTemplate\Stubs\RetrieveAllTemplatesStub;
use Tuleap\Test\Builders\Export\Pdf\Template\PdfTemplateTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UsageDetectorTest extends TestCase
{
    private PdfTemplateImageIdentifierFactory $identifier_factory;
    private PdfTemplateImage $company_logo;
    private PdfTemplateImage $another_logo;
    private PdfTemplateImageHrefBuilder $href_builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->identifier_factory = new PdfTemplateImageIdentifierFactory(new DatabaseUUIDV7Factory());

        $this->company_logo = new PdfTemplateImage(
            $this->identifier_factory->buildIdentifier(),
            'company-logo.gif',
            123,
            UserTestBuilder::buildWithDefaults(),
            new \DateTimeImmutable(),
        );

        $this->another_logo = new PdfTemplateImage(
            $this->identifier_factory->buildIdentifier(),
            'company-logo.gif',
            123,
            UserTestBuilder::buildWithDefaults(),
            new \DateTimeImmutable(),
        );

        $this->href_builder = new PdfTemplateImageHrefBuilder();
    }

    public function testEmptyIfNoTemplates(): void
    {
        $detector = new UsageDetector(
            RetrieveAllTemplatesStub::withoutTemplates(),
            $this->href_builder,
        );

        $this->assertUsages([], $detector->getUsages($this->company_logo));
        $this->assertUsages([], $detector->getUsages($this->another_logo));
    }

    public function testEmptyIfTemplatesDoesNotUseImage(): void
    {
        $detector = new UsageDetector(
            RetrieveAllTemplatesStub::withTemplates([
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Blue')
                    ->build(),
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Red')
                    ->build(),
            ]),
            $this->href_builder,
        );

        $this->assertUsages([], $detector->getUsages($this->company_logo));
        $this->assertUsages([], $detector->getUsages($this->another_logo));
    }

    public function testItReturnsAllTemplatesThatUseImageInStyle(): void
    {
        $company_logo_href = $this->href_builder->getImageHref($this->company_logo);
        $another_logo_href = $this->href_builder->getImageHref($this->another_logo);

        $detector = new UsageDetector(
            RetrieveAllTemplatesStub::withTemplates([
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Blue')
                    ->withStyle("body { background: url($company_logo_href) }")
                    ->build(),
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Red')
                    ->withStyle("body { background: url($company_logo_href) } h1 { background: url($another_logo_href) }")
                    ->build(),
            ]),
            $this->href_builder,
        );

        $this->assertUsages(['Blue', 'Red'], $detector->getUsages($this->company_logo));
        $this->assertUsages(['Red'], $detector->getUsages($this->another_logo));
    }

    public function testItReturnsAllTemplatesThatUseImageInHeader(): void
    {
        $company_logo_href = $this->href_builder->getImageHref($this->company_logo);
        $another_logo_href = $this->href_builder->getImageHref($this->another_logo);

        $detector = new UsageDetector(
            RetrieveAllTemplatesStub::withTemplates([
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Blue')
                    ->withHeaderContent("<img src='$company_logo_href'>")
                    ->build(),
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Red')
                    ->withHeaderContent("<img src='$company_logo_href'><img src='$another_logo_href'>")
                    ->build(),
            ]),
            $this->href_builder,
        );

        $this->assertUsages(['Blue', 'Red'], $detector->getUsages($this->company_logo));
        $this->assertUsages(['Red'], $detector->getUsages($this->another_logo));
    }

    public function testItReturnsAllTemplatesThatUseImageInFooter(): void
    {
        $company_logo_href = $this->href_builder->getImageHref($this->company_logo);
        $another_logo_href = $this->href_builder->getImageHref($this->another_logo);

        $detector = new UsageDetector(
            RetrieveAllTemplatesStub::withTemplates([
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Blue')
                    ->withFooterContent("<img src='$company_logo_href'>")
                    ->build(),
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Red')
                    ->withFooterContent("<img src='$company_logo_href'><img src='$another_logo_href'>")
                    ->build(),
            ]),
            $this->href_builder,
        );

        $this->assertUsages(['Blue', 'Red'], $detector->getUsages($this->company_logo));
        $this->assertUsages(['Red'], $detector->getUsages($this->another_logo));
    }

    public function testItDoesNotDuplicateResultsIfImageIsFoundEverywhereInTemplate(): void
    {
        $company_logo_href = $this->href_builder->getImageHref($this->company_logo);
        $another_logo_href = $this->href_builder->getImageHref($this->another_logo);

        $detector = new UsageDetector(
            RetrieveAllTemplatesStub::withTemplates([
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Blue')
                    ->withStyle("body { background: url($company_logo_href) }")
                    ->withHeaderContent("<img src='$company_logo_href'>")
                    ->withFooterContent("<img src='$company_logo_href'>")
                    ->build(),
                PdfTemplateTestBuilder::aTemplate()
                    ->withLabel('Red')
                    ->build(),
            ]),
            $this->href_builder,
        );

        $this->assertUsages(['Blue'], $detector->getUsages($this->company_logo));
        $this->assertUsages([], $detector->getUsages($this->another_logo));
    }

    /**
     * @param list<string> $expected
     * @param list<PdfTemplate> $templates
     */
    private function assertUsages(array $expected, array $templates): void
    {
        self::assertSame(count($expected), count($templates));
        self::assertSame(
            $expected,
            array_map(
                static fn (PdfTemplate $template) => $template->label,
                $templates,
            ),
        );
    }
}
