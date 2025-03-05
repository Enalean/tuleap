<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Document;

use Docman_MetadataDao;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use TestHelper;
use Tuleap\Docman\Metadata\MetadataValueCreator;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentMetadataCreatorTest extends TestCase
{
    private MetadataValueCreator&MockObject $value_creator;
    private DocumentMetadataCreator $creator;
    private Docman_MetadataDao&MockObject $metadata_dao;

    protected function setUp(): void
    {
        $this->value_creator = $this->createMock(MetadataValueCreator::class);
        $this->metadata_dao  = $this->createMock(Docman_MetadataDao::class);

        $this->creator = new DocumentMetadataCreator(
            $this->value_creator,
            $this->metadata_dao
        );
    }

    public function testItThrowALogicalExceptionWhenMetadataDoesNotExists(): void
    {
        $this->metadata_dao->method('searchById')->willReturn(false);

        self::expectException(LogicException::class);

        $this->creator->storeItemCustomMetadata(1, [['id' => 5, 'value' => 'abcde']]);
    }

    public function testItDoesNotStoreWhenMetadataIsAnEmptyArray(): void
    {
        $this->metadata_dao->expects(self::never())->method('searchById');
        $this->value_creator->expects(self::never())->method('createMetadataObject');

        $this->creator->storeItemCustomMetadata(1, []);
    }

    public function testItStoreMetadata(): void
    {
        $metadata_list = [['id' => 10, 'value' => 'Text']];
        $this->metadata_dao->method('searchById')
            ->willReturn(TestHelper::arrayToDar(['data_type' => PLUGIN_DOCMAN_METADATA_TYPE_TEXT]));

        $this->value_creator->expects(self::once())->method('createMetadataObject');

        $this->creator->storeItemCustomMetadata(1, $metadata_list);
    }
}
