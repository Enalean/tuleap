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
use PDO;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

class ConnectionManager implements ConnectionManagerInterface
{
    private const MAX_DB_WAIT_LOOPS = 60;

    private const AUTHORISED_SQL_MODES = [
        'NO_AUTO_CREATE_USER' => true,
        'NO_ENGINE_SUBSTITUTION' => true,
        'ONLY_FULL_GROUP_BY' => true,
    ];

    private const string SUPPORTED_MYSQL80_VERSION_PREFIX = '8.0.';
    private const string SUPPORTED_MYSQL84_VERSION_PREFIX = '8.4.';

    #[\Override]
    public function getDBWithoutDBName(
        SymfonyStyle $io,
        string $host,
        int $port,
        bool $ssl_enabled,
        bool $verify_certificate,
        string $ssl_ca_file,
        string $user,
        string $password,
    ): DBWrapperInterface {
        $easydb = $this->loopToConnect(
            $io,
            [
                sprintf('mysql:host=%s;port=%d', $host, $port),
                $user,
                $password,
                $this->getOptions($ssl_enabled, $verify_certificate, $ssl_ca_file),
            ]
        );
        if ($easydb === null) {
            throw new \RuntimeException('Cannot connect to database');
        }
        return new EasyDBWrapper($easydb);
    }

    public function getDBWithDBName(SymfonyStyle $io, string $host, int $port, bool $ssl_enabled, bool $verify_certificate, string $ssl_ca_file, string $user, string $password, string $dbname): DBWrapperInterface
    {
        $easydb = $this->loopToConnect(
            $io,
            [
                sprintf('mysql:host=%s;port=%d;dbname=%s', $host, $port, $dbname),
                $user,
                $password,
                $this->getOptions($ssl_enabled, $verify_certificate, $ssl_ca_file),
            ]
        );
        if ($easydb === null) {
            throw new \RuntimeException('Cannot connect to database');
        }
        return new EasyDBWrapper($easydb);
    }

    private function getOptions(bool $ssl_enabled, bool $verify_certificate, string $ssl_ca_file): array
    {
        if (! $ssl_enabled) {
            return [];
        }
        return [
            PDO::MYSQL_ATTR_SSL_CA => $ssl_ca_file,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => $verify_certificate,
        ];
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
                $real_exception = $exception->getRealException();
                if ($real_exception !== null) {
                    $io->getErrorStyle()->writeln($real_exception->getMessage());
                } else {
                    $io->getErrorStyle()->writeln('Could not contact the DB');
                }
                $result = 0;
                $i++;
                sleep(1);
            }
        } while ($result !== 1 && $i < self::MAX_DB_WAIT_LOOPS);
        return null;
    }

    private function checkSQLModes(DBWrapperInterface $db): Ok|Err
    {
        $row    = $db->row('SHOW VARIABLES LIKE \'sql_mode\'');
        $errors = [];
        foreach (explode(',', $row['Value']) as $sql_mode) {
            if (! isset(self::AUTHORISED_SQL_MODES[$sql_mode])) {
                $errors[] = $sql_mode;
            }
        }
        if (count($errors) > 0) {
            return Result::err(Fault::fromMessage(sprintf('Following SQL modes not supported: %s, remove them from MySQL server configuration (sql_mode)', implode(', ', $errors))));
        }
        return Result::ok(null);
    }

    private function checkVersion(DBWrapperInterface $db): Ok|Err
    {
        $mysql_version = $db->row('SHOW VARIABLES LIKE \'version\'')['Value'];
        if (! str_starts_with($mysql_version, self::SUPPORTED_MYSQL80_VERSION_PREFIX) && ! str_starts_with($mysql_version, self::SUPPORTED_MYSQL84_VERSION_PREFIX)) {
            $mysql_version_comment = $db->row('SHOW VARIABLES LIKE \'version_comment\'')['Value'] ?? '';
            return Result::err(Fault::fromMessage(sprintf('Tuleap only support MySQL 8.0.x and 8.4.x. Found: version %s - %s', $mysql_version_comment, $mysql_version)));
        }
        return Result::ok(null);
    }

    #[\Override]
    public function sanityCheck(DBWrapperInterface $db): Ok|Err
    {
        return $this->checkVersion($db)
            ->andThen(fn () => $this->checkSQLModes($db));
    }
}
