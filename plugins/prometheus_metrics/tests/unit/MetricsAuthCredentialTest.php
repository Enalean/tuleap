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

namespace Tuleap\PrometheusMetrics;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Server\Authentication\LoginCredentialSet;

final class MetricsAuthCredentialTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testCredentialDoesNotMatchWhenNoneWereSet(): void
    {
        $basic_auth_credential = MetricsAuthCredential::noCredentialSet();
        self::assertFalse($basic_auth_credential->doesCredentialMatch('username', new ConcealedString('')));
    }

    public function testCredentialMatchesWhenUsernameAndPasswordAreCorrects(): void
    {
        $basic_auth_credential = MetricsAuthCredential::fromLoginCredentialSet(
            new LoginCredentialSet('username', new ConcealedString('password'))
        );
        self::assertTrue($basic_auth_credential->doesCredentialMatch('username', new ConcealedString('password')));
    }

    public function testCredentialDoesNotMatchWhenUsernameIsIncorrect(): void
    {
        $basic_auth_credential = MetricsAuthCredential::fromLoginCredentialSet(
            new LoginCredentialSet('wrong', new ConcealedString('password'))
        );
        self::assertFalse($basic_auth_credential->doesCredentialMatch('username', new ConcealedString('password')));
    }

    public function testCredentialDoesNotMatchWhenPasswordIsIncorrect(): void
    {
        $basic_auth_credential = MetricsAuthCredential::fromLoginCredentialSet(
            new LoginCredentialSet('username', new ConcealedString('wrong'))
        );
        self::assertFalse($basic_auth_credential->doesCredentialMatch('username', new ConcealedString('password')));
    }
}
