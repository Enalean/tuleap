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

use Mockery;
use PHPUnit\Framework\TestCase;

class RepositoryRegexpBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $regexp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->regexp = new RepositoryRegexpBuilder();
    }

    public function testItReturnsAValidRegexpForARepository(): void
    {
        $path        = '/directory';
        $data_access = Mockery::mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $data_access->shouldReceive('escapeLikeValue')->withArgs(['directory'])->andReturn('directory');
        $this->assertEquals($this->regexp->generateRegexpFromPath($path, $data_access), "^(/(directory|\\*))$|^(/(directory|\\*)/)$");
    }

    public function testItReturnsAValidRegexpForARepositoryWithSubdirectories(): void
    {
        $path        = '/directory/subdirectory1/subdirectory2';
        $data_access = Mockery::mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $data_access->shouldReceive('escapeLikeValue')->withArgs(['directory'])->andReturn('directory');
        $data_access->shouldReceive('escapeLikeValue')->withArgs(['subdirectory1'])->andReturn('subdirectory1');
        $data_access->shouldReceive('escapeLikeValue')->withArgs(['subdirectory2'])->andReturn('subdirectory2');
        $this->assertEquals($this->regexp->generateRegexpFromPath($path, $data_access), "^(/(directory|\\*))$|^(/(directory|\\*)/)$|^(/(directory|\\*)/(subdirectory1|\\*))$|^(/(directory|\\*)/(subdirectory1|\\*)/)$|^(/(directory|\\*)/(subdirectory1|\\*)/(subdirectory2|\\*))$|^(/(directory|\\*)/(subdirectory1|\\*)/(subdirectory2|\\*)/)$");
    }
}
