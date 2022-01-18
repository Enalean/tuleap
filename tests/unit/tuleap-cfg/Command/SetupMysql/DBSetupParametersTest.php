<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use function PHPUnit\Framework\assertEquals;

final class DBSetupParametersTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testNoHostThrowAnException(): void
    {
        $this->expectException(MissingMandatoryParameterException::class);
        DBSetupParameters::fromForgeConfig('root', 'foo', new ConcealedString('bar'), 'tuleap.example.com');
    }

    public function testNoTuleapMySQLUserPasswordThrowAnException(): void
    {
        $this->expectException(MissingMandatoryParameterException::class);
        \ForgeConfig::set(DBConfig::CONF_HOST, 'db');
        DBSetupParameters::fromForgeConfig('root', 'foo', new ConcealedString('bar'), 'tuleap.example.com');
    }

    public function getTests(): iterable
    {
        yield 'no dbname defined falls back to `tuleap`' => [
            'parameters' => function () {
                \ForgeConfig::set(DBConfig::CONF_HOST, 'db');
                \ForgeConfig::set(DBConfig::CONF_DBPASSWORD, 'welcome0');
                return DBSetupParameters::fromForgeConfig('root', 'foo', new ConcealedString('bar'), 'tuleap.example.com');
            },
            'tests' => function (DBSetupParameters $params) {
                assertEquals('tuleap', $params->dbname);
            },
        ];

        yield 'no tuleap_username defined falls back to `tuleapadm`' => [
            'parameters' => function () {
                \ForgeConfig::set(DBConfig::CONF_HOST, 'db');
                \ForgeConfig::set(DBConfig::CONF_DBPASSWORD, 'welcome0');
                return DBSetupParameters::fromForgeConfig('root', 'foo', new ConcealedString('bar'), 'tuleap.example.com');
            },
            'tests' => function (DBSetupParameters $params) {
                assertEquals('tuleapadm', $params->tuleap_user);
            },
        ];

        yield 'no port defined falls back to default' => [
            'parameters' => function () {
                \ForgeConfig::set(DBConfig::CONF_HOST, 'db');
                \ForgeConfig::set(DBConfig::CONF_DBPASSWORD, 'welcome0');
                return DBSetupParameters::fromForgeConfig('root', 'foo', new ConcealedString('bar'), 'tuleap.example.com');
            },
            'tests' => function (DBSetupParameters $params) {
                assertEquals(DBConfig::DEFAULT_MYSQL_PORT, $params->port);
            },
        ];
    }

    /**
     * @dataProvider getTests
     */
    public function testSetup(callable $parameters, callable $tests): void
    {
        $tests($parameters());
    }
}
