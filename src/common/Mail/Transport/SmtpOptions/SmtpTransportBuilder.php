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

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tuleap\Mail\Transport\MailTransportBuilder;

class SmtpTransportBuilder
{
    private const PORT_SEPARATOR = ':';

    /**
     * @psalm-param non-empty-string $relay_host_config
     */
    public static function buildSmtpTransportFromForgeConfig(string $relay_host_config): EsmtpTransport
    {
        $url_parts = explode(self::PORT_SEPARATOR, $relay_host_config);
        $host      = $url_parts[0];
        $port      = 0;

        if (isset($url_parts[1]) && strlen($url_parts[1]) > 0) {
            $port = (int) $url_parts[1];
        }

        $implicit_tls = \ForgeConfig::getStringAsBool(MailTransportBuilder::RELAYHOST_SMTP_USE_IMPLICIT_TLS);

        $transport = new EsmtpTransport($host, $port, $implicit_tls);

        $auth_username = (string) \ForgeConfig::get(MailTransportBuilder::RELAYHOST_SMTP_USERNAME);
        if ($auth_username !== '' && \ForgeConfig::exists(MailTransportBuilder::RELAYHOST_SMTP_PASSWORD)) {
            $transport->setUsername($auth_username);
            $transport->setPassword(\ForgeConfig::getSecretAsClearText(MailTransportBuilder::RELAYHOST_SMTP_PASSWORD)->getString());
        }

        return $transport;
    }
}
