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
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeSaveDocumentTokenDAOTest extends TestCase
{
    private OnlyOfficeSaveDocumentTokenDAO $dao;

    protected function setUp(): void
    {
        $this->dao = new OnlyOfficeSaveDocumentTokenDAO();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->run('DELETE FROM plugin_onlyoffice_save_document_token');
    }

    public function testCanProcessANonExpiredToken(): void
    {
        $current_time    = 10;
        $expiration_time = 20;

        $key_id = $this->dao->create(102, 11, 96, 'verification_string', $expiration_time);

        $row = $this->dao->searchTokenVerificationAndAssociatedData($key_id, $current_time);
        self::assertEqualsCanonicalizing(['verifier' => 'verification_string', 'user_id' => 102, 'document_id' => 11, 'version_id' => 96], $row);
    }

    public function testDoesNotFoundAnExpiredToken(): void
    {
        $current_time    = 20;
        $expiration_time = 10;

        $key_id = $this->dao->create(102, 11, 96, 'verification_string', $expiration_time);

        self::assertNull($this->dao->searchTokenVerificationAndAssociatedData($key_id, $current_time));
    }
}
