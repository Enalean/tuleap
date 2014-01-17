<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once 'bootstrap.php';

class Proftpd_Directory_DirectoryPathParser_CleanPathTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->parser = new Proftpd_Directory_DirectoryPathParser();
    }

    public function itReturnsTheBasePathIfPathIsEmpty() {
        $base_path = Proftpd_Directory_DirectoryPathParser::BASE_PATH;

        $this->assertEqual($base_path, $this->parser->getCleanPath(''));
        $this->assertEqual($base_path, $this->parser->getCleanPath(null));
        $this->assertEqual($base_path, $this->parser->getCleanPath(false));
    }

    public function itReturnsTheSubmittedPathIfPathHasNoDotDot() {
        $path = 'some_path';
        $this->assertEqual($path, $this->parser->getCleanPath($path));

        $path = 'some_path/to///';
        $this->assertEqual($path, $this->parser->getCleanPath($path));

        $path = 'some_path/./kjjbh2143356578_-hgf';
        $this->assertEqual($path, $this->parser->getCleanPath($path));

        $path = '/./55__-some_path';
        $this->assertEqual($path, $this->parser->getCleanPath($path));
    }

    public function itReturnsTheParentPathIfPathHasDotDot() {
        $path = '../some_path';
        $this->assertEqual('', $this->parser->getCleanPath($path));

        $path = 'some_path/to/../../';
        $this->assertEqual('some_path', $this->parser->getCleanPath($path));

        $path = '../some_path/../some_otherplace';
        $this->assertEqual('', $this->parser->getCleanPath($path));

        $path = '/./..';
        $this->assertEqual('', $this->parser->getCleanPath($path));
    }
}
?>
