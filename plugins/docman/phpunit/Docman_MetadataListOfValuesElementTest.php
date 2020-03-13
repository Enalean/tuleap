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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class Docman_MetadataListOfValuesElementTest extends TestCase // @codingStandardsIgnoreLine
{

    use MockeryPHPUnitIntegration;

    /**
     * @var \Docman_MetadataListOfValuesElement
     */
    private $metadata_list_values;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata_list_values = new \Docman_MetadataListOfValuesElement();
    }


    public function testItInitsTheClassAttributeIfTheRowValueIsSet(): void
    {
        $row = [
            'value_id' => 120,
            'name'     => 'Bang',
            'status'   => 'status Value'
        ];

        $this->metadata_list_values->initFromRow($row);

        $this->assertEquals(120, $this->metadata_list_values->getId());
        $this->assertEquals('Bang', $this->metadata_list_values->getName());
        $this->assertNull($this->metadata_list_values->getDescription());
        $this->assertNull($this->metadata_list_values->getRank());
        $this->assertEquals('status Value', $this->metadata_list_values->getStatus());
    }

    public function testItReturnsTheNoneValueWhenTheIdIs100(): void
    {
        $this->metadata_list_values->setId(100);
        $this->metadata_list_values->setName("Value Not Expected");

        $metadata_list_value = $this->metadata_list_values->getMetadataValue();

        $this->assertEquals("None", $metadata_list_value);
    }

    public function testItReturnsTheMetadataNameWhenTheIdIsNot100(): void
    {
        $this->metadata_list_values->setId(102);
        $this->metadata_list_values->setName('Blanka');

        $metadata_list_value = $this->metadata_list_values->getMetadataValue();

        $this->assertEquals("Blanka", $metadata_list_value);
    }
}
