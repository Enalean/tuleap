<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Search;

use Docman_Metadata;
use Docman_ReportColumnTitle;
use Tuleap\Docman\REST\v1\Search\SearchSortRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ColumnReportAugmenterTest extends TestCase
{
    /**
     * @var \Docman_ReportColumnFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $column_factory;
    private ColumnReportAugmenter $builder;
    private \Codendi_Request $request;

    #[\Override]
    protected function setUp(): void
    {
        $this->column_factory = $this->createMock(\Docman_ReportColumnFactory::class);
        $this->builder        = new ColumnReportAugmenter($this->column_factory, new SearchSortPropertyMapper());

        $this->request = new \Codendi_Request(
            [
                'aaaa'              => 'bbb',
                'ccc' => '1280',
            ],
            null
        );
    }

    public function testItBuildsColumnFromRequest(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('My column');
        $column_title = new Docman_ReportColumnTitle($metadata);
        $this->column_factory->method('getColumnFromLabel')->with('title')->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromRequest($this->request, ['title'], $report);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertNull($report->columns[0]->getSort());
    }

    public function testItKeepsColumnSort(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('My column');
        $column_title = new Docman_ReportColumnTitle($metadata);
        $column_title->setSort('sort_' . $metadata->getLabel());
        $this->column_factory->method('getColumnFromLabel')->with('title')->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromRequest($this->request, ['title'], $report);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame($column_title->getSort(), $report->columns[0]->getSort());
    }

    public function testItHasASpecialSortWhenNoSortIsDefinedAndReportHasLastUpdateDate(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('My column');
        $column_title = new Docman_ReportColumnTitle($metadata);
        $this->column_factory->method('getColumnFromLabel')->with('update_date')->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromRequest($this->request, ['update_date'], $report);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame(0, $report->columns[0]->getSort());
    }

    public function testItKeepsCustomSortWhenReportHasLastUpdateDate(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('My column');
        $column_title = new Docman_ReportColumnTitle($metadata);
        $column_title->setSort('sort_' . $metadata->getLabel());
        $this->column_factory->method('getColumnFromLabel')->with('update_date')->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromRequest($this->request, ['update_date'], $report);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame($column_title->getSort(), $report->columns[0]->getSort());
    }

    public function testItBuildsColumnFromArray(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('My column');
        $column_title = new Docman_ReportColumnTitle($metadata);
        $this->column_factory->method('getColumnFromLabel')->with('title')->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromArray(['title'], $report, []);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertNull($report->columns[0]->getSort());
    }

    public function testItKeepsColumnSortFromArray(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('My column');
        $column_title = new Docman_ReportColumnTitle($metadata);
        $column_title->setSort('sort_' . $metadata->getLabel());
        $this->column_factory->method('getColumnFromLabel')->with('title')->willReturn($column_title);

        $sort        = new SearchSortRepresentation();
        $sort->name  = 'GR';
        $sort->order = 'asc';
        $sort_list   = [$sort];

        $report = new \Docman_Report();
        $this->builder->addColumnsFromArray(['title'], $report, $sort_list);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame($column_title->getSort(), $report->columns[0]->getSort());
    }

    public function testItHasASpecialSortWhenNoSortIsDefinedAndReportHasLastUpdateDateFromArray(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('My column');
        $column_title = new Docman_ReportColumnTitle($metadata);
        $this->column_factory->method('getColumnFromLabel')->with('update_date')->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromArray(['update_date'], $report, []);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame(0, $report->columns[0]->getSort());
    }
}
