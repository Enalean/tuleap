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

namespace Tuleap\OAuth2Server\OpenIDConnect\IDToken;

use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBFactory;

final class OpenIDConnectSigningKeyDAOTest extends TestCase
{
    /**
     * @var OpenIDConnectSigningKeyDAO
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = new OpenIDConnectSigningKeyDAO();
    }

    protected function tearDown(): void
    {
        DBFactory::getMainTuleapDBConnection()->getDB()->run('DELETE FROM plugin_oauth2_oidc_signing_key');
    }

    public function testCanSaveAndRetrieveKey(): void
    {
        $this->assertNull($this->dao->searchPublicKey());
        $this->assertNull($this->dao->searchEncryptedPrivateKey());
        $this->dao->save('public_key', 'encrypted_private_key');
        $this->assertEquals('public_key', $this->dao->searchPublicKey());
        $this->assertEquals('encrypted_private_key', $this->dao->searchEncryptedPrivateKey());
    }

    public function testCanSaveKeyOnlyOnce(): void
    {
        $this->dao->save('public_key', 'encrypted_private_key');
        $this->expectException(\PDOException::class);
        $this->dao->save('public_key', 'encrypted_private_key');
    }
}
