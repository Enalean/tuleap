<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use Tuleap\Docman\Search\FilenameColumnReport;
use Tuleap\Docman\Search\IdColumnReport;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_ReportColumnFactoryTest extends TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    private Docman_ReportColumnFactory $report_column_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->report_column_factory = new Docman_ReportColumnFactory(101);
    }

    public function testItReturnsTheLocationColumnReport(): void
    {
        $result = $this->report_column_factory->getColumnFromLabel('location');

        self::assertInstanceOf(Docman_ReportColumnLocation::class, $result);
    }

    public function testItReturnsTheTitleColumnReport(): void
    {
        $result = $this->report_column_factory->getColumnFromLabel('title');

        self::assertInstanceOf(Docman_ReportColumnTitle::class, $result);
    }

    public function testItReturnsTheIdColumnReport(): void
    {
        $result = $this->report_column_factory->getColumnFromLabel('id');

        self::assertInstanceOf(IdColumnReport::class, $result);
    }

    public function testItReturnsTheFilenameColumnReport(): void
    {
        $result = $this->report_column_factory->getColumnFromLabel('filename');

        self::assertInstanceOf(FilenameColumnReport::class, $result);
    }

    public function testItReturnsTheColumnReportOfCustomMetadata(): void
    {
        $result = $this->report_column_factory->getColumnFromLabel('description');

        self::assertInstanceOf(Docman_ReportColumn::class, $result);
    }
}
