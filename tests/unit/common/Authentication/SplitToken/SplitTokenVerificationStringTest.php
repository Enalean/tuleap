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

namespace Tuleap\Authentication\SplitToken;

use Tuleap\Cryptography\ConcealedString;

final class SplitTokenVerificationStringTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testIncorrectlySizedVerificationStringAreRejected()
    {
        $this->expectException(IncorrectSizeVerificationStringException::class);
        new SplitTokenVerificationString(new ConcealedString('too_short'));
    }

    public function testGeneratedKeyIsValid()
    {
        $generated_access_key = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $access_key           = new SplitTokenVerificationString($generated_access_key->getString());

        self::assertSame($generated_access_key->getString(), $access_key->getString());
    }
}
