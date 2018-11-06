<?php
/**
* Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\Svn\Repository;

use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class RepositoryRegexpBuilderTest extends TuleapTestCase {

    private $regexp;

    public function setUp() {
        parent::setUp();
        $this->regexp = new RepositoryRegexpBuilder();
    }

    public function itReturnsAValidRegexpForARepository()
    {
        $path        = '/directory';
        $data_access = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        stub($data_access)->escapeLikeValue('directory')->returns('directory');
        $this->assertEqual($this->regexp->generateRegexpFromPath($path, $data_access), "^(/(directory|\\*))$|^(/(directory|\\*)/)$");
    }

    public function itReturnsAValidRegexpForARepositoryWithSubdirectories()
    {
        $path        = '/directory/subdirectory1/subdirectory2';
        $data_access = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        stub($data_access)->escapeLikeValue('directory')->returns('directory');
        stub($data_access)->escapeLikeValue('subdirectory1')->returns('subdirectory1');
        stub($data_access)->escapeLikeValue('subdirectory2')->returns('subdirectory2');
        $this->assertEqual($this->regexp->generateRegexpFromPath($path, $data_access), "^(/(directory|\\*))$|^(/(directory|\\*)/)$|^(/(directory|\\*)/(subdirectory1|\\*))$|^(/(directory|\\*)/(subdirectory1|\\*)/)$|^(/(directory|\\*)/(subdirectory1|\\*)/(subdirectory2|\\*))$|^(/(directory|\\*)/(subdirectory1|\\*)/(subdirectory2|\\*)/)$");
    }
}