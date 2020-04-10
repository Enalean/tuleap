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
 */

declare(strict_types=1);

namespace TuleapCodingStandard\Tuleap\OAuth2Server\User;

use PHPUnit\Framework\TestCase;
use Tuleap\OAuth2Server\User\UserInfoResponseRepresentation;

final class UserInfoResponseRepresentationTest extends TestCase
{
    public function testBuildsRepresentationWithSubjectClaimOnly(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '{"sub":"110"}',
            json_encode(UserInfoResponseRepresentation::fromSubject('110'), JSON_THROW_ON_ERROR)
        );
    }

    public function testBuildsRepresentationWithEmailClaim(): void
    {
        $representation = UserInfoResponseRepresentation::fromSubject('110')
            ->withEmail('user@example.com', true);

        $this->assertJsonStringEqualsJsonString(
            '{"sub":"110","email":"user@example.com","email_verified":true}',
            json_encode($representation, JSON_THROW_ON_ERROR)
        );
    }
}
