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

namespace Tuleap\WebAuthn\Challenge;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class WebAuthnChallengeDaoTest extends TestIntegrationTestCase
{
    private WebAuthnChallengeDao $dao;
    private EasyDB $db;

    private const USER_ID_WITH_CHALLENGE_1  = 101;
    private const USER_ID_WITH_CHALLENGE_2  = 102;
    private const USER_ID_WITHOUT_CHALLENGE = 103;

    public function setUp(): void
    {
        $this->dao = new WebAuthnChallengeDao();
        $this->db  = DBFactory::getMainTuleapDBConnection()->getDB();
    }

    public function testItCanSaveThenRetrieve(): void
    {
        $challenge = random_bytes(32);
        $this->dao->saveChallenge(self::USER_ID_WITH_CHALLENGE_1, $challenge);

        $retrieved = $this->dao->searchChallenge(self::USER_ID_WITH_CHALLENGE_1);
        self::assertTrue($retrieved->isValue());
        self::assertSame($challenge, $retrieved->unwrapOr(null));
    }

    public function testUserCanSaveOnlyOneChallenge(): void
    {
        $challenge  = random_bytes(32);
        $challenge2 = random_bytes(32);

        $this->dao->saveChallenge(self::USER_ID_WITH_CHALLENGE_1, $challenge);
        $this->dao->saveChallenge(self::USER_ID_WITH_CHALLENGE_1, $challenge2);

        $rows = $this->db->run('SELECT challenge FROM webauthn_challenge WHERE user_id = ?', self::USER_ID_WITH_CHALLENGE_1);
        self::assertCount(1, $rows);
        self::assertSame($challenge2, $rows[0]['challenge']);
    }

    public function testExpiredChallengesAreRemovedWhenNewOneIsInserted(): void
    {
        $challenge  = random_bytes(32);
        $challenge2 = random_bytes(32);

        $this->dao->saveChallenge(self::USER_ID_WITH_CHALLENGE_1, $challenge);
        $this->db->update(
            'webauthn_challenge',
            ['expiration_date' => '1680700000'],
            ['user_id' => self::USER_ID_WITH_CHALLENGE_1]
        );

        $this->dao->saveChallenge(self::USER_ID_WITH_CHALLENGE_2, $challenge2);

        $row = $this->db->row('SELECT challenge FROM webauthn_challenge WHERE user_id = ?', self::USER_ID_WITH_CHALLENGE_1);
        self::assertNull($row);
    }

    public function testItCannotRetrieveNotSavedChallenge(): void
    {
        $retrieved = $this->dao->searchChallenge(self::USER_ID_WITHOUT_CHALLENGE);
        self::assertTrue($retrieved->isNothing());
    }

    public function testItCannotRetrieveExpiredChallenge(): void
    {
        $this->dao->saveChallenge(self::USER_ID_WITH_CHALLENGE_1, random_bytes(32));
        $this->db->update(
            'webauthn_challenge',
            ['expiration_date' => '1680700000'],
            ['user_id' => self::USER_ID_WITH_CHALLENGE_1]
        );

        $retrieved = $this->dao->searchChallenge(self::USER_ID_WITH_CHALLENGE_1);
        self::assertTrue($retrieved->isNothing());
    }

    public function testItCannotRetrieveSameChallengeTwice(): void
    {
        $this->dao->saveChallenge(self::USER_ID_WITH_CHALLENGE_1, random_bytes(32));

        $retrieved = $this->dao->searchChallenge(self::USER_ID_WITH_CHALLENGE_1);
        self::assertTrue($retrieved->isValue());

        $retrieved2 = $this->dao->searchChallenge(self::USER_ID_WITH_CHALLENGE_1);
        self::assertTrue($retrieved2->isNothing());
    }
}
