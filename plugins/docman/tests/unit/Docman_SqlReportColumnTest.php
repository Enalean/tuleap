<?php
/*
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

use Tuleap\Test\PHPUnit\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_SqlReportColumnTest extends TestCase
{
    public function testItReturnsAnEmptyArrayIfTheFieldIsNotACustomMetadata(): void
    {
        $metadata        = new Docman_Metadata();
        $metadata->label = 'title';

        $report_column = new \Docman_ReportColumn($metadata);

        $sql_report_column                 = new \Docman_SqlReportColumn($report_column);
        $sql_report_column->isRealMetadata = false;

        $table_result = $sql_report_column->getCustomMetadataFromIfNeeded([]);
        self::assertEmpty($table_result);
    }

    public function testItReturnsAnEmptyArrayIfTheColumnIsAlreadyInFromStatement(): void
    {
        $metadata        = new Docman_Metadata();
        $metadata->label = 'field_18';
        $metadata->id    = 18;

        $report_column = new \Docman_ReportColumn($metadata);

        $sql_report_column                 = new \Docman_SqlReportColumn($report_column);
        $sql_report_column->isRealMetadata = true;

        $previous_from_statement = [
            'plugin_docman_metadata_value AS mdv_field_18 ON (mdv_field_18.item_id = i.item_id  AND mdv_field_18.field_id = 18)',
            'plugin_docman_metadata_value AS mdv_field_20 ON (mdv_field_20.item_id = i.item_id  AND mdv_field_20.field_id = 20)',
        ];
        $table_result            = $sql_report_column->getCustomMetadataFromIfNeeded($previous_from_statement);
        self::assertEmpty($table_result);
    }

    public function testItReturnsAnEmptyArrayIfTheColumnIsNotAColumnToSort(): void
    {
        $metadata        = new Docman_Metadata();
        $metadata->label = 'field_18';
        $metadata->id    = 18;

        $report_column = new \Docman_ReportColumn($metadata);
        $report_column->setSort(null);

        $sql_report_column                 = new \Docman_SqlReportColumn($report_column);
        $sql_report_column->isRealMetadata = true;

        $previous_from_statement = [
            'plugin_docman_metadata_value AS mdv_field_30 ON (mdv_field_30.item_id = i.item_id  AND mdv_field_30.field_id = 30)',
            'plugin_docman_metadata_value AS mdv_field_20 ON (mdv_field_20.item_id = i.item_id  AND mdv_field_20.field_id = 20)',
        ];
        $table_result            = $sql_report_column->getCustomMetadataFromIfNeeded($previous_from_statement);
        self::assertEmpty($table_result);
    }

    public function testItReturnsTheFromStatement(): void
    {
        $metadata        = new Docman_Metadata();
        $metadata->label = 'field_18';
        $metadata->id    = 18;

        $report_column = new \Docman_ReportColumn($metadata);
        $report_column->setSort(true);

        $sql_report_column                 = new \Docman_SqlReportColumn($report_column);
        $sql_report_column->isRealMetadata = true;

        $previous_from_statement = [
            'plugin_docman_metadata_value AS mdv_field_30 ON (mdv_field_30.item_id = i.item_id  AND mdv_field_30.field_id = 30)',
            'plugin_docman_metadata_value AS mdv_field_20 ON (mdv_field_20.item_id = i.item_id  AND mdv_field_20.field_id = 20)',
        ];
        $table_result            = $sql_report_column->getCustomMetadataFromIfNeeded($previous_from_statement);

        $expected_from_table_statements = [
            'plugin_docman_metadata_value AS mdv_field_18 ON (mdv_field_18.item_id = i.item_id  AND mdv_field_18.field_id = 18)',
        ];

        self::assertEquals(1, count($table_result));
        self::assertEqualsCanonicalizing($expected_from_table_statements, $table_result);
    }
}
