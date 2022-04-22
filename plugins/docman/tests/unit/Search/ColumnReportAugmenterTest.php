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
use Docman_ReportColumn;
use Docman_ReportColumnTitle;
use Tuleap\Docman\REST\v1\Search\SearchPropertyRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

final class ColumnReportAugmenterTest extends TestCase
{
    /**
     * @var \Docman_ReportColumnFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $column_factory;
    private ColumnReportAugmenter $builder;
    private \Codendi_Request $request;

    protected function setUp(): void
    {
        $this->column_factory = $this->createMock(\Docman_ReportColumnFactory::class);
        $this->builder        = new ColumnReportAugmenter($this->column_factory, new SearchSortPropertyMapper());

        $this->request = new \Codendi_Request(
            [
                "aaaa"              => "bbb",
                "ccc" => "1280",
            ],
            null
        );
    }

    public function testItBuildsColumnFromRequest(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel("My column");
        $column_title = new Docman_ReportColumnTitle($metadata);
        $this->column_factory->method("getColumnFromLabel")->with("title")->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromRequest($this->request, ["title"], $report);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertNull($report->columns[0]->getSort());
    }

    public function testItKeepsColumnSort(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel("My column");
        $column_title = new Docman_ReportColumnTitle($metadata);
        $column_title->setSort('sort_' . $metadata->getLabel());
        $this->column_factory->method("getColumnFromLabel")->with("title")->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromRequest($this->request, ["title"], $report);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame($column_title->getSort(), $report->columns[0]->getSort());
    }

    public function testItHasASpecialSortWhenNoSortIsDefinedAndReportHasLastUpdateDate(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel("My column");
        $column_title = new Docman_ReportColumnTitle($metadata);
        $this->column_factory->method("getColumnFromLabel")->with("update_date")->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromRequest($this->request, ["update_date"], $report);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame(0, $report->columns[0]->getSort());
    }

    public function testItKeepsCustomSortWhenReportHasLastUpdateDate(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel("My column");
        $column_title = new Docman_ReportColumnTitle($metadata);
        $column_title->setSort('sort_' . $metadata->getLabel());
        $this->column_factory->method("getColumnFromLabel")->with("update_date")->willReturn($column_title);

        $report = new \Docman_Report();
        $this->builder->addColumnsFromRequest($this->request, ["update_date"], $report);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame($column_title->getSort(), $report->columns[0]->getSort());
    }

    public function testItKeepsColumnSortFromArray(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel("My column");
        $column_title = new Docman_ReportColumnTitle($metadata);
        $column_title->setSort('sort_' . $metadata->getLabel());
        $this->column_factory->method("getColumnFromLabel")->with("title")->willReturn($column_title);

        $property          = new SearchPropertyRepresentation();
        $property->name    = "GR";
        $property->value   = "azer*";
        $search_properties = [$property];

        $report = new \Docman_Report();
        $this->builder->addColumnsFromArray(["title"], $report, $search_properties);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame($column_title->getSort(), $report->columns[0]->getSort());
    }

    public function testItHasASpecialSortWhenNoSortIsDefinedAndReportHasLastUpdateDateFromArray(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel("My column");
        $column_title = new Docman_ReportColumnTitle($metadata);
        $this->column_factory->method("getColumnFromLabel")->with("update_date")->willReturn($column_title);

        $property          = new SearchPropertyRepresentation();
        $property->name    = "GR";
        $property->value   = "azer*";
        $search_properties = [$property];

        $report = new \Docman_Report();
        $this->builder->addColumnsFromArray(["update_date"], $report, $search_properties);

        self::assertSame($metadata->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame(0, $report->columns[0]->getSort());
    }

    public function testItBuildsColumnFromArray(): void
    {
        $metadata_title = new Docman_Metadata();
        $metadata_title->setLabel("title");
        $column_title = new Docman_ReportColumnTitle($metadata_title);

        $metadata_update_date = new Docman_Metadata();
        $metadata_update_date->setLabel("update_date");
        $column_update_date = new Docman_ReportColumn($metadata_update_date);

        $metadata_gr = new Docman_Metadata();
        $metadata_gr->setLabel("gr");
        $column_gr = new Docman_ReportColumn($metadata_gr);

        $metadata_n = new Docman_Metadata();
        $metadata_n->setLabel("n");
        $column_n = new Docman_ReportColumn($metadata_n);

        $this->column_factory->expects($this->atLeastOnce())->method("getColumnFromLabel")
            ->withConsecutive(["title"], ["update_date"], ["gr"], ["n"])
            ->willReturnOnConsecutiveCalls($column_title, $column_update_date, $column_gr, $column_n);


        $property_title        = new SearchPropertyRepresentation();
        $property_title->name  = 'title';
        $property_title->value = '*tle';
        $property_title->sort  = 'DESC';

        $property_gr        = new SearchPropertyRepresentation();
        $property_gr->name  = "gr";
        $property_gr->value = "*t*y*";
        $property_gr->sort  = "ASC";

        $property_n        = new SearchPropertyRepresentation();
        $property_n->name  = "n";
        $property_n->value = "hyu*";
        $property_n->sort  = null;

        $search_properties = [
            $property_title, $property_gr, $property_n,
        ];

        $report = new \Docman_Report();
        $this->builder->addColumnsFromArray(["title", "update_date", "gr", "n"], $report, $search_properties);

        self::assertSame($metadata_title->getLabel(), $report->columns[0]->md->getLabel());
        self::assertSame(0, $report->columns[0]->getSort());

        self::assertSame($metadata_update_date->getLabel(), $report->columns[1]->md->getLabel());
        self::assertNull($report->columns[1]->getSort());

        self::assertSame($metadata_gr->getLabel(), $report->columns[2]->md->getLabel());
        self::assertSame(1, $report->columns[2]->getSort());

        self::assertSame($metadata_n->getLabel(), $report->columns[3]->md->getLabel());
        self::assertNull($report->columns[3]->getSort());
    }
}
