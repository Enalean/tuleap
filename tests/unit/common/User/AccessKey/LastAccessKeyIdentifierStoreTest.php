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

declare(strict_types=1);

namespace Tuleap\User\AccessKey;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;

final class LastAccessKeyIdentifierStoreTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private EncryptionKey $encryption_key;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenFormatter
     */
    private $access_key_formatter;

    protected function setUp(): void
    {
        $this->encryption_key = new EncryptionKey(
            new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES))
        );

        $this->access_key_formatter = $this->createMock(SplitTokenFormatter::class);
    }

    public function testAnAccessKeyIdentifierAndCanBeStoredAndRetrieved(): void
    {
        $storage               = [];
        $last_access_key_store = new LastAccessKeyIdentifierStore($this->access_key_formatter, $this->encryption_key, $storage);

        $this->access_key_formatter->method('getIdentifier')->willReturn(new ConcealedString('identifier_value'));

        $last_access_key_store->storeLastGeneratedAccessKeyIdentifier($this->createMock(SplitToken::class));
        self::assertCount(1, $storage);
        $identifier = $last_access_key_store->getLastGeneratedAccessKeyIdentifier();
        self::assertSame('identifier_value', $identifier->getString());
        self::assertCount(0, $storage);
    }

    public function testOnlyTheLastAccessKeyIsStored(): void
    {
        $storage               = [];
        $last_access_key_store = new LastAccessKeyIdentifierStore($this->access_key_formatter, $this->encryption_key, $storage);

        $access_key1 = $this->createMock(SplitToken::class);
        $access_key2 = $this->createMock(SplitToken::class);
        $this->access_key_formatter->method('getIdentifier')->willReturnMap([
            [$access_key1, new ConcealedString('identifier_value1')],
            [$access_key2, new ConcealedString('identifier_value2')],
        ]);

        $last_access_key_store->storeLastGeneratedAccessKeyIdentifier($access_key1);
        self::assertCount(1, $storage);
        $last_access_key_store->storeLastGeneratedAccessKeyIdentifier($access_key2);
        self::assertCount(1, $storage);

        $identifier = $last_access_key_store->getLastGeneratedAccessKeyIdentifier();
        self::assertSame('identifier_value2', $identifier->getString());
    }

    public function testNullIsGivenWhenNoAccessKeyIdentifierIsStored(): void
    {
        $storage               = [];
        $last_access_key_store = new LastAccessKeyIdentifierStore($this->access_key_formatter, $this->encryption_key, $storage);

        self::assertNull($last_access_key_store->getLastGeneratedAccessKeyIdentifier());
    }
}
