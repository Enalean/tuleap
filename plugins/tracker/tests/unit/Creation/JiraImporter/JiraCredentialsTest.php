<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JiraCredentialsTest extends TestCase
{
    public function testHoldsValues(): void
    {
        $user   = 'username';
        $secret = new ConcealedString('secret');
        $url    = 'https://jira.example.com:8443';

        $credentials = new JiraCredentials($url, $user, $secret);

        self::assertSame($user, $credentials->getJiraUsername());
        self::assertSame($secret, $credentials->getJiraToken());
        self::assertSame($url, $credentials->getJiraUrl());
    }

    public function testTrimsAdditionalSlashesFromTheURL(): void
    {
        $credentials = new JiraCredentials('https://jira.example.com/', 'user', new ConcealedString('secret'));

        self::assertEquals('https://jira.example.com', $credentials->getJiraUrl());
    }
}
