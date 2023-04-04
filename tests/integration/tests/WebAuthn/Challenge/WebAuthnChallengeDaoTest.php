<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace integration\tests\WebAuthn\Challenge;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\WebAuthn\Challenge\WebAuthnChallengeDao;

final class WebAuthnOptionsDaoTest extends TestCase
{
    private WebAuthnChallengeDao $dao;
    private EasyDB $db;

    public function setUp(): void
    {
        $this->dao = new WebAuthnChallengeDao();
        $this->db  = DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public static function tearDownAfterClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->run('DELETE FROM webauthn_challenge');
    }

    public function testItCanSave(): void
    {
        $challenge = random_bytes(32);
        $this->dao->saveChallenge(101, $challenge);

        $row = $this->db->row('SELECT challenge FROM webauthn_challenge WHERE user_id = ?', 101);
        self::assertIsArray($row);
        $retrieved = $row['challenge'];
        self::assertIsString($retrieved);
        self::assertSame($challenge, $retrieved);
    }

    public function testUserCanSaveOnlyOneChallenge(): void
    {
        $challenge  = random_bytes(32);
        $challenge2 = random_bytes(32);

        $this->dao->saveChallenge(104, $challenge);
        $this->dao->saveChallenge(104, $challenge2);

        $rows = $this->db->run('SELECT challenge FROM webauthn_challenge WHERE user_id = ?', 104);
        self::assertCount(1, $rows);
        self::assertSame($challenge2, $rows[0]['challenge']);
    }

    public function testExpiredChallengesAreRemovedWhenNewOneIsInserted(): void
    {
        $challenge  = random_bytes(32);
        $challenge2 = random_bytes(32);

        $this->dao->saveChallenge(105, $challenge);
        $this->db->update(
            'webauthn_challenge',
            ['expiration_date' => '1680700000'],
            ['user_id' => 105]
        );

        $this->dao->saveChallenge(106, $challenge2);

        $row = $this->db->row('SELECT challenge FROM webauthn_challenge WHERE user_id = ?', 105);
        self::assertNull($row);
    }
}
