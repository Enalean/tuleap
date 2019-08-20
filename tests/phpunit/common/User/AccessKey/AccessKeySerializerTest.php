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

namespace Tuleap\User\AccessKey;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;

final class AccessKeySerializerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testIdentifierPrefixIsPresent()
    {
        $access_key_verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $access_key_verification_string->shouldReceive('getString')->andReturns('random_string');
        $access_key = new SplitToken(1, $access_key_verification_string);

        $access_key_serializer = new AccessKeySerializer();

        $identifier = $access_key_serializer->getIdentifier($access_key);

        $this->assertStringStartsWith('tlp-k1-', $identifier->getString());
    }

    public function testCanBeBuiltFromAnIdentifier()
    {
        $expected_id                  = 15;
        $hex_verification_string      = '7f2c5f68b1c802c21486cf88a7d4209d9685b43b5f1661fb1528759c5387fd13';
        $identifier                   = "tlp-k1-$expected_id.$hex_verification_string";

        $access_key_serializer = new AccessKeySerializer();

        $access_key = $access_key_serializer->getSplitToken(new ConcealedString($identifier));

        $this->assertSame($expected_id, $access_key->getID());
        $this->assertSame($hex_verification_string, bin2hex($access_key->getVerificationString()->getString()));
    }

    /**
     * @dataProvider incorrectlyFormattedIdentifierProvider
     */
    public function testBuildingFromAnIncorrectlyFormattedIdentifierIsRejected(string $incorrectly_formatted_identifier) : void
    {
        $access_key_serializer = new AccessKeySerializer();

        $this->expectException(InvalidIdentifierFormatException::class);

        $access_key_serializer->getSplitToken(new ConcealedString($incorrectly_formatted_identifier));
    }

    public function incorrectlyFormattedIdentifierProvider() : array
    {
        return [
            ['incorrect_identifier'],
            ['tlp-k1-1.aaa'],
        ];
    }
}
