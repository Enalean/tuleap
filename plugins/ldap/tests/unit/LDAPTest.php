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

namespace Tuleap\LDAP;

use ColinODell\PsrTestLogger\TestLogger;

final class LDAPTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @testWith ["ldaps://ldap1.example.com,ldaps://ldap2.example.com"]
     *           ["ldaps://ldap1.example.com;ldaps://ldap2.example.com"]
     */
    public function testCanTryToFailOverMultipleServersToConnect(string $sys_ldap_server): void
    {
        $logger = new TestLogger();

        $ldap = new \LDAP(
            ['server' => $sys_ldap_server],
            $logger
        );

        $ldap->search('dc=example,dc=com', 'filter=something');

        self::assertTrue($logger->hasWarningThatContains('ldaps://ldap1.example.com'));
        self::assertTrue($logger->hasWarningThatContains('ldaps://ldap2.example.com'));
        self::assertTrue($logger->hasWarningThatContains('Cannot connect to any LDAP server'));
        self::assertTrue($logger->hasErrorRecords());
    }
}
