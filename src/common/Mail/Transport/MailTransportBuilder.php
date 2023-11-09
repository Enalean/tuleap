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

namespace Tuleap\Mail\Transport;

use ForgeConfig;
use Laminas\Mail;
use Psr\Log\LoggerInterface;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\ConfigKeySecret;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\ConfigKeyValueValidator;
use Tuleap\Mail\Transport\SmtpOptions\SMTPAuthTypeValidator;
use Tuleap\Mail\Transport\SmtpOptions\SmtpOptionsBuilder;

#[ConfigKeyCategory('Email')]
class MailTransportBuilder
{
    #[ConfigKey("Option to define how Tuleap will send emails")]
    #[ConfigKeyString(self::EMAIL_TRANSPORT_SENDMAIL_VALUE)]
    public const TRANSPORT_CONFIG_KEY = 'email_transport';

    #[ConfigKey("Option to define the relay host used when email_transport is configured to 'smtp'. The used port must be provided here.")]
    #[ConfigKeyString('')]
    public const RELAYHOST_CONFIG_KEY     = 'email_relayhost';
    #[ConfigKey("Activate the usage of TLS for the SMTP relay host")]
    #[ConfigKeyInt(0)]
    public const RELAYHOST_SMTP_USE_TLS   = 'email_relayhost_smtp_use_tls';
    #[ConfigKey('Username to use to authenticate against the SMTP relay host')]
    #[ConfigKeyString('')]
    public const RELAYHOST_SMTP_USERNAME  = 'email_relayhost_smtp_username';
    #[ConfigKey('Password to use to authenticate against the SMTP relay host')]
    #[ConfigKeySecret]
    public const RELAYHOST_SMTP_PASSWORD  = 'email_relayhost_smtp_password';
    #[ConfigKey('Type of authentication to use against the SMTP relay host (either plain or login)')]
    #[ConfigKeyString('plain')]
    #[ConfigKeyValueValidator(SMTPAuthTypeValidator::class)]
    public const RELAYHOST_SMTP_AUTH_TYPE = 'email_relayhost_smtp_auth_type';

    public const EMAIL_TRANSPORT_SENDMAIL_VALUE = 'sendmail';
    public const EMAIL_TRANSPORT_SMTP_VALUE     = 'smtp';

    private function __construct()
    {
    }

    public static function buildMailTransport(LoggerInterface $logger): Mail\Transport\TransportInterface
    {
        return self::buildFromMailConfiguration(
            static fn() => new Mail\Transport\Sendmail(),
            static fn(string $relay_host) => new Mail\Transport\Smtp(SmtpOptionsBuilder::buildSmtpOptionFromForgeConfig($relay_host)),
            static fn() => new NotConfiguredSmtpTransport($logger),
            static fn() => new InvalidDefinedTransport($logger),
        );
    }

    /**
     * @psalm-template T
     * @psalm-param callable(): T $build_when_sendmail
     * @psalm-param callable(non-empty-string): T $build_when_smtp
     * @psalm-param callable(): T $build_when_smtp_misconfigured
     * @psalm-param callable(): T $build_when_invalid
     * @psalm-return T
     */
    private static function buildFromMailConfiguration(
        callable $build_when_sendmail,
        callable $build_when_smtp,
        callable $build_when_smtp_misconfigured,
        callable $build_when_invalid,
    ): mixed {
        if (ForgeConfig::get(self::TRANSPORT_CONFIG_KEY, self::EMAIL_TRANSPORT_SENDMAIL_VALUE) === self::EMAIL_TRANSPORT_SENDMAIL_VALUE) {
            return $build_when_sendmail();
        }
        if (ForgeConfig::get(self::TRANSPORT_CONFIG_KEY) === self::EMAIL_TRANSPORT_SMTP_VALUE) {
            $relay_host_config = (string) ForgeConfig::get(self::RELAYHOST_CONFIG_KEY, '');
            if ($relay_host_config === '') {
                return $build_when_smtp_misconfigured();
            }
            return $build_when_smtp($relay_host_config);
        }
        return $build_when_invalid();
    }
}
