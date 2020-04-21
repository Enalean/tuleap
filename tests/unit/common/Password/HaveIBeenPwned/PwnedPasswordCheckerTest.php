<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Password\HaveIBeenPwned;

use PHPUnit\Framework\TestCase;

final class PwnedPasswordCheckerTest extends TestCase
{
    public const API_RESPONSE_EXAMPLE = <<<EOF
63DBC31449BE0453A859936BD1BC9957642:15
DD41FB439C6EEB61BBE84136C182CEA04FC:4
95CEB99C43E3EFD89FE8550E3C4355F1FED:9
936F6D904E3DB7EB90EBB14B6146CE40542:6
33C627D2B7625DFB8F489A643A348E98808:0
46DA232BA31579BB91ED45FC94FB344F125:10
BF009320A70F9353613B0550167C2E57EDE:5
EOF;

    /**
     * @dataProvider passwordProvider
     */
    public function testCompromisedPasswordIsRightlyIdentified(string $password, bool $expected): void
    {
        $retriever = $this->createMock(PwnedPasswordRangeRetriever::class);
        $retriever->method('getHashSuffixesMatchingPrefix')->willReturn(self::API_RESPONSE_EXAMPLE);

        $pwned_password_checker = new PwnedPasswordChecker($retriever);
        $this->assertEquals($expected, $pwned_password_checker->isPasswordCompromised($password));
    }

    public function passwordProvider(): array
    {
        return [
            ['not_compromised_password', false],
            ['compromised_password', true],
            ['almost_compromised_password', false],
            ['', false]
        ];
    }

    public function testAPICallEmptyResponseIsNotConsideredAsACompromisedPassword(): void
    {
        $retriever   = $this->createMock(PwnedPasswordRangeRetriever::class);
        $retriever->method('getHashSuffixesMatchingPrefix')->willReturn('');

        $pwned_password_checker = new PwnedPasswordChecker($retriever);
        $this->assertFalse($pwned_password_checker->isPasswordCompromised('password'));
    }
}
