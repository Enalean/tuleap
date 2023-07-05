<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

class RepositoryRegexpBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RepositoryRegexpBuilder $regexp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->regexp = new RepositoryRegexpBuilder();
    }

    public function testItReturnsAValidRegexpForARepository(): void
    {
        $path        = '/directory';
        $data_access = $this->createMock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $data_access->method('escapeLikeValue')->with('directory')->willReturn('directory');
        self::assertEquals($this->regexp->generateRegexpFromPath($path, $data_access), "^(/(directory|\\*))$|^(/(directory|\\*)/)$");
    }

    public function testItReturnsAValidRegexpForARepositoryWithSubdirectories(): void
    {
        $path        = '/directory/subdirectory1/subdirectory2';
        $data_access = $this->createMock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);

        $data_access->method('escapeLikeValue')->willReturnMap([
            ['directory', 'directory'],
            ['subdirectory1', 'subdirectory1'],
            ['subdirectory2', 'subdirectory2'],
        ]);

        self::assertEquals(
            "^(/(directory|\\*))$|^(/(directory|\\*)/)$|^(/(directory|\\*)/(subdirectory1|\\*))$|^(/(directory|\\*)/(subdirectory1|\\*)/)$|^(/(directory|\\*)/(subdirectory1|\\*)/(subdirectory2|\\*))$|^(/(directory|\\*)/(subdirectory1|\\*)/(subdirectory2|\\*)/)$",
            $this->regexp->generateRegexpFromPath($path, $data_access),
        );
    }
}
