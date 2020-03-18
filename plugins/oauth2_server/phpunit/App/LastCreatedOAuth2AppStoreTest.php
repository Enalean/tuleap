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
use Project;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;

final class LastCreatedOAuth2AppStoreTest extends TestCase
{
    /**
     * @var LastCreatedOAuth2AppStore
     */
    private $store;

    protected function setUp(): void
    {
        $storage = [];
        $this->store = new LastCreatedOAuth2AppStore(
            new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
            new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES))),
            $storage
        );
    }

    public function testAnNewlyCreatedAppCanStoredAndRetrievedOnce(): void
    {
        $this->store->storeLastCreatedApp(1, $this->buildNewOAuth2App());

        $last_created_app = $this->store->getLastCreatedApp();
        $this->assertNotNull($last_created_app);
        $this->assertEquals(1, $last_created_app->getAppID());
        $this->assertNull($this->store->getLastCreatedApp());
    }

    public function testOnlyTheMostCreatedAppIsRemembered(): void
    {
        $this->store->storeLastCreatedApp(2, $this->buildNewOAuth2App());
        $this->store->storeLastCreatedApp(3, $this->buildNewOAuth2App());

        $last_created_app = $this->store->getLastCreatedApp();
        $this->assertNotNull($last_created_app);
        $this->assertEquals(3, $last_created_app->getAppID());
    }

    private function buildNewOAuth2App(): NewOAuth2App
    {
        return NewOAuth2App::fromAppData(
            'name',
            'https://example.com/redirect',
            true,
            new Project(['group_id' => 102]),
            new SplitTokenVerificationStringHasher()
        );
    }
}
