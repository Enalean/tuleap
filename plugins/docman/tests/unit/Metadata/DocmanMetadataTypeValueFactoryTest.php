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

namespace Tuleap\Docman\Metadata;

use Docman_MetadataValueList;
use Docman_MetadataValueScalar;
use Mockery;
use PHPUnit\Framework\TestCase;

class DocmanMetadataTypeValueFactoryTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|DocmanMetadataTypeValueFactory
     */
    private $type_value_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type_value_factory = new DocmanMetadataTypeValueFactory();
    }

    public function testItReturnsAScalarValueForTextType(): void
    {
        $metadata_value = $this->type_value_factory->createFromType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $this->assertInstanceOf(Docman_MetadataValueScalar::class, $metadata_value);
    }

    public function testItReturnsAScalarValueForStringType(): void
    {
        $metadata_value = $this->type_value_factory->createFromType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $this->assertInstanceOf(Docman_MetadataValueScalar::class, $metadata_value);
    }

    public function testItReturnsAScalarValueForDateType(): void
    {
        $metadata_value = $this->type_value_factory->createFromType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        $this->assertInstanceOf(Docman_MetadataValueScalar::class, $metadata_value);
    }

    public function testItReturnsAListValueForListType(): void
    {
        $metadata_value = $this->type_value_factory->createFromType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $this->assertInstanceOf(Docman_MetadataValueList::class, $metadata_value);
    }

    public function testItThrowsAnExceptionWhenTypeIsNotFound(): void
    {
        $this->expectException(\LogicException::class);
        $this->type_value_factory->createFromType(1233);
    }
}
