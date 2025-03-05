<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\Account\Register\InvitationShouldBeToEmailException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class InvitationToEmailTest extends TestCase
{
    public function testItReturnsAnInvitationToEmailObject(): void
    {
        self::assertEquals(
            'jdoe@example.com',
            InvitationToEmail::fromInvitation(
                InvitationTestBuilder::aSentInvitation(1)
                    ->to('jdoe@example.com')
                    ->build(),
                new ConcealedString('secret')
            )->to_email,
        );
    }

    public function testEnsureThatInvitationHasBeenMadeForANewUser(): void
    {
        $this->expectException(InvitationShouldBeToEmailException::class);

        InvitationToEmail::fromInvitation(
            InvitationTestBuilder::aSentInvitation(1)
                ->to(102)
                ->build(),
            new ConcealedString('secret')
        );
    }
}
