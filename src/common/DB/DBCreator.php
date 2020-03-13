<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\DB;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use PDO;

class DBCreator
{
    /**
     * @var string
     */
    private $database_name;

    public function __construct(string $database_name)
    {
        $this->database_name = $database_name;
    }

    public function createDB(): EasyDB
    {
        return Factory::fromArray([
            $this->getDSN(),
            \ForgeConfig::get('sys_dbuser'),
            \ForgeConfig::get('sys_dbpasswd'),
            $this->getOptions(),
        ]);
    }

    private function getOptions(): array
    {
        $options = [];
        if (DBConfig::isSSLEnabled()) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = DBConfig::getSSLCACertFile();
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = DBConfig::isSSLVerifyCert();
        }
        return $options;
    }

    private function getDSN(): string
    {
        return 'mysql:host=' . \ForgeConfig::get('sys_dbhost') . ';dbname=' . $this->database_name;
    }
}
