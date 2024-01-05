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

use Laminas\Mail\Transport\SmtpOptions;
use Tuleap\Mail\Transport\MailTransportBuilder;

class SmtpOptionsBuilder
{
    private const PORT_SEPARATOR = ":";

    /**
     * @psalm-param non-empty-string $relay_host_config
     */
    public static function buildSmtpOptionFromForgeConfig(string $relay_host_config): SmtpOptions
    {
        $smtp_options = new SmtpOptions();
        $url_parts    = explode(self::PORT_SEPARATOR, $relay_host_config);
        $smtp_options->setHost($url_parts[0]);

        if (isset($url_parts[1]) && strlen($url_parts[1]) > 0) {
            $smtp_options->setPort((int) $url_parts[1]);
        }

        $connection_config = [];

        $auth_username = (string) \ForgeConfig::get(MailTransportBuilder::RELAYHOST_SMTP_USERNAME);
        if ($auth_username !== '' && \ForgeConfig::exists(MailTransportBuilder::RELAYHOST_SMTP_PASSWORD)) {
            $smtp_options->setConnectionClass(\ForgeConfig::get(MailTransportBuilder::RELAYHOST_SMTP_AUTH_TYPE));
            $connection_config = [
                'username' => $auth_username,
                'password' => \ForgeConfig::getSecretAsClearText(MailTransportBuilder::RELAYHOST_SMTP_PASSWORD)->getString(),
            ];
        }

        if (\ForgeConfig::getStringAsBool(MailTransportBuilder::RELAYHOST_SMTP_USE_TLS)) {
            $connection_config['ssl'] = 'tls';
        }

        if (count($connection_config) > 0) {
            $smtp_options->setConnectionConfig($connection_config);
        }

        return $smtp_options;
    }
}
