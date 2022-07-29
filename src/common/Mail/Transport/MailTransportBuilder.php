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

use ForgeConfig;
use Laminas\Mail;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyString;

class MailTransportBuilder
{
    #[ConfigKey("Option to define how Tuleap will send emails")]
    #[ConfigKeyString(self::EMAIL_TRANSPORT_SENDMAIL_VALUE)]
    public const CONFIG_KEY = 'email_transport';

    public const EMAIL_TRANSPORT_SENDMAIL_VALUE = 'sendmail';
    public const EMAIL_TRANSPORT_SMTP_VALUE     = 'smtp';

    public static function buildMailTransport(): Mail\Transport\TransportInterface
    {
        if (ForgeConfig::get(self::CONFIG_KEY) === self::EMAIL_TRANSPORT_SENDMAIL_VALUE) {
            return new Mail\Transport\Sendmail();
        } elseif (ForgeConfig::get(self::CONFIG_KEY) === self::EMAIL_TRANSPORT_SMTP_VALUE) {
            return new Mail\Transport\Smtp();
        } else {
            // We don't deal with invalid value yet. We fallback on the legacy Sendmail
            return new Mail\Transport\Sendmail();
        }
    }
}
