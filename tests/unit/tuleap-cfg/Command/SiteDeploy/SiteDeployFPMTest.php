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

use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;
use Tuleap\TemporaryTestDirectory;
use TuleapCfg\Command\ProcessFactory;
use TuleapCfg\Command\SiteDeploy\FPM\FPMSessionFiles;
use TuleapCfg\Command\SiteDeploy\FPM\FPMSessionRedis;
use TuleapCfg\Command\SiteDeploy\FPM\SiteDeployFPM;

final class SiteDeployFPMTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    private string $php_configuration_folder;
    /**
     * @var string
     */
    private $temp_dir;
    /**
     * @var string
     */
    private $current_user;
    /**
     * @var string
     */
    private $tuleap_redis_conf_file;

    protected function setUp(): void
    {
        $base_dir                       = $this->getTmpDir();
        $this->php_configuration_folder = $base_dir . '/etc/opt/remi/php82';
        mkdir($this->php_configuration_folder . '/php-fpm.d', 0755, true);
        $this->temp_dir = $base_dir . '/var/tmp';
        mkdir($this->temp_dir, 0755, true);
        $tuleap_conf_dir = $base_dir . '/etc/tuleap/conf';
        mkdir($tuleap_conf_dir, 0755, true);
        $this->tuleap_redis_conf_file = $tuleap_conf_dir . '/redis.inc';
        $this->current_user           = posix_getpwuid(posix_geteuid())['name'];
    }

    public function testDeployphp82Prod(): void
    {
        $deploy = new SiteDeployFPM(
            $this->buildAlwaysSuccessfulProcessFactory(),
            new NullLogger(),
            $this->current_user,
            false,
            new FPMSessionFiles(),
            ['php-fpm.service'],
            $this->php_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm82',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        $this->assertFileExists($this->php_configuration_folder . '/php-fpm.d/tuleap.conf');
        $this->assertFileExists($this->php_configuration_folder . '/php-fpm.d/tuleap-long-running-request.conf');
        $this->assertFileExists($this->php_configuration_folder . '/php-fpm.d/tuleap_common.part');
        $this->assertFileExists($this->php_configuration_folder . '/php-fpm.d/tuleap_errors.part');
        $this->assertFileExists($this->php_configuration_folder . '/php-fpm.d/tuleap_sessions.part');

        $this->assertDirectoryExists($this->temp_dir . '/tuleap_cache/php/session');

        $tuleap_conf = file_get_contents($this->php_configuration_folder . '/php-fpm.d/tuleap_common.part');
        $this->assertStringContainsString('user = ' . $this->current_user, $tuleap_conf);
        $this->assertStringContainsString('group = ' . $this->current_user, $tuleap_conf);

        $this->assertFileEquals(__DIR__ . '/../../../../../src/etc/fpm82/tuleap_errors_prod.part', $this->php_configuration_folder . '/php-fpm.d/tuleap_errors.part');
    }

    public function testDeployDoesntTouchExistingFilesByDefault(): void
    {
        $all_files = [
            'tuleap.conf',
            'tuleap-long-running-request.conf',
            'tuleap_common.part',
            'tuleap_errors.part',
            'tuleap_sessions.part',
        ];
        foreach ($all_files as $file) {
            touch($this->php_configuration_folder . '/php-fpm.d/' . $file);
        }

        $deploy = new SiteDeployFPM(
            $this->buildAlwaysSuccessfulProcessFactory(),
            new NullLogger(),
            $this->current_user,
            false,
            new FPMSessionFiles(),
            ['php-fpm.service'],
            $this->php_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm82',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        foreach ($all_files as $file) {
            $this->assertEquals('', file_get_contents($this->php_configuration_folder . '/php-fpm.d/' . $file));
        }
    }

    public function testForceDeployOverrideExistingFiles(): void
    {
        $all_files = [
            'tuleap.conf',
            'tuleap-long-running-request.conf',
            'tuleap_common.part',
            'tuleap_errors.part',
            'tuleap_sessions.part',
        ];
        foreach ($all_files as $file) {
            touch($this->php_configuration_folder . '/php-fpm.d/' . $file);
        }

        $deploy = new SiteDeployFPM(
            $this->buildAlwaysSuccessfulProcessFactory(),
            new NullLogger(),
            $this->current_user,
            false,
            new FPMSessionFiles(),
            ['php-fpm.service'],
            $this->php_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm82',
            [],
            $this->temp_dir,
        );
        $deploy->forceDeploy();

        foreach ($all_files as $file) {
            $target_file = $this->php_configuration_folder . '/php-fpm.d/' . $file;
            $this->assertNotEmpty(file_get_contents($this->php_configuration_folder . '/php-fpm.d/' . $file), "$target_file should not be empty");
        }
        $this->assertStringContainsString('user = ' . $this->current_user, file_get_contents($this->php_configuration_folder . '/php-fpm.d/tuleap_common.part'));
    }

    public function testForceDeployRemovesExistingTuleapPartsFiles(): void
    {
        touch($this->php_configuration_folder . '/php-fpm.d/tuleap_errors_stuff.part');
        touch($this->php_configuration_folder . '/php-fpm.d/custom_stuff.part');

        $deploy = new SiteDeployFPM(
            $this->buildAlwaysSuccessfulProcessFactory(),
            new NullLogger(),
            $this->current_user,
            false,
            new FPMSessionFiles(),
            ['php-fpm.service'],
            $this->php_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm82',
            [],
            $this->temp_dir,
        );
        $deploy->forceDeploy();

        $this->assertFileDoesNotExist($this->php_configuration_folder . '/php-fpm.d/tuleap_errors_stuff.part');
        $this->assertFileExists($this->php_configuration_folder . '/php-fpm.d/custom_stuff.part');
    }

    public function testDeployCreateMissingFiles(): void
    {
        $all_files = [
            'tuleap.conf',
            'tuleap-long-running-request.conf',
            'tuleap_errors.part',
            'tuleap_sessions.part',
        ];
        foreach ($all_files as $file) {
            touch($this->php_configuration_folder . '/php-fpm.d/' . $file);
        }

        $deploy = new SiteDeployFPM(
            $this->buildAlwaysSuccessfulProcessFactory(),
            new NullLogger(),
            $this->current_user,
            false,
            new FPMSessionFiles(),
            ['php-fpm.service'],
            $this->php_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm82',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        foreach ($all_files as $file) {
            $this->assertEquals('', file_get_contents($this->php_configuration_folder . '/php-fpm.d/' . $file));
        }
        $this->assertStringContainsString('user = ' . $this->current_user, file_get_contents($this->php_configuration_folder . '/php-fpm.d/tuleap_common.part'));
    }

    public function testDeployphp82WithSimpleRedisSession(): void
    {
        $deploy = new SiteDeployFPM(
            $this->buildAlwaysSuccessfulProcessFactory(),
            new NullLogger(),
            $this->current_user,
            false,
            new FPMSessionRedis($this->tuleap_redis_conf_file, $this->current_user, 'redis'),
            ['php-fpm.service'],
            $this->php_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm82',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        $tuleap_conf = file_get_contents($this->php_configuration_folder . '/php-fpm.d/tuleap_sessions.part');
        $this->assertStringContainsString('php_value[session.save_handler] = redis', $tuleap_conf);
        $this->assertStringContainsString('php_value[session.save_path]    = "tcp://redis:6379"', $tuleap_conf);

        require($this->tuleap_redis_conf_file);
        $this->assertEquals('redis', $redis_server);
        $this->assertEquals(6379, $redis_port);
        $this->assertEquals('', $redis_password);
    }

    public function testDeployphp82WithAuthenticatedRedisSession(): void
    {
        $deploy = new SiteDeployFPM(
            $this->buildAlwaysSuccessfulProcessFactory(),
            new NullLogger(),
            $this->current_user,
            false,
            new FPMSessionRedis($this->tuleap_redis_conf_file, $this->current_user, 'another-redis', false, 7222, 'this_is_secure,really'),
            ['php-fpm.service'],
            $this->php_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm82',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        $tuleap_conf = file_get_contents($this->php_configuration_folder . '/php-fpm.d/tuleap_sessions.part');
        self::assertStringContainsString('php_value[session.save_handler] = redis', $tuleap_conf);
        self::assertStringContainsString('php_value[session.save_path]    = "tcp://another-redis:7222?auth=this_is_secure%2Creally"', $tuleap_conf);

        require($this->tuleap_redis_conf_file);
        self::assertEquals('another-redis', $redis_server);
        self::assertEquals(7222, $redis_port);
        self::assertEquals('this_is_secure,really', $redis_password);
    }

    public function testDeployphp82WithAuthenticatedRedisSessionWithTLS(): void
    {
        $deploy = new SiteDeployFPM(
            $this->buildAlwaysSuccessfulProcessFactory(),
            new NullLogger(),
            $this->current_user,
            false,
            new FPMSessionRedis($this->tuleap_redis_conf_file, $this->current_user, 'another-redis', true, 7222, ''),
            ['php-fpm.service'],
            $this->php_configuration_folder,
            __DIR__ . '/../../../../../src/etc/fpm82',
            [],
            $this->temp_dir,
        );
        $deploy->configure();

        $tuleap_conf = file_get_contents($this->php_configuration_folder . '/php-fpm.d/tuleap_sessions.part');
        $this->assertStringContainsString('php_value[session.save_handler] = redis', $tuleap_conf);
        $this->assertStringContainsString('php_value[session.save_path]    = "tls://another-redis:7222"', $tuleap_conf);

        require($this->tuleap_redis_conf_file);
        $this->assertEquals('tls://another-redis', $redis_server);
        $this->assertEquals(7222, $redis_port);
        $this->assertEquals('', $redis_password);
    }

    private function buildAlwaysSuccessfulProcessFactory(): ProcessFactory
    {
        return new class extends ProcessFactory
        {
            public function getProcess(array $args): Process
            {
                return new Process([]);
            }
        };
    }
}
