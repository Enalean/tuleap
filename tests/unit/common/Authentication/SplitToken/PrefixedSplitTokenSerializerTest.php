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

namespace Tuleap\Authentication\SplitToken;

use Tuleap\Cryptography\ConcealedString;

final class PrefixedSplitTokenSerializerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TEST_PREFIX = 'foo.prefix-';

    public function testIdentifierPrefixIsPresent(): void
    {
        self::assertStringStartsWith(self::TEST_PREFIX, $this->buildIdentifier()->getString());
    }

    public function testBuiltIdentifierIsInTheBase64CharsetSoItCanBeSafelyUsedInHTTPHeadersAndURLs(): void
    {
        self::assertMatchesRegularExpression('/(?:[a-zA-Z0-9]|-|\.|\_|\~|\+|\/|=)+/', $this->buildIdentifier()->getString());
    }

    private function buildIdentifier(): ConcealedString
    {
        $verification_string = $this->createMock(SplitTokenVerificationString::class);
        $verification_string->method('getString')->willReturn(new ConcealedString('random_string'));
        $access_key = new SplitToken(1, $verification_string);

        $serializer = new PrefixedSplitTokenSerializer($this->getPrefix());

        return $serializer->getIdentifier($access_key);
    }

    public function testCanBeBuiltFromAnIdentifier(): void
    {
        $expected_id             = 15;
        $hex_verification_string = '7f2c5f68b1c802c21486cf88a7d4209d9685b43b5f1661fb1528759c5387fd13';
        $identifier              = self::TEST_PREFIX . "$expected_id.$hex_verification_string";

        $serializer = new PrefixedSplitTokenSerializer($this->getPrefix());

        $access_key = $serializer->getSplitToken(new ConcealedString($identifier));

        self::assertSame($expected_id, $access_key->getID());
        self::assertSame($hex_verification_string, bin2hex((string) $access_key->getVerificationString()->getString()));
    }

    /**
     * @dataProvider incorrectlyFormattedIdentifierProvider
     */
    public function testBuildingFromAnIncorrectlyFormattedIdentifierIsRejected(string $incorrectly_formatted_identifier): void
    {
        $access_key_serializer = new PrefixedSplitTokenSerializer($this->getPrefix());

        $this->expectException(InvalidIdentifierFormatException::class);

        $access_key_serializer->getSplitToken(new ConcealedString($incorrectly_formatted_identifier));
    }

    public static function incorrectlyFormattedIdentifierProvider(): array
    {
        return [
            ['incorrect_identifier'],
            [self::TEST_PREFIX . '1.aaa'],
        ];
    }

    private function getPrefix(): PrefixSplitTokenForSerialization
    {
        return new class (self::TEST_PREFIX) implements PrefixSplitTokenForSerialization
        {
            /**
             * @var string
             */
            private $prefix;

            public function __construct(string $prefix)
            {
                $this->prefix = $prefix;
            }

            public function getString(): string
            {
                return $this->prefix;
            }
        };
    }
}
