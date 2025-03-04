<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Token;

use Tuleap\Cryptography\ConcealedString;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class IntegrationApiTokenTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildAlreadyKnownToken(): void
    {
        $secret = new ConcealedString('Ny secret');

        $apparently_valid_token = IntegrationApiToken::buildAlreadyKnownToken($secret, false);
        self::assertEquals($secret, $apparently_valid_token->getToken());
        self::assertFalse($apparently_valid_token->isEmailAlreadySendForInvalidToken());

        $probably_expired_token = IntegrationApiToken::buildAlreadyKnownToken($secret, true);
        self::assertEquals($secret, $probably_expired_token->getToken());
        self::assertTrue($probably_expired_token->isEmailAlreadySendForInvalidToken());
    }

    public function testBuildBrandNewToken(): void
    {
        $secret = new ConcealedString('Ny secret');

        $brand_new_token = IntegrationApiToken::buildBrandNewToken($secret);
        self::assertEquals($secret, $brand_new_token->getToken());
        self::assertFalse($brand_new_token->isEmailAlreadySendForInvalidToken());
    }
}
