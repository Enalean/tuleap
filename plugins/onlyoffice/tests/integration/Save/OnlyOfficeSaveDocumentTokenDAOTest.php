<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Save;

use Tuleap\DB\DBFactory;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeSaveDocumentTokenDAOTest extends TestIntegrationTestCase
{
    private OnlyOfficeSaveDocumentTokenDAO $dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao = new OnlyOfficeSaveDocumentTokenDAO();
    }

    public function testCanProcessANonExpiredToken(): void
    {
        $current_time    = 10;
        $expiration_time = 20;

        $server_id = new UUIDTestContext();
        $key_id    = $this->dao->create(102, 11, 'verification_string', $expiration_time, $server_id);

        $row = $this->dao->searchTokenVerificationAndAssociatedData($key_id, $current_time);
        self::assertNotNull($row);
        $row_server_id = $row['server_id'];
        unset($row['server_id']);
        self::assertEqualsCanonicalizing(
            ['verifier' => 'verification_string', 'user_id' => 102, 'document_id' => 11],
            $row
        );
        self::assertEquals($server_id->toString(), $row_server_id->toString());
    }

    public function testDoesNotFoundAnExpiredToken(): void
    {
        $current_time    = 20;
        $expiration_time = 10;

        $key_id = $this->dao->create(102, 11, 'verification_string', $expiration_time, new UUIDTestContext());

        self::assertNull($this->dao->searchTokenVerificationAndAssociatedData($key_id, $current_time));
    }

    public function testCanUpdateExpirationTimeOfTokens(): void
    {
        $server_id = new UUIDTestContext();

        $key_id_1 = $this->dao->create(102, 11, 'verification_string', 10, $server_id);
        $key_id_2 = $this->dao->create(103, 11, 'verification_string', 10, $server_id);
        self::assertEquals(10, $this->getExpirationDateOfAToken($key_id_1));
        self::assertEquals(10, $this->getExpirationDateOfAToken($key_id_2));

        $this->dao->updateTokensExpirationDate(11, $server_id, 1, 20);
        self::assertEquals(20, $this->getExpirationDateOfAToken($key_id_1));
        self::assertEquals(20, $this->getExpirationDateOfAToken($key_id_2));

        $this->dao->updateTokensExpirationDate(11, $server_id, 15, 10);
        self::assertFalse($this->getExpirationDateOfAToken($key_id_1));
        self::assertFalse($this->getExpirationDateOfAToken($key_id_2));
    }

    private function getExpirationDateOfAToken(int $key_id): int|false
    {
        return DBFactory::getMainTuleapDBConnection()->getDB()->cell(
            'SELECT expiration_date FROM plugin_onlyoffice_save_document_token WHERE id=?',
            $key_id
        );
    }
}
