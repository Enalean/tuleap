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

namespace Tuleap\Mail\Transport;

use Laminas\Mail\Transport\Sendmail;
use Laminas\Mail\Transport\Smtp;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

class MailTransportBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testItReturnsLegacySendmailIfNoOptionSet(): void
    {
        self::assertInstanceOf(
            Sendmail::class,
            MailTransportBuilder::buildMailTransport(new NullLogger())
        );
    }

    public function testItReturnsLegacySendmailIfOptionIsSetToSystem(): void
    {
        \ForgeConfig::set(
            "email_transport",
            "sendmail",
        );

        self::assertInstanceOf(
            Sendmail::class,
            MailTransportBuilder::buildMailTransport(new NullLogger())
        );
    }

    public function testItReturnsNotConfiguredSmtpTransportIfOptionIsSetToSmtpAndNotConfigured(): void
    {
        \ForgeConfig::set(
            "email_transport",
            "smtp",
        );

        self::assertInstanceOf(
            NotConfiguredSmtpTransport::class,
            MailTransportBuilder::buildMailTransport(new NullLogger())
        );
    }

    public function testItReturnsSmtpIfOptionIsSetToSmtpAndWellConfigured(): void
    {
        \ForgeConfig::set(
            "email_transport",
            "smtp",
        );

        \ForgeConfig::set(
            "email_relayhost",
            "https://example.com:443",
        );

        self::assertInstanceOf(
            Smtp::class,
            MailTransportBuilder::buildMailTransport(new NullLogger())
        );
    }

    public function testItReturnsInvalidTransportIfOptionIsSetToAnyOtherValue(): void
    {
        \ForgeConfig::set(
            "email_transport",
            "whatever",
        );

        self::assertInstanceOf(
            InvalidDefinedTransport::class,
            MailTransportBuilder::buildMailTransport(new NullLogger())
        );
    }
}
