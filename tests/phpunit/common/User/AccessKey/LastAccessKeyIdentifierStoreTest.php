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

declare(strict_types=1);

namespace Tuleap\User\AccessKey;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;

class LastAccessKeyIdentifierStoreTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|EncryptionKey
     */
    private $encryption_key;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenFormatter
     */
    private $access_key_formatter;

    protected function setUp(): void
    {
        $this->encryption_key = \Mockery::mock(EncryptionKey::class);
        $this->encryption_key->shouldReceive('getRawKeyMaterial')->andReturns(
            str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );
        $this->access_key_formatter = \Mockery::mock(SplitTokenFormatter::class);
    }

    public function testAnAccessKeyIdentifierAndCanBeStoredAndRetrieved(): void
    {
        $storage               = [];
        $last_access_key_store = new LastAccessKeyIdentifierStore($this->access_key_formatter, $this->encryption_key, $storage);

        $this->access_key_formatter->shouldReceive('getIdentifier')->andReturns(new ConcealedString('identifier_value'));

        $last_access_key_store->storeLastGeneratedAccessKeyIdentifier(\Mockery::mock(SplitToken::class));
        $this->assertCount(1, $storage);
        $identifier = $last_access_key_store->getLastGeneratedAccessKeyIdentifier();
        $this->assertSame('identifier_value', $identifier->getString());
        $this->assertCount(0, $storage);
    }

    public function testOnlyTheLastAccessKeyIsStored(): void
    {
        $storage               = [];
        $last_access_key_store = new LastAccessKeyIdentifierStore($this->access_key_formatter, $this->encryption_key, $storage);

        $access_key1 = \Mockery::mock(SplitToken::class);
        $this->access_key_formatter->shouldReceive('getIdentifier')->with($access_key1)->andReturns(new ConcealedString('identifier_value1'));
        $access_key2 = \Mockery::mock(SplitToken::class);
        $this->access_key_formatter->shouldReceive('getIdentifier')->with($access_key2)->andReturns(new ConcealedString('identifier_value2'));

        $last_access_key_store->storeLastGeneratedAccessKeyIdentifier($access_key1);
        $this->assertCount(1, $storage);
        $last_access_key_store->storeLastGeneratedAccessKeyIdentifier($access_key2);
        $this->assertCount(1, $storage);

        $identifier = $last_access_key_store->getLastGeneratedAccessKeyIdentifier();
        $this->assertSame('identifier_value2', $identifier->getString());
    }

    public function testNullIsGivenWhenNoAccessKeyIdentifierIsStored(): void
    {
        $storage               = [];
        $last_access_key_store = new LastAccessKeyIdentifierStore($this->access_key_formatter, $this->encryption_key, $storage);

        $this->assertNull($last_access_key_store->getLastGeneratedAccessKeyIdentifier());
    }
}
