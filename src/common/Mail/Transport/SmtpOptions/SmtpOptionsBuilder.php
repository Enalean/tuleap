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

class SmtpOptionsBuilder
{
    private const PORT_SEPARATOR = ":";

    /**
     * @psalm-param non-empty-string $relay_host_config
     */
    public static function buildSmtpOptionFromForgeConfig(string $relay_host_config): SmtpOptions
    {
        $url_parts = explode(self::PORT_SEPARATOR, $relay_host_config);
        $options   = [
            'host' => $url_parts[0],
        ];

        if (isset($url_parts[1]) && strlen($url_parts[1]) > 0) {
            $options['port'] = $url_parts[1];
        }

        return new SmtpOptions($options);
    }
}
