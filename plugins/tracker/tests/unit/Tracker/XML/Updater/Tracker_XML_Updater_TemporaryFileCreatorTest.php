<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Updater_TemporaryFileCreatorTest extends \PHPUnit\Framework\TestCase
{
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\TemporaryTestDirectory;

    /** @var Tracker_XML_Updater_TemporaryFileCreator */
    private $creator;
    /**
     * @var string
     */
    private $initial;

    protected function setUp(): void
    {
        ForgeConfig::set('tmp_dir', $this->getTmpDir());

        $this->creator = new Tracker_XML_Updater_TemporaryFileCreator();
        $this->initial = __DIR__ . '/_fixtures/toto.txt';
    }

    protected function tearDown(): void
    {
        // This is needed to trigger Tracker_XML_Updater_TemporaryFileCreator destructor before the clean up
        // of the temporary directory
        // When the temporary directory is removed before the destructor is,
        // Tracker_XML_Updater_TemporaryFileCreator::deleteTemporaryDirectory() crashes
        unset($this->creator);
    }


    public function testItCreatesTemporaryFile(): void
    {
        $copy = $this->creator->createTemporaryFile($this->initial);
        $this->assertFileEquals($this->initial, $copy);
    }

    public function testItCreatesFileInPlateformDefinedTmpDir(): void
    {
        $copy = $this->creator->createTemporaryFile($this->initial);
        $this->assertSame(0, strpos($copy, ForgeConfig::get('tmp_dir')));
    }

    public function testItCreatesFileInATemporaryDirectoryThatIsDifferentFromOtherCreators(): void
    {
        $another_creator = new Tracker_XML_Updater_TemporaryFileCreator();
        $this->assertNotEquals(
            $this->creator->getTemporaryDirectory(),
            $another_creator->getTemporaryDirectory()
        );
    }

    public function testItCleanUpEverythingAtTheVeryEnd(): void
    {
        $temporary_directory = $this->creator->getTemporaryDirectory();

        unset($this->creator);

        $this->assertFileDoesNotExist($temporary_directory);
    }
}
