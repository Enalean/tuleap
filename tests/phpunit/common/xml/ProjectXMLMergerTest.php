<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\XML;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\TemporaryTestDirectory;

class ProjectXMLMergerTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    private $fixtures;
    private $destination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = __DIR__ . '/_fixtures';
        $this->destination   = tempnam($this->getTmpDir(), 'XML');
    }

    protected function tearDown(): void
    {
        unlink($this->destination);
        parent::tearDown();
    }

    public function testItMergesTwoXMLFilesInOne()
    {
        $source1 = "$this->fixtures/source1.xml";
        $source2 = "$this->fixtures/source2.xml";

        $expected = file_get_contents("$this->fixtures/expected.xml");

        $merger = new ProjectXMLMerger();
        $merger->merge($source1, $source2, $this->destination);

        $this->assertEquals($expected, file_get_contents($this->destination));
    }
}
