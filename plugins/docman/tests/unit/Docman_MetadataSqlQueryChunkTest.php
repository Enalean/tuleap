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

use Tuleap\Test\PHPUnit\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_MetadataSqlQueryChunkTest extends TestCase
{
    public function testItBuildsTheSqlFieldNamePartForACustomTextMetadata(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('field_1');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $metadata_sql_query_chunk = new Docman_MetadataSqlQueryChunk($metadata);

        self::assertSame('mdv_field_1.valueText', $metadata_sql_query_chunk->field);
    }

    public function testItBuildsTheSqlFieldNamePartForACustomStringMetadata(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('field_1');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $metadata_sql_query_chunk = new Docman_MetadataSqlQueryChunk($metadata);

        self::assertSame('mdv_field_1.valueString', $metadata_sql_query_chunk->field);
    }

    public function testItBuildsTheSqlFieldNamePartForACustomDateMetadata(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('field_1');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $metadata_sql_query_chunk = new Docman_MetadataSqlQueryChunk($metadata);

        self::assertSame('mdv_field_1.valueDate', $metadata_sql_query_chunk->field);
    }

    public function testItBuildsTheSqlFieldNamePartForACustomListMetadata(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('field_1');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $metadata_sql_query_chunk = new Docman_MetadataSqlQueryChunk($metadata);

        self::assertSame('mdv_field_1.valueInt', $metadata_sql_query_chunk->field);
    }

    public function testItBuildsTheSqlFieldNamePartForTheOwnerMetadata(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('owner');

        $metadata_sql_query_chunk = new Docman_MetadataSqlQueryChunk($metadata);

        self::assertSame('i.user_id', $metadata_sql_query_chunk->field);
    }

    public function testItBuildsTheSqlFieldNamePartForTheFilenameMetadata(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('filename');

        $metadata_sql_query_chunk = new Docman_MetadataSqlQueryChunk($metadata);

        self::assertSame('(i.item_type = 2) DESC, v.filename', $metadata_sql_query_chunk->field);
    }

    public function testItBuildsTheSqlFieldNamePartForHardcodedMetadata(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setLabel('title');

        $metadata_sql_query_chunk = new Docman_MetadataSqlQueryChunk($metadata);

        self::assertSame('i.title', $metadata_sql_query_chunk->field);
    }
}
