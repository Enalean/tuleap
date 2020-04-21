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

namespace Tuleap\ProFTPd\Directory;

require_once __DIR__ . '/../../bootstrap.php';

class DirectoryPathParser_CleanPathTest extends \PHPUnit\Framework\TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->parser = new DirectoryPathParser();
    }

    public function itReturnsTheBasePathIfPathIsEmpty()
    {
        $base_path = DirectoryPathParser::BASE_PATH;

        $this->assertEquals($base_path, $this->parser->getCleanPath(''));
        $this->assertEquals($base_path, $this->parser->getCleanPath(null));
        $this->assertEquals($base_path, $this->parser->getCleanPath(false));
    }

    public function testItReturnsTheSubmittedPathIfPathHasNoDotDot()
    {
        $path = 'some_path';
        $this->assertEquals($path, $this->parser->getCleanPath($path));

        $path = 'some_path/to///';
        $this->assertEquals($path, $this->parser->getCleanPath($path));

        $path = 'some_path/./kjjbh2143356578_-hgf';
        $this->assertEquals($path, $this->parser->getCleanPath($path));

        $path = '/./55__-some_path';
        $this->assertEquals($path, $this->parser->getCleanPath($path));
    }

    public function testItReturnsTheParentPathIfPathHasDotDot()
    {
        $path = '../some_path';
        $this->assertEquals('', $this->parser->getCleanPath($path));

        $path = 'some_path/to/../../';
        $this->assertEquals('some_path', $this->parser->getCleanPath($path));

        $path = '../some_path/../some_otherplace';
        $this->assertEquals('', $this->parser->getCleanPath($path));

        $path = '/./..';
        $this->assertEquals('', $this->parser->getCleanPath($path));
    }
}
