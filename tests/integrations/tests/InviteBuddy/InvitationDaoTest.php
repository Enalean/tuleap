<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestCase;

class InvitationDaoTest extends TestCase
{
    private InvitationDao $dao;

    protected function setUp(): void
    {
        $this->dao = new InvitationDao(new SplitTokenVerificationStringHasher());
    }

    protected function tearDown(): void
    {
        DBFactory::getMainTuleapDBConnection()->getDB()->run("DELETE FROM invitations");
    }

    public function testSavesInvitationWithVerifier(): void
    {
        $verifier = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $id = $this->dao->create(1234567890, 101, "jdoe@example.com", null, null, 'creating', $verifier);

        $invitation = $this->dao->searchBySplitToken(new SplitToken($id, $verifier));
        self::assertEquals('jdoe@example.com', $invitation->to_email);
    }

    public function testExceptionWhenTokenCannotBeVerified(): void
    {
        $verifier = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $id = $this->dao->create(1234567890, 101, "jdoe@example.com", null, null, 'creating', $verifier);

        $invalid_verifier = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $this->expectException(InvalidInvitationTokenException::class);
        $this->dao->searchBySplitToken(new SplitToken($id, $invalid_verifier));
    }
}
