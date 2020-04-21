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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class LDAPTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @testWith ["ldaps://ldap1.example.com,ldaps://ldap2.example.com"]
     *           ["ldaps://ldap1.example.com;ldaps://ldap2.example.com"]
     */
    public function testCanTryToFailOverMultipleServersToConnect(string $sys_ldap_server): void
    {
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);

        $ldap = new \LDAP(
            ['server' => $sys_ldap_server],
            $logger
        );

        $logger->shouldReceive('warning')->with(\Mockery::pattern('# ldaps://ldap1.example.com #'));
        $logger->shouldReceive('warning')->with(\Mockery::pattern('# ldaps://ldap2.example.com #'));
        $logger->shouldReceive('warning')->with(\Mockery::pattern('#Cannot connect to any LDAP server#'));
        $logger->shouldReceive('error');

        $ldap->search('dc=example,dc=com', 'filter=something');
    }
}
