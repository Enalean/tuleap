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

use Symfony\Component\Console\Style\SymfonyStyle;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

interface ConnectionManagerInterface
{
    public function getDBWithoutDBName(
        SymfonyStyle $io,
        string $host,
        int $port,
        bool $ssl_enabled,
        bool $verify_certificate,
        string $ssl_ca_file,
        string $user,
        string $password,
    ): DBWrapperInterface;

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function sanityCheck(DBWrapperInterface $db): Ok|Err;
}
