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
use Docman_MetadataFactory;
use Docman_ReportColumnTitle;
use Docman_SettingsBo;
use Luracast\Restler\RestException;
use Tuleap\Docman\Metadata\CustomMetadataException;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Search\SearchColumn;
use Tuleap\Docman\REST\v1\Search\SearchColumnCollection;
use Tuleap\Docman\REST\v1\Search\SearchPropertyRepresentation;
use Tuleap\Docman\REST\v1\Search\PostSearchRepresentation;
use Tuleap\Docman\REST\v1\Search\SearchDateRepresentation;
use Tuleap\Docman\Search\AlwaysThereColumnRetriever;
use Tuleap\Docman\Search\ColumnReportAugmenter;
use Tuleap\Docman\Search\SearchSortPropertyMapper;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchReportBuilderTest extends TestCase
{
    private const string CUSTOM_TEXT_PROPERTY   = 'field_1';
    private const string CUSTOM_STRING_PROPERTY = 'field_2';
    private const string CUSTOM_DATE_PROPERTY   = 'field_3';
    private const string CUSTOM_LIST_PROPERTY   = 'field_4';

    private SearchReportBuilder $search_report_builder;
    private Docman_MetadataFactory|\PHPUnit\Framework\MockObject\MockObject $metadata_factory;
    private SearchColumnCollection $custom_properties;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadata_factory = $this->getMockBuilder(Docman_MetadataFactory::class)
            ->setConstructorArgs([101])
            ->onlyMethods(['getRealMetadataList', 'getMetadataFromLabel'])
            ->getMock();
        $this->metadata_factory->method('getRealMetadataList')->willReturn([]);
        $filter_factory  = new Docman_FilterFactory(101);
        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn('1');
        $always_there_column_retriever = new AlwaysThereColumnRetriever($docman_settings);

        $column_factory = $this->createMock(\Docman_ReportColumnFactory::class);
        $metadata       = new Docman_Metadata();
        $metadata->setLabel('My column');
        $column_title = new Docman_ReportColumnTitle($metadata);
        $column_factory->method('getColumnFromLabel')->willReturn($column_title);

        $column_report_builder = new ColumnReportAugmenter($column_factory, new SearchSortPropertyMapper());

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager
            ->method('findUser')
            ->with('John Doe (jdoe)')
            ->willReturn(UserTestBuilder::aUser()->withUserName('jdoe')->build());

        $this->search_report_builder = new SearchReportBuilder(
            $this->metadata_factory,
            $filter_factory,
            new ItemStatusMapper($docman_settings),
            $always_there_column_retriever,
            $column_report_builder,
            $user_manager,
            EventDispatcherStub::withIdentityCallback(),
        );

        $custom_text_property = new \Docman_Metadata();
        $custom_text_property->setLabel(self::CUSTOM_TEXT_PROPERTY);
        $custom_text_property->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $custom_string_property = new \Docman_Metadata();
        $custom_string_property->setLabel(self::CUSTOM_STRING_PROPERTY);
        $custom_string_property->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $custom_date_property = new \Docman_Metadata();
        $custom_date_property->setLabel(self::CUSTOM_DATE_PROPERTY);
        $custom_date_property->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $custom_list_property = new \Docman_Metadata();
        $custom_list_property->setLabel(self::CUSTOM_LIST_PROPERTY);
        $custom_list_property->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $this->metadata_factory
            ->method('getMetadataFromLabel')
            ->willReturnMap(
                array_merge(
                    array_map(
                        fn (string $hardcoded_metadata_label): array => [
                            $hardcoded_metadata_label,
                            $this->metadata_factory->getHardCodedMetadataFromLabel(
                                $hardcoded_metadata_label
                            ),
                        ],
                        \Docman_MetadataFactory::HARDCODED_METADATA_LABELS
                    ),
                    [
                        [self::CUSTOM_TEXT_PROPERTY, $custom_text_property],
                        [self::CUSTOM_STRING_PROPERTY, $custom_string_property],
                        [self::CUSTOM_DATE_PROPERTY, $custom_date_property],
                        [self::CUSTOM_LIST_PROPERTY, $custom_list_property],
                    ]
                )
            );

        $this->custom_properties = new SearchColumnCollection();
    }

    public function testItBuildsAReportWithAlwaysThereAndWantedCustomProperties(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = new PostSearchRepresentation();

        $this->custom_properties->add(SearchColumn::buildForSingleValueCustomProperty('field_2', 'Comments'));

        $report = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        self::assertCount(10, $report->getColumnIterator());
    }

    public function testItBuildsAReportWithAGlobalSearchFilter(): void
    {
        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = '*.docx';

        $report = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        self::assertSame($search->global_search, $report->getFiltersArray()[0]->value);
        self::assertSame('My column', $report->columns[0]->md->getLabel());
    }

    public function testItRaises400IfUnknownProperty(): void
    {
        $this->metadata_factory
            ->method('getMetadataFromLabel')
            ->with('unknown')
            ->willThrowException(CustomMetadataException::metadataNotFound('unknown'));

        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('unknown', 'lorem');

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['folder', 1])]
    #[\PHPUnit\Framework\Attributes\TestWith(['file', 2])]
    #[\PHPUnit\Framework\Attributes\TestWith(['link', 3])]
    #[\PHPUnit\Framework\Attributes\TestWith(['embedded', 4])]
    #[\PHPUnit\Framework\Attributes\TestWith(['wiki', 5])]
    #[\PHPUnit\Framework\Attributes\TestWith(['empty', 6])]
    public function testItBuildsAReportWithATypeSearchFilter(string $submitted_type_value, int $expected_internal_value): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('type', $submitted_type_value);

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame('item_type', $second_filter->md->getLabel());
        self::assertSame($expected_internal_value, $second_filter->value);
    }

    public function testItRaisesA400IfTypeIsUnknown(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('type', 'whatever');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
    }

    public function testItBuildsAReportWithATitleSearchFilter(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('title', 'lorem');

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame('title', $second_filter->md->getLabel());
        self::assertSame('lorem', $second_filter->value);
    }

    public function testItBuildsAReportWithAnIdSearchFilter(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('id', '123');

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame('item_id', $second_filter->md->getLabel());
        self::assertSame('123', $second_filter->value);
    }

    public function testItBuildsAReportWithAFilenameSearchFilter(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('filename', 'lorem');

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame('filename', $second_filter->md->getLabel());
        self::assertSame('lorem', $second_filter->value);
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['none', 100])]
    #[\PHPUnit\Framework\Attributes\TestWith(['draft', 101])]
    #[\PHPUnit\Framework\Attributes\TestWith(['approved', 102])]
    #[\PHPUnit\Framework\Attributes\TestWith(['rejected', 103])]
    public function testItBuildsAReportWithAStatusSearchFilter(string $submitted_status_value, int $expected_internal_value): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('status', $submitted_status_value);

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame('status', $second_filter->md->getLabel());
        self::assertSame($expected_internal_value, $second_filter->value);
    }

    public function testItRaisesA400IfStatusIsUnknown(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('status', 'whatever');

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);


        $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
    }

    public function testItBuildsAReportWithADescriptionSearchFilter(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('description', 'lorem');

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame('description', $second_filter->md->getLabel());
        self::assertSame('lorem', $second_filter->value);
    }

    public function testItBuildsAReportWithAOwnerSearchFilterAndUseTheUsernameToSearch(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty('owner', 'John Doe (jdoe)');

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame('owner', $second_filter->md->getLabel());
        self::assertSame('jdoe', $second_filter->value);
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['>', 1])]
    #[\PHPUnit\Framework\Attributes\TestWith(['=', 0])]
    #[\PHPUnit\Framework\Attributes\TestWith(['<', -1])]
    public function testItBuildsAReportWithAnUpdateDateSearchFilter(string $symbol_operator, int $expected_numeric_operator): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithDateProperty('update_date', '2022-01-30', $symbol_operator);

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        assert($second_filter instanceof \Docman_FilterDate);
        self::assertSame('update_date', $second_filter->md->getLabel());
        self::assertSame('2022-01-30', $second_filter->value);
        self::assertSame($expected_numeric_operator, $second_filter->operator);
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['>', 1])]
    #[\PHPUnit\Framework\Attributes\TestWith(['=', 0])]
    #[\PHPUnit\Framework\Attributes\TestWith(['<', -1])]
    public function testItBuildsAReportWithACreateDateSearchFilter(string $symbol_operator, int $expected_numeric_operator): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithDateProperty('create_date', '2022-01-30', $symbol_operator);

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        assert($second_filter instanceof \Docman_FilterDate);
        self::assertSame('create_date', $second_filter->md->getLabel());
        self::assertSame('2022-01-30', $second_filter->value);
        self::assertSame($expected_numeric_operator, $second_filter->operator);
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['>', 1])]
    #[\PHPUnit\Framework\Attributes\TestWith(['=', 0])]
    #[\PHPUnit\Framework\Attributes\TestWith(['<', -1])]
    public function testItBuildsAReportWithAnObsolescenceDateSearchFilter(string $symbol_operator, int $expected_numeric_operator): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithDateProperty('obsolescence_date', '2022-01-30', $symbol_operator);

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        assert($second_filter instanceof \Docman_FilterDate);
        self::assertSame('obsolescence_date', $second_filter->md->getLabel());
        self::assertSame('2022-01-30', $second_filter->value);
        self::assertSame($expected_numeric_operator, $second_filter->operator);
    }

    public function testItBuildsAReportWithACustomTextSearchFilter(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty(self::CUSTOM_TEXT_PROPERTY, 'lorem');

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame(self::CUSTOM_TEXT_PROPERTY, $second_filter->md->getLabel());
        self::assertSame('lorem', $second_filter->value);
    }

    public function testItBuildsAReportWithACustomStringSearchFilter(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty(self::CUSTOM_STRING_PROPERTY, 'lorem');

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame(self::CUSTOM_STRING_PROPERTY, $second_filter->md->getLabel());
        self::assertSame('lorem', $second_filter->value);
    }

    public function testItBuildsAReportWithACustomListSearchFilter(): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithProperty(self::CUSTOM_LIST_PROPERTY, 'lorem');

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertInstanceOf(\Docman_FilterList::class, $second_filter);
        self::assertSame(self::CUSTOM_LIST_PROPERTY, $second_filter->md->getLabel());
        self::assertSame('lorem', $second_filter->value);
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['>', 1])]
    #[\PHPUnit\Framework\Attributes\TestWith(['=', 0])]
    #[\PHPUnit\Framework\Attributes\TestWith(['<', -1])]
    public function testItBuildsAReportWithACustomDateSearchFilter(string $symbol_operator, int $expected_numeric_operator): void
    {
        $folder = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search = $this->searchDocxWithDateProperty(self::CUSTOM_DATE_PROPERTY, '2022-01-30', $symbol_operator);

        $report       = $this->search_report_builder->buildReport($folder, $search, $this->custom_properties);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame('global_txt', $first_filter->md->getLabel());
        self::assertSame('*.docx', $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        assert($second_filter instanceof \Docman_FilterDate);
        self::assertSame(self::CUSTOM_DATE_PROPERTY, $second_filter->md->getLabel());
        self::assertSame('2022-01-30', $second_filter->value);
        self::assertSame($expected_numeric_operator, $second_filter->operator);
    }

    private function searchDocxWithProperty(string $name, string $value): PostSearchRepresentation
    {
        $search                = new PostSearchRepresentation();
        $search->global_search = '*.docx';

        $property        = new SearchPropertyRepresentation();
        $property->name  = $name;
        $property->value = $value;

        $search->properties = [$property];

        return $search;
    }

    private function searchDocxWithDateProperty(string $name, string $date, string $operator): PostSearchRepresentation
    {
        $search                = new PostSearchRepresentation();
        $search->global_search = '*.docx';

        $property             = new SearchPropertyRepresentation();
        $property->name       = $name;
        $property->value_date = new SearchDateRepresentation();

        $property->value_date->operator = $operator;
        $property->value_date->date     = $date;

        $search->properties = [$property];

        return $search;
    }
}
