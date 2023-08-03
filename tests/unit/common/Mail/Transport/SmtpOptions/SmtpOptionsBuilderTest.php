<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Mail\Transport\SmtpOptions;

use org\bovigo\vfs\vfsStream;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

final class SmtpOptionsBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    private const DEFAULT_PORT = 25;

    protected function setUp(): void
    {
        \ForgeConfig::set('email_relayhost_smtp_use_tls', '0');
    }

    public function testItBuildsSmtpOptionsWithHostAndPortFromConfig(): void
    {
        $smtp_options = SmtpOptionsBuilder::buildSmtpOptionFromForgeConfig("url:443");

        self::assertSame("url", $smtp_options->getHost());
        self::assertSame(443, $smtp_options->getPort());
    }

    public function testItBuildsSmtpOptionsWithHostOnlyFromConfig(): void
    {
        $smtp_options = SmtpOptionsBuilder::buildSmtpOptionFromForgeConfig("url");

        self::assertSame("url", $smtp_options->getHost());
        self::assertSame(self::DEFAULT_PORT, $smtp_options->getPort());
    }

    public function testItBuildsSmtpOptionsWithHostAndEmptyPortFromConfig(): void
    {
        $smtp_options = SmtpOptionsBuilder::buildSmtpOptionFromForgeConfig("url:");

        self::assertSame("url", $smtp_options->getHost());
        self::assertSame(self::DEFAULT_PORT, $smtp_options->getPort());
    }

    public function testSetupSMTPAuthWithTLS(): void
    {
        \ForgeConfig::set('sys_custom_dir', vfsStream::setup('root', null, ['conf' => []])->url());

        \ForgeConfig::set('email_relayhost_smtp_use_tls', '1');
        \ForgeConfig::set('email_relayhost_smtp_auth_type', 'login');
        \ForgeConfig::set('email_relayhost_smtp_username', 'username');
        \ForgeConfig::set('email_relayhost_smtp_password', \ForgeConfig::encryptValue(new ConcealedString('password')));

        $smtp_options = SmtpOptionsBuilder::buildSmtpOptionFromForgeConfig("smtp.example.com");

        self::assertSame(['username' => 'username', 'password' => 'password', 'ssl' => 'tls'], $smtp_options->getConnectionConfig());
        self::assertSame('login', $smtp_options->getConnectionClass());
    }
}
