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
require_once __DIR__ . '/../../../bootstrap.php';

class Tracker_XML_Updater_TemporaryFileCreatorTest extends TuleapTestCase
{

    /** @var Tracker_XML_Updater_TemporaryFileCreator */
    private $creator;
    /**
     * @var string
     */
    private $initial;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('tmp_dir', sys_get_temp_dir());

        $this->creator = new Tracker_XML_Updater_TemporaryFileCreator();
        $this->initial = __DIR__ . '/_fixtures/toto.txt';
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itCreatesTemporaryFile()
    {
        $copy = $this->creator->createTemporaryFile($this->initial);
        $this->assertEqual(file_get_contents($this->initial), file_get_contents($copy));
    }

    public function itCreatesFileInPlateformDefinedTmpDir()
    {
        $copy = $this->creator->createTemporaryFile($this->initial);
        $this->assertTrue(strpos($copy, ForgeConfig::get('tmp_dir')) === 0);
    }

    public function itCreatesFileInATemporaryDirectoryThatIsDifferentFromOtherCreators()
    {
        $another_creator = new Tracker_XML_Updater_TemporaryFileCreator();
        $this->assertNotEqual(
            $this->creator->getTemporaryDirectory(),
            $another_creator->getTemporaryDirectory()
        );
    }

    public function itCleanUpEverythingAtTheVeryEnd()
    {
        $temporary_directory = $this->creator->getTemporaryDirectory();

        unset($this->creator);

        $this->assertFalse(file_exists($temporary_directory));
    }
}
