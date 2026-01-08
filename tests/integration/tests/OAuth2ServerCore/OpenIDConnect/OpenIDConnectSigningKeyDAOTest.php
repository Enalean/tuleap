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

namespace Tuleap\OAuth2ServerCore\OpenIDConnect;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Stub\KeyFactoryStub;
use Tuleap\DB\DBFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OpenIDConnectSigningKeyDAOTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private OpenIDConnectSigningKeyDAO $dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao = new OpenIDConnectSigningKeyDAO(null, null, new KeyFactoryStub());
    }

    #[\Override]
    protected function tearDown(): void
    {
        DBFactory::getMainTuleapDBConnection()->getDB()->run('DELETE FROM oauth2_server_oidc_signing_key');
    }

    public function testCanSaveAndRetrieveKeys(): void
    {
        $current_time = 10;
        $this->assertEmpty($this->dao->searchPublicKeys());
        $this->assertNull($this->dao->searchMostRecentNonExpiredPrivateKey($current_time));
        $this->dao->save('public_key_1', new ConcealedString('private_key_1'), 100, 90);
        $this->dao->save('public_key_2', new ConcealedString('private_key_2'), 101, 90);
        $this->assertEqualsCanonicalizing(['public_key_1', 'public_key_2'], $this->dao->searchPublicKeys());
        $most_recent_non_expired_key = $this->dao->searchMostRecentNonExpiredPrivateKey($current_time);
        self::assertNotNull($most_recent_non_expired_key);
        self::assertEquals('public_key_2', $most_recent_non_expired_key['public_key']);
        self::assertTrue($most_recent_non_expired_key['private_key']->isIdenticalTo(new ConcealedString('private_key_2')));
    }

    public function testDoesNotRetrieveExpiredPrivateKeys(): void
    {
        $this->dao->save('public_key', new ConcealedString('private_key'), 100, 90);

        $time_in_the_future_where_all_keys_have_expired = 999;
        $this->assertNull($this->dao->searchMostRecentNonExpiredPrivateKey($time_in_the_future_where_all_keys_have_expired));
    }

    public function testCleanupOldKeysWhileSavingNewOne(): void
    {
        $this->dao->save('expired_public_key', new ConcealedString('expired_private_key'), 10, 5);
        $this->dao->save('public_key', new ConcealedString('private_key'), 100, 90);

        $this->assertEquals(['public_key'], $this->dao->searchPublicKeys());
    }
}
