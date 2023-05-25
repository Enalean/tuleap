<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\Credential;

require_once __DIR__ . '/../bootstrap.php';

final class CredentialTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testCredentialExpiration(): void
    {
        $valid_credential   = new Credential('id1', new \DateTimeImmutable('+10 minutes'));
        $expired_credential = new Credential('id2', new \DateTimeImmutable('-10 minutes'));

        self::assertFalse($valid_credential->hasExpired());
        self::assertTrue($expired_credential->hasExpired());
    }
}
