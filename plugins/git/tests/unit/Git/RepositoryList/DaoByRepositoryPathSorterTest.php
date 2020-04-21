<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\RepositoryList;

use PHPUnit\Framework\TestCase;

class DaoByRepositoryPathSorterTest extends TestCase
{
    public function testSort()
    {
        $repository_list_results = [
            ['repository_name' => 'a.git'],
            ['repository_name' => 'b/a.git'],
            ['repository_name' => 'b/b/a.git'],
            ['repository_name' => 'b/b/b.git'],
            ['repository_name' => 'b/c.git'],
            ['repository_name' => 'deps/tuleap/rhel/6/mediawiki.git'],
            ['repository_name' => 'c.git'],
            ['repository_name' => 'd/e/f.git'],
        ];

        $expected_results = [
            ['repository_name' => 'b/b/a.git'],
            ['repository_name' => 'b/b/b.git'],
            ['repository_name' => 'b/a.git'],
            ['repository_name' => 'b/c.git'],
            ['repository_name' => 'd/e/f.git'],
            ['repository_name' => 'deps/tuleap/rhel/6/mediawiki.git'],
            ['repository_name' => 'a.git'],
            ['repository_name' => 'c.git'],
        ];

        $sorter = new DaoByRepositoryPathSorter();
        $this->assertEquals($expected_results, $sorter->sort($repository_list_results));
    }
}
