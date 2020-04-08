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

namespace TuleapCfg\Command\SetupMysql;

use ParagonIE\EasyDB\EasyDB;
use Symfony\Component\Console\Style\SymfonyStyle;

interface ConnectionManagerInterface
{
    public const SSL_NO_SSL     = 'disabled';
    public const SSL_NO_VERIFY  = 'no-verify';
    public const SSL_VERIFY_CA  = 'verify-ca';

    public const ALLOWED_SSL_MODES = [
        self::SSL_NO_SSL,
        self::SSL_NO_VERIFY,
        self::SSL_VERIFY_CA,
    ];

    /**
     * @psalm-param value-of<self::ALLOWED_SSL_MODES> $ssl_mode
     */
    public function getDBWithoutDBName(SymfonyStyle $io, string $host, int $port, string $ssl_mode, string $ssl_ca_file, string $user, string $password): ?EasyDB;

    public function checkSQLModes(EasyDB $db): void;
}
