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
use Tuleap\Config\ConfigKeyString;
use Tuleap\Mail\Transport\SmtpOptions\SmtpOptionsBuilder;

class MailTransportBuilder
{
    #[ConfigKey("Option to define how Tuleap will send emails")]
    #[ConfigKeyString(self::EMAIL_TRANSPORT_SENDMAIL_VALUE)]
    public const TRANSPORT_CONFIG_KEY = 'email_transport';

    #[ConfigKey("Option to define the relay host used when email_transport is configured to 'smtp'. The used port must be provided here.")]
    #[ConfigKeyString('')]
    public const RELAYHOST_CONFIG_KEY = 'email_relayhost';

    public const EMAIL_TRANSPORT_SENDMAIL_VALUE = 'sendmail';
    public const EMAIL_TRANSPORT_SMTP_VALUE     = 'smtp';

    public static function buildMailTransport(LoggerInterface $logger): Mail\Transport\TransportInterface
    {
        if (ForgeConfig::get(self::TRANSPORT_CONFIG_KEY, self::EMAIL_TRANSPORT_SENDMAIL_VALUE) === self::EMAIL_TRANSPORT_SENDMAIL_VALUE) {
            return new Mail\Transport\Sendmail();
        } elseif (ForgeConfig::get(self::TRANSPORT_CONFIG_KEY) === self::EMAIL_TRANSPORT_SMTP_VALUE) {
            $relay_host_config = ForgeConfig::get(self::RELAYHOST_CONFIG_KEY, '');
            if ($relay_host_config === '') {
                return new NotConfiguredSmtpTransport($logger);
            }
            return new Mail\Transport\Smtp(
                SmtpOptionsBuilder::buildSmtpOptionFromForgeConfig($relay_host_config)
            );
        } else {
            return new InvalidDefinedTransport($logger);
        }
    }
}
