<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Backend;

use BackendLogger;
use ForgeConfig;
use Psr\Log\LogLevel;
use TruncateLevelLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class BackendLoggerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private $log_file;
    /** @var BackendLogger */
    private $logger;

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set('codendi_log', $this->getTmpDir());
        ForgeConfig::set('sys_logger_level', LogLevel::DEBUG);
        ForgeConfig::set('sys_http_user', posix_getuid());

        $this->log_file = tempnam(ForgeConfig::get('codendi_log'), 'codendi_syslog');
        $this->logger   = new BackendLogger($this->log_file);
    }

    public function testItLogsToTheSyslog(): void
    {
        $this->logger->log(\Psr\Log\LogLevel::INFO, 'toto tata');

        $this->assertStringContainsString('toto tata', file_get_contents($this->log_file));
    }

    public function testItAddsTheLevelToTheLogMessage(): void
    {
        $this->logger->info('toto tata');
        $this->assertStringContainsString('[info] toto tata', file_get_contents($this->log_file));
        $this->logger->debug('hej min van');
        $this->assertStringContainsString('[debug] hej min van', file_get_contents($this->log_file));
        $this->logger->warning('au dodo');
        $this->assertStringContainsString('[warning] au dodo', file_get_contents($this->log_file));
        $this->logger->error('arrete!');
        $this->assertStringContainsString('[error] arrete!', file_get_contents($this->log_file));
    }

    public function testItReturnsTheLegacyTuleapLogger(): void
    {
        $logger = BackendLogger::getDefaultLogger();

        $this->assertInstanceOf(TruncateLevelLogger::class, $logger);

        $logger->info('foo');

        $this->assertStringContainsString('[info] foo', file_get_contents($this->getTmpDir() . '/codendi_syslog'));
    }

    public function testItReturnsTheSyslogBasedLogger(): void
    {
        ForgeConfig::set('sys_logger', 'syslog');
        $logger = BackendLogger::getDefaultLogger();

        $this->assertInstanceOf(\Monolog\Logger::class, $logger);
    }

    public function testItReturnsTheFileBaseLogger(): void
    {
        ForgeConfig::set('sys_logger', 'files');
        $logger = BackendLogger::getDefaultLogger();

        $this->assertInstanceOf(TruncateLevelLogger::class, $logger);
    }

    public function testItReturnsFileLoggerWhenCannotConfigure(): void
    {
        ForgeConfig::set('sys_logger', 'graylog2');
        $logger = BackendLogger::getDefaultLogger();

        $logger->info('foo');
        $this->assertStringContainsString('[warning] Unable to setup logger handler graylog2', file_get_contents($this->getTmpDir() . '/codendi_syslog'));
        $this->assertStringContainsString('[info] foo', file_get_contents($this->getTmpDir() . '/codendi_syslog'));
    }
}
