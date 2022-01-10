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

namespace Tuleap\Docman\REST\v1\Folders;

use Docman_FilterFactory;
use Docman_Metadata;
use Docman_ReportColumnTitle;
use Docman_SettingsBo;
use Tuleap\Docman\Search\AlwaysThereColumnRetriever;
use Tuleap\Docman\Search\ColumnReportAugmenter;
use Tuleap\Test\PHPUnit\TestCase;

final class SearchReportBuilderTest extends TestCase
{
    private SearchReportBuilder $search_report_builder;

    protected function setUp(): void
    {
        $filter_factory  = new Docman_FilterFactory(101);
        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn(false);
        $always_there_column_retriever = new AlwaysThereColumnRetriever($docman_settings);

        $column_factory = $this->createMock(\Docman_ReportColumnFactory::class);
        $metadata       = new Docman_Metadata();
        $metadata->setLabel("My column");
        $column_title = new Docman_ReportColumnTitle($metadata);
        $column_factory->method("getColumnFromLabel")->willReturn($column_title);

        $column_report_builder = new ColumnReportAugmenter($column_factory);

        $this->search_report_builder = new SearchReportBuilder(
            $filter_factory,
            $always_there_column_retriever,
            $column_report_builder
        );
    }

    public function testItBuildsAReportWithAGlobalSearchFilter(): void
    {
        $folder            = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search_parameters = "*.docx";

        $report = $this->search_report_builder->buildReport($folder, $search_parameters);
        self::assertSame($search_parameters, $report->getFiltersArray()[0]->value);
        self::assertSame("My column", $report->columns[0]->md->getLabel());
    }
}
