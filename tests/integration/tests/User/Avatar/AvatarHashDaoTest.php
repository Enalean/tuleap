<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\User\Avatar;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AvatarHashDaoTest extends TestIntegrationTestCase
{
    public function testStorage(): void
    {
        $first_hash  = '2a8caf150a698d576f72fa06b5e15e27c2a9323394197c7d51657d4865b62533';
        $second_hash = '47c5980ff911e17a2e60e068e79fbfc52c7f36e518a38a37e4dfc69650138bd7';

        $alice = UserTestBuilder::aUser()->withId(101)->build();
        $bob   = UserTestBuilder::aUser()->withId(102)->build();

        $dao = new AvatarHashDao();

        $dao->store($alice, $first_hash);
        $dao->store($alice, $second_hash);

        self::assertSame($second_hash, $dao->retrieve($alice)->unwrapOr(null));
        self::assertSame(null, $dao->retrieve($bob)->unwrapOr(null));

        $dao->delete($alice);

        self::assertSame(null, $dao->retrieve($alice)->unwrapOr(null));
        self::assertSame(null, $dao->retrieve($bob)->unwrapOr(null));
    }
}
