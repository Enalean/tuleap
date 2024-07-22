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

namespace Tuleap\Docman\Metadata\ListOfValuesElement;

use Docman_MetadataListOfValuesElementDao;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

final class MetadataListOfValuesElementListBuilderTest extends TestCase
{
    private Docman_MetadataListOfValuesElementDao&MockObject $dao;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(Docman_MetadataListOfValuesElementDao::class);
    }

    public function testBuildListOfListValuesElement(): void
    {
        $id = 1;

        $value     = ['value_id' => 1, 'name' => 'value'];
        $value_two = ['value_id' => 2, 'name' => 'name value 2'];

        $this->dao->method('searchByFieldId')->with($id, false)->willReturn([$value, $value_two]);

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);
        $expected_list_of_elements = [$element, $element_two];

        $list_of_elements_builder = new MetadataListOfValuesElementListBuilder($this->dao);

        $list_of_elements = $list_of_elements_builder->build($id, false);

        self::assertEquals($expected_list_of_elements, $list_of_elements);
    }
}
