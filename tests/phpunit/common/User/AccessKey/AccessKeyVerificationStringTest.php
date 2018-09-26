<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;

class AccessKeyVerificationStringTest extends TestCase
{
    /**
     * @expectedException \Tuleap\User\AccessKey\IncorrectSizeVerificationStringException
     */
    public function testIncorrectlySizedVerificationStringAreRejected()
    {
        new AccessKeyVerificationString(new ConcealedString('too_short'));
    }

    public function testGeneratedKeyIsValid()
    {
        $generated_access_key = AccessKeyVerificationString::generateNewAccessKeyVerificationString();
        $access_key           = new AccessKeyVerificationString($generated_access_key->getString());

        $this->assertSame($generated_access_key->getString(), $access_key->getString());
    }
}
