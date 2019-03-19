<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Test\Psalm;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class PsalmCommandLauncherWithIgnoreDirectoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var vfsStreamDirectory
     */
    private $tmp_dir;

    protected function setUp(): void
    {
        $this->tmp_dir = vfsStream::setup();
    }

    public function testPsalmCommandIsCalledWithRewrittenConfig() : void
    {
        $ignored_directory_provider = \Mockery::mock(PsalmIgnoreDirectory::class);

        $temporary_dir_for_rewritten_config = $this->tmp_dir->url() . DIRECTORY_SEPARATOR . 'conf';
        mkdir($temporary_dir_for_rewritten_config);

        $shell_passthrough = function (string $command, int &$return_var) use ($temporary_dir_for_rewritten_config) {
            $this->assertStringNotContainsString('{config_path}', $command);
            $tmp_files = scandir($temporary_dir_for_rewritten_config, SCANDIR_SORT_DESCENDING);
            $this->assertCount(3, $tmp_files);

            $this->assertXmlStringEqualsXmlString(
                '<psalm><projectFiles><ignoreFiles><directory name="ignore1"/><directory name="ignore2"/></ignoreFiles></projectFiles></psalm>',
                file_get_contents($temporary_dir_for_rewritten_config . DIRECTORY_SEPARATOR . $tmp_files[0])
            );

            $return_var = 147;
        };

        $command_launcher = new PsalmCommandLauncherWithIgnoreDirectory(
            $temporary_dir_for_rewritten_config,
            $ignored_directory_provider,
            $shell_passthrough
        );
        $ignored_directory_provider->shouldReceive('getIgnoredDirectories')->andReturn(['ignore1', 'ignore2']);

        $existing_config_path = $this->tmp_dir->url() . DIRECTORY_SEPARATOR . 'psalm.xml';
        file_put_contents($existing_config_path, '<psalm/>');

        $exit_code = $command_launcher->execute('init_script', $existing_config_path, './src/vendor/bin/psalm', '-c={config_path}');
        $this->assertEquals(147, $exit_code);
        $tmp_files = scandir($temporary_dir_for_rewritten_config, SCANDIR_SORT_DESCENDING);
        $this->assertCount(2, $tmp_files);
    }

    public function testIncorrectCallToInitScriptIsRejected() : void
    {
        $command_launcher = new PsalmCommandLauncherWithIgnoreDirectory(
            $this->tmp_dir->url(),
            \Mockery::mock(PsalmIgnoreDirectory::class),
            function () {
            }
        );

        $exit_code = $command_launcher->execute('init_script');

        $this->assertGreaterThan(0, $exit_code);
        $this->expectOutputRegex('/^Usage: init_script/');
    }

    public function testFailsWhenConfigFileCanNotBeFound() : void
    {
        $command_launcher = new PsalmCommandLauncherWithIgnoreDirectory(
            $this->tmp_dir->url(),
            \Mockery::mock(PsalmIgnoreDirectory::class),
            function () {
            }
        );

        $config_path = $this->tmp_dir->url() . DIRECTORY_SEPARATOR . 'not_existing_config';

        $exit_code = $command_launcher->execute(
            'init_script',
            $config_path,
            './src/vendor/bin/psalm'
        );

        $this->assertGreaterThan(0, $exit_code);
        $this->expectOutputString("$config_path can not be read\n");
    }

    public function testFailsWhenConfigFileIsNotAValidXMLFile() : void
    {
        $command_launcher = new PsalmCommandLauncherWithIgnoreDirectory(
            $this->tmp_dir->url(),
            \Mockery::mock(PsalmIgnoreDirectory::class),
            function () {
            }
        );

        $config_path = $this->tmp_dir->url() . DIRECTORY_SEPARATOR . 'not_xml';
        file_put_contents($config_path, 'Not XML data');

        $exit_code = $command_launcher->execute(
            'init_script',
            $config_path,
            './src/vendor/bin/psalm'
        );

        $this->assertGreaterThan(0, $exit_code);
        $this->expectOutputString("$config_path is not a valid XML file\n");
    }

    public function testUnknownPsalmCommandIsRejected() : void
    {
        $command_launcher = new PsalmCommandLauncherWithIgnoreDirectory(
            $this->tmp_dir->url(),
            \Mockery::mock(PsalmIgnoreDirectory::class),
            function () {
            }
        );

        $config_path = $this->tmp_dir->url() . DIRECTORY_SEPARATOR . 'psalm.xml';
        file_put_contents($config_path, '<psalm/>');

        $exit_code = $command_launcher->execute(
            'init_script',
            $config_path,
            'wrong_command'
        );
        $this->assertGreaterThan(0, $exit_code);
        $this->expectOutputRegex('/^wrong_command is not a Psalm command/');
    }

    public function testDoNotExecuteToUpdateBaseline() : void
    {
        $command_launcher = new PsalmCommandLauncherWithIgnoreDirectory(
            $this->tmp_dir->url(),
            \Mockery::mock(PsalmIgnoreDirectory::class),
            function () {
            }
        );

        $exit_code = $command_launcher->execute(
            'init_script',
            'my_config_path',
            './src/vendor/bin/psalm',
            '--update-baseline'
        );

        $this->assertGreaterThan(0, $exit_code);
        $this->expectOutputRegex('/baseline/');
    }
}
