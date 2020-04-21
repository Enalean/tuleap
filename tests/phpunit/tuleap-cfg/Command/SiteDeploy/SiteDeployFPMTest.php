<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace TuleapCfg\Command\SiteDeploy;

use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

final class SiteDeployFPMTest extends TestCase
{
    /**
     * @var string
     */
    private $php73_configuration_folder;
    /**
     * @var string
     */
    private $php74_configuration_folder;

    private $temp_dir;
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $vfsStream;
    /**
     * @var string
     */
    private $current_user;

    protected function setUp(): void
    {
        $this->vfsStream = vfsStream::setup();
        $base_dir = $this->vfsStream->url();
        $this->php73_configuration_folder = $base_dir . '/etc/opt/remi/php73';
        mkdir($this->php73_configuration_folder . '/php-fpm.d', 0755, true);
        $this->php74_configuration_folder = $base_dir . '/etc/opt/remi/php74';
        mkdir($this->php74_configuration_folder . '/php-fpm.d', 0755, true);
        $this->temp_dir = $base_dir . '/var/tmp';
        mkdir($this->temp_dir, 0755, true);
        $this->current_user = posix_getpwuid(posix_geteuid())['name'];
    }

    public function testDeployPHP73Prod(): void
    {
        $deploy = new SiteDeployFPM(
            new NullLogger(),
            $this->current_user,
            false,
            $this->php73_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm73',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap.conf');
        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap-long-running-request.conf');
        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap_common.part');
        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap_errors.part');
        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap_sessions.part');

        $this->assertDirectoryExists($this->temp_dir . '/tuleap_cache/php/session');
        $this->assertDirectoryExists($this->temp_dir . '/tuleap_cache/php/wsdlcache');

        $tuleap_conf = file_get_contents($this->php73_configuration_folder . '/php-fpm.d/tuleap_common.part');
        $this->assertStringContainsString('user = ' . $this->current_user, $tuleap_conf);
        $this->assertStringContainsString('group = ' . $this->current_user, $tuleap_conf);

        $this->assertFileEquals(__DIR__ . '/../../../../../src/etc/fpm73/tuleap_errors_prod.part', $this->php73_configuration_folder . '/php-fpm.d/tuleap_errors.part');
    }

