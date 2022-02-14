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

namespace Tuleap\Document\Tree;

use Tuleap\Test\PHPUnit\TestCase;

class ListOfSearchCriterionPresenterBuilderTest extends TestCase
{
    public function testGetCriteria(): void
    {
        $builder = new ListOfSearchCriterionPresenterBuilder();

        $criteria = $builder->getCriteria();

        self::assertCount(6, $criteria);
        self::assertEquals('type', $criteria[0]->name);
        self::assertEquals('title', $criteria[1]->name);
        self::assertEquals('description', $criteria[2]->name);
        self::assertEquals('owner', $criteria[3]->name);
        self::assertEquals('create_date', $criteria[4]->name);
        self::assertEquals('update_date', $criteria[5]->name);
    }
}
