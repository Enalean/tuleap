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

namespace Tuleap\OAuth2Server\App;

use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;

final class LastGeneratedClientSecretStoreTest extends TestCase
{
    /**
     * @var LastGeneratedClientSecretStore
     */
    private $store;

    protected function setUp(): void
    {
        $storage = [];
        $this->store = new LastGeneratedClientSecretStore(
            new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
            new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES))),
            $storage
        );
    }

    public function testANewlyGeneratedClientSecretCanBeStoredAndRetrievedOnce(): void
    {
        $secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $this->store->storeLastGeneratedClientSecret(1, $secret);

        $last_client_secret = $this->store->getLastGeneratedClientSecret();
        $this->assertNotNull($last_client_secret);
        $this->assertEquals(1, $last_client_secret->getAppID());
        // Does not retrieve twice
        $this->assertNull($this->store->getLastGeneratedClientSecret());
    }

    public function testOnlyTheLastSecretIsRemembered(): void
    {
        $secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $second_secret = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $this->store->storeLastGeneratedClientSecret(2, $secret);
        $this->store->storeLastGeneratedClientSecret(3, $second_secret);

        $last_created_app = $this->store->getLastGeneratedClientSecret();
        $this->assertNotNull($last_created_app);
        $this->assertEquals(3, $last_created_app->getAppID());
    }
}