    public function testDeployPHP74Prod(): void
    {
        $deploy = new SiteDeployFPM(
            new NullLogger(),
            $this->current_user,
            false,
            $this->php74_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm74',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        $this->assertFileExists($this->php74_configuration_folder . '/php-fpm.d/tuleap.conf');
        $this->assertFileExists($this->php74_configuration_folder . '/php-fpm.d/tuleap-long-running-request.conf');
        $this->assertFileExists($this->php74_configuration_folder . '/php-fpm.d/tuleap_common.part');
        $this->assertFileExists($this->php74_configuration_folder . '/php-fpm.d/tuleap_errors.part');
        $this->assertFileExists($this->php74_configuration_folder . '/php-fpm.d/tuleap_sessions.part');

        $this->assertDirectoryExists($this->temp_dir . '/tuleap_cache/php/session');
        $this->assertDirectoryExists($this->temp_dir . '/tuleap_cache/php/wsdlcache');

        $tuleap_conf = file_get_contents($this->php74_configuration_folder . '/php-fpm.d/tuleap_common.part');
        $this->assertStringContainsString('user = ' . $this->current_user, $tuleap_conf);
        $this->assertStringContainsString('group = ' . $this->current_user, $tuleap_conf);

        $this->assertFileEquals(__DIR__ . '/../../../../../src/etc/fpm73/tuleap_errors_prod.part', $this->php74_configuration_folder . '/php-fpm.d/tuleap_errors.part');
    }

    public function testDeployPHP73Dev(): void
    {
        $deploy = new SiteDeployFPM(
            new NullLogger(),
            $this->current_user,
            true,
            $this->php73_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm73',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap.conf');
        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap-long-running-request.conf');
        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap_common.part');
        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap_errors.part');
        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/tuleap_sessions.part');

        $this->assertDirectoryExists($this->temp_dir . '/tuleap_cache/php/session');
        $this->assertDirectoryExists($this->temp_dir . '/tuleap_cache/php/wsdlcache');

        $tuleap_conf = file_get_contents($this->php73_configuration_folder . '/php-fpm.d/tuleap_common.part');
        $this->assertStringContainsString('user = ' . $this->current_user, $tuleap_conf);
        $this->assertStringContainsString('group = ' . $this->current_user, $tuleap_conf);

        $this->assertFileEquals(__DIR__ . '/../../../../../src/etc/fpm73/tuleap_errors_dev.part', $this->php73_configuration_folder . '/php-fpm.d/tuleap_errors.part');
    }

    public function testDeployDoesntTouchExistingFilesByDefault(): void
    {
        $all_files = [
            'tuleap.conf',
            'tuleap-long-running-request.conf',
            'tuleap_common.part',
            'tuleap_errors.part',
            'tuleap_sessions.part'
        ];
        foreach ($all_files as $file) {
            touch($this->php73_configuration_folder . '/php-fpm.d/' . $file);
        }

        $deploy = new SiteDeployFPM(
            new NullLogger(),
            $this->current_user,
            false,
            $this->php73_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm73',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        foreach ($all_files as $file) {
            $this->assertEquals('', file_get_contents($this->php73_configuration_folder . '/php-fpm.d/' . $file));
        }
    }

    public function testForceDeployOverrideExistingFiles(): void
    {
        $all_files = [
            'tuleap.conf',
            'tuleap-long-running-request.conf',
            'tuleap_common.part',
            'tuleap_errors.part',
            'tuleap_sessions.part'
        ];
        foreach ($all_files as $file) {
            touch($this->php73_configuration_folder . '/php-fpm.d/' . $file);
        }

        $deploy = new SiteDeployFPM(
            new NullLogger(),
            $this->current_user,
            false,
            $this->php73_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm73',
            [],
            $this->temp_dir,
        );
        $deploy->forceDeploy();

        foreach ($all_files as $file) {
            $target_file = $this->php73_configuration_folder . '/php-fpm.d/' . $file;
            $this->assertNotEmpty(file_get_contents($this->php73_configuration_folder . '/php-fpm.d/' . $file), "$target_file should not be empty");
        }
        $this->assertStringContainsString('user = ' . $this->current_user, file_get_contents($this->php73_configuration_folder . '/php-fpm.d/tuleap_common.part'));
    }


    public function testForceDeployRemovesExistingTuleapPartsFiles(): void
    {
        touch($this->php73_configuration_folder . '/php-fpm.d/tuleap_errors_stuff.part');
        touch($this->php73_configuration_folder . '/php-fpm.d/custom_stuff.part');

        $deploy = new SiteDeployFPM(
            new NullLogger(),
            $this->current_user,
            false,
            $this->php73_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm73',
            [],
            $this->temp_dir,
        );
        $deploy->forceDeploy();

        $this->assertFileDoesNotExist($this->php73_configuration_folder . '/php-fpm.d/tuleap_errors_stuff.part');
        $this->assertFileExists($this->php73_configuration_folder . '/php-fpm.d/custom_stuff.part');
    }

    public function testDeployCreateMissingFiles(): void
    {
        $all_files = [
            'tuleap.conf',
            'tuleap-long-running-request.conf',
            'tuleap_errors.part',
            'tuleap_sessions.part'
        ];
        foreach ($all_files as $file) {
            touch($this->php73_configuration_folder . '/php-fpm.d/' . $file);
        }

        $deploy = new SiteDeployFPM(
            new NullLogger(),
            $this->current_user,
            false,
            $this->php73_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm73',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        foreach ($all_files as $file) {
            $this->assertEquals('', file_get_contents($this->php73_configuration_folder . '/php-fpm.d/' . $file));
        }
        $this->assertStringContainsString('user = ' . $this->current_user, file_get_contents($this->php73_configuration_folder . '/php-fpm.d/tuleap_common.part'));
    }
}
