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

namespace Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class JenkinsTuleapPluginHookTokenDAOTest extends TestIntegrationTestCase
{
    private JenkinsTuleapPluginHookTokenDAO $dao;

    public function setUp(): void
    {
        $this->dao = new JenkinsTuleapPluginHookTokenDAO();
    }

    public function testCanProcessANonExpiredToken(): void
    {
        $current_time    = 10;
        $expiration_time = 20;

        $key_id = $this->dao->create('verification_string', $expiration_time);

        $row = $this->dao->searchTokenVerification($key_id, $current_time);
        self::assertEqualsCanonicalizing(['verifier' => 'verification_string'], $row);

        $this->dao->deleteTokenByID($key_id);

        self::assertNull($this->dao->searchTokenVerification($key_id, $current_time));
    }

    public function testDoesNotFoundAnExpiredToken(): void
    {
        $current_time    = 20;
        $expiration_time = 10;

        $key_id = $this->dao->create('verification_string', $expiration_time);

        self::assertNull($this->dao->searchTokenVerification($key_id, $current_time));
    }
}
