<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\View\Admin;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ValueRanksBuilderTest extends TestCase
{
    public function testGetRanks(): void
    {
        $metadata = new \Docman_ListMetadata();
        $values   = [
            $this->getValue(100, '', 'P', 1),
            $this->getValue(101, 'Am', 'A', 2),
            $this->getValue(102, 'Stram', 'A', 3),
        ];
        $metadata->setListOfValueElements($values);

        $builder = new ValueRanksBuilder();
        self::assertEquals(
            [
                [
                    'value' => 2,
                    'label' => 'After None',
                ],
                [
                    'value' => 3,
                    'label' => 'After Am',
                ],
                [
                    'value' => 4,
                    'label' => 'After Stram',
                ],
            ],
            $builder->getRanks($metadata),
        );
    }

    private function getValue(int $id, string $name, string $status, int $rank): \Docman_MetadataListOfValuesElement
    {
        $value = new \Docman_MetadataListOfValuesElement();
        $value->setId($id);
        $value->setStatus($status);
        $value->setName($name);
        $value->setRank($rank);

        return $value;
    }
}
