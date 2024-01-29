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
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class OnlyOfficeSaveDocumentTokenDAOTest extends TestIntegrationTestCase
{
    private OnlyOfficeSaveDocumentTokenDAO $dao;

    protected function setUp(): void
    {
        $this->dao = new OnlyOfficeSaveDocumentTokenDAO();
    }

    public function testCanProcessANonExpiredToken(): void
    {
        $current_time    = 10;
        $expiration_time = 20;

        $key_id = $this->dao->create(102, 11, 'verification_string', $expiration_time, 1);

        $row = $this->dao->searchTokenVerificationAndAssociatedData($key_id, $current_time);
        self::assertEqualsCanonicalizing(
            ['verifier' => 'verification_string', 'user_id' => 102, 'document_id' => 11, 'server_id' => 1],
            $row
        );
    }

    public function testDoesNotFoundAnExpiredToken(): void
    {
        $current_time    = 20;
        $expiration_time = 10;

        $key_id = $this->dao->create(102, 11, 'verification_string', $expiration_time, 1);

        self::assertNull($this->dao->searchTokenVerificationAndAssociatedData($key_id, $current_time));
    }

    public function testCanUpdateExpirationTimeOfTokens(): void
    {
        $key_id_1 = $this->dao->create(102, 11, 'verification_string', 10, 1);
        $key_id_2 = $this->dao->create(103, 11, 'verification_string', 10, 1);
        self::assertEquals(10, $this->getExpirationDateOfAToken($key_id_1));
        self::assertEquals(10, $this->getExpirationDateOfAToken($key_id_2));

        $this->dao->updateTokensExpirationDate(11, 1, 1, 20);
        self::assertEquals(20, $this->getExpirationDateOfAToken($key_id_1));
        self::assertEquals(20, $this->getExpirationDateOfAToken($key_id_2));

        $this->dao->updateTokensExpirationDate(11, 1, 15, 10);
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
