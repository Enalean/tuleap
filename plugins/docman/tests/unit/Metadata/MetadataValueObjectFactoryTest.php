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

use Docman_MetadataListOfValuesElement;
use Mockery;
use PHPUnit\Framework\TestCase;

class MetadataValueObjectFactoryTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var MetadataValueObjectFactory
     */
    private $metadata_object_value_factory;

    /**
     * @var Mockery\MockInterface|DocmanMetadataTypeValueFactory
     */
    private $metadata_type_value_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata_type_value_factory = new DocmanMetadataTypeValueFactory();

        $this->metadata_object_value_factory = new MetadataValueObjectFactory(
            $this->metadata_type_value_factory
        );
    }

    public function testItCreateCorrectValueForTextValue(): void
    {
        $item_id  = 1;
        $field_id = 10;
        $type     = PLUGIN_DOCMAN_METADATA_TYPE_TEXT;
        $value    = 'text value';

        $metadata_object_value = $this->metadata_object_value_factory->createMetadataValueObjectWithCorrectValue(
            $item_id,
            $field_id,
            $type,
            $value
        );

        $this->assertEquals($metadata_object_value->getValue(), $value);
    }

    public function testItCreateCorrectValueForListWithSingleValue(): void
    {
        $item_id  = 1;
        $field_id = 10;
        $type     = PLUGIN_DOCMAN_METADATA_TYPE_LIST;
        $value    = 101;

        $metadata_object_value = $this->metadata_object_value_factory->createMetadataValueObjectWithCorrectValue(
            $item_id,
            $field_id,
            $type,
            $value
        );

        $values = $metadata_object_value->getValue();

        $this->assertInstanceOf(Docman_MetadataListOfValuesElement::class, $values[0]);
        $this->assertEquals(count($values), 1);
    }

    public function testItCreateCorrectValueForListWithMultipleValues(): void
    {
        $item_id  = 1;
        $field_id = 10;
        $type     = PLUGIN_DOCMAN_METADATA_TYPE_LIST;
        $value    = [101, 102];

        $metadata_object_value = $this->metadata_object_value_factory->createMetadataValueObjectWithCorrectValue(
            $item_id,
            $field_id,
            $type,
            $value
        );

        $values = $metadata_object_value->getValue();

        $this->assertInstanceOf(Docman_MetadataListOfValuesElement::class, $values[0]);
        $this->assertInstanceOf(Docman_MetadataListOfValuesElement::class, $values[1]);
        $this->assertEquals(count($values), 2);
    }
}
