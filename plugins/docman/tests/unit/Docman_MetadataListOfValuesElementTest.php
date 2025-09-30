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

use Docman_MetadataListOfValuesElement;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_MetadataListOfValuesElementTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    private Docman_MetadataListOfValuesElement $metadata_list_values;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadata_list_values = new Docman_MetadataListOfValuesElement();
    }

    public function testItInitsTheClassAttributeIfTheRowValueIsSet(): void
    {
        $row = [
            'value_id' => 120,
            'name'     => 'Bang',
            'status'   => 'status Value',
        ];

        $this->metadata_list_values->initFromRow($row);

        self::assertEquals(120, $this->metadata_list_values->getId());
        self::assertEquals('Bang', $this->metadata_list_values->getName());
        self::assertNull($this->metadata_list_values->getDescription());
        self::assertNull($this->metadata_list_values->getRank());
        self::assertEquals('status Value', $this->metadata_list_values->getStatus());
    }

    public function testItReturnsTheNoneValueWhenTheIdIs100(): void
    {
        $this->metadata_list_values->setId(100);
        $this->metadata_list_values->setName('Value Not Expected');

        $metadata_list_value = $this->metadata_list_values->getMetadataValue();

        self::assertEquals('None', $metadata_list_value);
    }

    public function testItReturnsTheMetadataNameWhenTheIdIsNot100(): void
    {
        $this->metadata_list_values->setId(102);
        $this->metadata_list_values->setName('Blanka');

        $metadata_list_value = $this->metadata_list_values->getMetadataValue();

        self::assertEquals('Blanka', $metadata_list_value);
    }
}
