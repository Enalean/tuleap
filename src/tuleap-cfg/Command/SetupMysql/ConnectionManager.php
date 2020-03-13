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
use ParagonIE\EasyDB\Exception\ConstructorFailed;
use ParagonIE\EasyDB\Factory;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConnectionManager
{
    private const MAX_DB_WAIT_LOOPS = 60;

    private const AUTHORISED_SQL_MODES = [
        'NO_AUTO_CREATE_USER' => true,
        'NO_ENGINE_SUBSTITUTION' => true,
    ];

    public function getDBWithoutDBName(SymfonyStyle $io, string $host, string $user, string $password): ?EasyDB
    {
        return $this->loopToConnect(
            $io,
            [
                'mysql:host=' . $host,
                $user,
                $password
            ]
        );
    }

    public function getDBWithDBName(SymfonyStyle $io, string $host, string $user, string $password, string $dbname): ?EasyDB
    {
        return $this->loopToConnect(
            $io,
            [
                'mysql:host=' . $host . ';dbname=' . $dbname,
                $user,
                $password
            ]
        );
    }

    private function loopToConnect(SymfonyStyle $io, array $easy_db): ?EasyDB
    {
        $i = 0;
        do {
            try {
                $db = Factory::fromArray($easy_db);
                $db->single('SELECT 1');
                return $db;
            } catch (ConstructorFailed $exception) {
                $io->getErrorStyle()->writeln($exception->getRealException()->getMessage());
                $result = 0;
                $i++;
                sleep(1);
            }
        } while ($result !== 1 && $i < self::MAX_DB_WAIT_LOOPS);
        return null;
    }

    public function checkSQLModes(EasyDB $db)
    {
        $row = $db->row('SHOW VARIABLES LIKE \'sql_mode\'');
        $errors = [];
        foreach (explode(',', $row['Value']) as $sql_mode) {
            if (! isset(self::AUTHORISED_SQL_MODES[$sql_mode])) {
                $errors[] = $sql_mode;
            }
        }
        if (count($errors) > 0) {
            throw new \RuntimeException(sprintf('Invalid SQL modes: %s, check MySQL server configuration', implode(', ', $errors)));
        }
    }
}
