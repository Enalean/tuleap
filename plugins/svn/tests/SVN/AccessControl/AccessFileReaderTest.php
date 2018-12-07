<?php
/**
* Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\SVN\AccessControl;

use TuleapTestCase;
use Tuleap\SVN\Repository\Repository;


require_once __DIR__ .'/../../bootstrap.php';

class AccessFileReaderTest extends TuleapTestCase {

    /** @var Repository */
    private $repository;

    /** @var AccessFileReader */
    private $reader;

    public function setUp() {
        parent::setUp();
        $fixtures_dir = __DIR__ .'/_fixtures';

        $this->repository = stub('Tuleap\SVN\Repository\Repository')->getSystemPath()->returns($fixtures_dir);

        $this->reader = new AccessFileReader();
    }

    public function itReadsTheDefaultBlock() {
        $this->assertPattern(
            '/le default/',
            $this->reader->readDefaultBlock($this->repository)
        );
    }

    public function itReadsTheContentBlock() {
        $this->assertPattern(
            '/le content/',
            $this->reader->readContentBlock($this->repository)
        );
    }

    public function itDoesNotContainDelimiters() {
        $this->assertNoPattern(
            '/# BEGIN CODENDI DEFAULT SETTINGS/',
            $this->reader->readDefaultBlock($this->repository)
        );
    }
}