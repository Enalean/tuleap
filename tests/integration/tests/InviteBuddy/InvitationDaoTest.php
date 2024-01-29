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
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

class InvitationDaoTest extends TestIntegrationTestCase
{
    private const CREATED_ON_TIMESTAMP = 1234567890;

    private InvitationDao $dao;
    private \PHPUnit\Framework\MockObject\MockObject&InvitationInstrumentation $instrumentation;

    protected function setUp(): void
    {
        $this->instrumentation = $this->createMock(InvitationInstrumentation::class);
        $this->dao             = new InvitationDao(
            new SplitTokenVerificationStringHasher(),
            $this->instrumentation,
        );
    }

    public function testSavesInvitationWithVerifier(): void
    {
        $verifier = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $id = $this->dao->create(self::CREATED_ON_TIMESTAMP, 101, "jdoe@example.com", null, null, null, $verifier);

        $invitation = $this->dao->searchBySplitToken(new SplitToken($id, $verifier));
        self::assertEquals($id, $invitation->id);
        self::assertEquals(101, $invitation->from_user_id);
        self::assertEquals('jdoe@example.com', $invitation->to_email);

        $same_invitation = $this->dao->searchById($invitation->id);
        self::assertEquals($same_invitation, $invitation);
    }

    public function testDoNotStoreEmailWhenWeTargetAnExistingUserToNotDuplicatePII(): void
    {
        $verifier = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $this->dao->create(self::CREATED_ON_TIMESTAMP, 101, "alice@example.com", 102, null, null, $verifier);
        $this->dao->create(self::CREATED_ON_TIMESTAMP, 101, "bob@example.com", null, null, null, $verifier);

        self::assertEquals(
            ['', 'bob@example.com'],
            $this->getStoredEmails(),
        );
    }

    public function testExceptionWhenTokenCannotBeVerified(): void
    {
        $verifier = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $id = $this->dao->create(self::CREATED_ON_TIMESTAMP, 101, "jdoe@example.com", null, null, null, $verifier);

        $invalid_verifier = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $this->expectException(InvalidInvitationTokenException::class);
        $this->dao->searchBySplitToken(new SplitToken($id, $invalid_verifier));
    }

    public function testExceptionWhenInvitationCannotBeFound(): void
    {
        $verifier = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $unknown_invitation_id = -1;

        $this->expectException(InvitationNotFoundException::class);

        $this->dao->searchBySplitToken(new SplitToken($unknown_invitation_id, $verifier));
    }

    public function testExceptionWhenInvitationCannotBeFoundById(): void
    {
        $unknown_invitation_id = -1;

        $this->expectException(InvitationNotFoundException::class);

        $this->dao->searchById($unknown_invitation_id);
    }

    public function testSaveJustCreatedUserThanksToInvitationWhenNoSpecificInvitationIsUsed(): void
    {
        $this->createBunchOfInvitations();

        self::assertFalse($this->dao->hasUsedAnInvitationToRegister(201));

        $this->dao->saveJustCreatedUserThanksToInvitation('alice@example.com', 201, null);

        self::assertFalse($this->dao->hasUsedAnInvitationToRegister(201));

        self::assertEquals(
            [201, null, 201],
            DBFactory::getMainTuleapDBConnection()->getDB()->column("SELECT created_user_id FROM invitations ORDER BY id"),
        );
        self::assertEquals(
            [Invitation::STATUS_COMPLETED, Invitation::STATUS_SENT, Invitation::STATUS_COMPLETED],
            DBFactory::getMainTuleapDBConnection()->getDB()->column("SELECT status FROM invitations ORDER BY id"),
        );
    }

    public function testSaveJustCreatedUserThanksToInvitationWhenAGivenInvitationIsUsed(): void
    {
        [, , $second_invitation_to_alice_id] = $this->createBunchOfInvitations();

        self::assertFalse($this->dao->hasUsedAnInvitationToRegister(201));

        $this->dao->saveJustCreatedUserThanksToInvitation('alice@example.com', 201, $second_invitation_to_alice_id);

        self::assertTrue($this->dao->hasUsedAnInvitationToRegister(201));

        self::assertEquals(
            [201, null, 201],
            DBFactory::getMainTuleapDBConnection()->getDB()->column("SELECT created_user_id FROM invitations ORDER BY id"),
        );
        self::assertEquals(
            [Invitation::STATUS_COMPLETED, Invitation::STATUS_SENT, Invitation::STATUS_USED],
            DBFactory::getMainTuleapDBConnection()->getDB()->column("SELECT status FROM invitations ORDER BY id"),
        );
        self::assertEquals(
            [101, 103],
            array_map(
                static fn (Invitation $invitation): int => $invitation->from_user_id,
                $this->dao->searchByCreatedUserId(201),
            ),
        );
    }

    public function testEmailAndVerifierShouldBeClearedAsSoonAsTheInvitationIsNotAnymoreInSentStatusSoThatWeDontKeepOrDuplicatePersonalyIdentifiableInformationEverywhere(): void
    {
        [
            $first_invitation_to_alice_id,
            $first_invitation_to_bob_id,
            $second_invitation_to_alice_id,
        ] = $this->createBunchOfInvitations();
        self::assertEquals(
            ["alice@example.com", "bob@example.com", "alice@example.com"],
            $this->getStoredEmails(),
        );
        $verifiers = $this->getStoredVerifiers();
        self::assertNotEquals('', $verifiers[0]);
        self::assertNotEquals('', $verifiers[1]);
        self::assertNotEquals('', $verifiers[2]);

        $this->dao->markAsError($first_invitation_to_bob_id);
        self::assertEquals(
            ["alice@example.com", "", "alice@example.com"],
            $this->getStoredEmails(),
        );
        $verifiers = $this->getStoredVerifiers();
        self::assertNotEquals('', $verifiers[0]);
        self::assertEquals('', $verifiers[1]);
        self::assertNotEquals('', $verifiers[2]);

        $this->dao->saveJustCreatedUserThanksToInvitation('alice@example.com', 201, $second_invitation_to_alice_id);
        self::assertEquals(
            ["", "", ""],
            $this->getStoredEmails(),
        );
        $verifiers = $this->getStoredVerifiers();
        self::assertEquals('', $verifiers[0]);
        self::assertEquals('', $verifiers[1]);
        self::assertEquals('', $verifiers[2]);
    }

    /**
     * @return string[]
     */
    private function getStoredEmails(): array
    {
        return DBFactory::getMainTuleapDBConnection()->getDB()->column("SELECT to_email FROM invitations ORDER BY id");
    }

    /**
     * @return string[]
     */
    private function getStoredVerifiers(): array
    {
        return DBFactory::getMainTuleapDBConnection()->getDB()->column("SELECT verifier FROM invitations ORDER BY id");
    }

    private function createBunchOfInvitations(): array
    {
        $verifier_1 = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $verifier_2 = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $verifier_3 = SplitTokenVerificationString::generateNewSplitTokenVerificationString();

        $first_invitation_to_alice_id  = $this->dao->create(self::CREATED_ON_TIMESTAMP, 101, "alice@example.com", null, null, null, $verifier_1);
        $first_invitation_to_bob_id    = $this->dao->create(self::CREATED_ON_TIMESTAMP, 102, "bob@example.com", null, null, null, $verifier_2);
        $second_invitation_to_alice_id = $this->dao->create(self::CREATED_ON_TIMESTAMP, 103, "alice@example.com", null, null, null, $verifier_3);

        $this->dao->markAsSent($first_invitation_to_alice_id);
        $this->dao->markAsSent($first_invitation_to_bob_id);
        $this->dao->markAsSent($second_invitation_to_alice_id);

        return [$first_invitation_to_alice_id, $first_invitation_to_bob_id, $second_invitation_to_alice_id];
    }

    public function testPurge(): void
    {
        $nb_days = 30;

        $date_without_expired_invitations = (new \DateTimeImmutable())
            ->setTimestamp(self::CREATED_ON_TIMESTAMP);

        $date_with_expired_invitations = (new \DateTimeImmutable())
            ->setTimestamp(self::CREATED_ON_TIMESTAMP + ($nb_days + 1) * 24 * 3600);

        $this->createBunchOfInvitations();

        $purged_invitations = $this->dao->purgeObsoleteInvitations($date_without_expired_invitations, $nb_days);
        self::assertCount(0, $purged_invitations);
        self::assertEquals(3, $this->getNumberOfRemainingInvitations());

        $purged_invitations = $this->dao->purgeObsoleteInvitations($date_with_expired_invitations, $nb_days);
        self::assertCount(3, $purged_invitations);
        self::assertEquals(0, $this->getNumberOfRemainingInvitations());

        DBFactory::getMainTuleapDBConnection()->getDB()->run("DELETE FROM invitations");

        [, , $second_invitation_to_alice_id] = $this->createBunchOfInvitations();

        $this->dao->saveJustCreatedUserThanksToInvitation('alice@example.com', 201, $second_invitation_to_alice_id);

        $purged_invitations = $this->dao->purgeObsoleteInvitations($date_with_expired_invitations, $nb_days);
        self::assertCount(1, $purged_invitations);
        self::assertEquals('bob@example.com', $purged_invitations[0]->to_email);
        self::assertEquals(2, $this->getNumberOfRemainingInvitations());
    }

    public function testWithdrawPendingInvitationsForProject(): void
    {
        $first_to_be_removed_id   = $this->dao->create(
            self::CREATED_ON_TIMESTAMP,
            101,
            'alice@example.com',
            null,
            101,
            null,
            SplitTokenVerificationString::generateNewSplitTokenVerificationString(),
        );
        $another_user_invit_id    = $this->dao->create(
            self::CREATED_ON_TIMESTAMP,
            101,
            'bob@example.com',
            null,
            101,
            null,
            SplitTokenVerificationString::generateNewSplitTokenVerificationString(),
        );
        $another_project_invit_id = $this->dao->create(
            self::CREATED_ON_TIMESTAMP,
            101,
            'alice@example.com',
            null,
            102,
            null,
            SplitTokenVerificationString::generateNewSplitTokenVerificationString(),
        );
        $second_to_be_removed_id  = $this->dao->create(
            self::CREATED_ON_TIMESTAMP,
            102,
            'alice@example.com',
            null,
            101,
            null,
            SplitTokenVerificationString::generateNewSplitTokenVerificationString(),
        );
        $this->dao->markAsSent($first_to_be_removed_id);
        $this->dao->markAsSent($another_user_invit_id);
        $this->dao->markAsSent($another_project_invit_id);
        $this->dao->markAsSent($second_to_be_removed_id);

        self::assertEquals(
            ['alice@example.com', 'bob@example.com', 'alice@example.com', 'alice@example.com'],
            $this->getStoredEmails(),
        );

        $this->instrumentation
            ->expects(self::once())
            ->method('incrementExpiredInvitations')
            ->with(2);

        $this->dao->withdrawPendingInvitationsForProject('alice@example.com', 101);

        self::assertEquals(
            ['bob@example.com', 'alice@example.com'],
            $this->getStoredEmails(),
        );
        self::assertEquals('bob@example.com', $this->dao->searchById($another_user_invit_id)->to_email);
        self::assertEquals('alice@example.com', $this->dao->searchById($another_project_invit_id)->to_email);
    }

    public function testRemovePendingInvitationsMadeByUser(): void
    {
        $a_sent_invitation_to_be_removed_id = $this->dao->create(
            self::CREATED_ON_TIMESTAMP,
            101,
            'alice@example.com',
            null,
            null,
            null,
            SplitTokenVerificationString::generateNewSplitTokenVerificationString(),
        );
        $this->dao->markAsSent($a_sent_invitation_to_be_removed_id);

        $another_sent_invitation_to_be_removed_id = $this->dao->create(
            self::CREATED_ON_TIMESTAMP,
            101,
            'bob@example.com',
            null,
            null,
            null,
            SplitTokenVerificationString::generateNewSplitTokenVerificationString(),
        );
        $this->dao->markAsSent($another_sent_invitation_to_be_removed_id);

        $a_used_invitation_that_should_not_be_removed = $this->dao->create(
            self::CREATED_ON_TIMESTAMP,
            101,
            'charlie@example.com',
            null,
            null,
            null,
            SplitTokenVerificationString::generateNewSplitTokenVerificationString(),
        );
        $this->dao->markAsSent($a_used_invitation_that_should_not_be_removed);

        $an_invitation_that_is_kept_in_sent_status_for_a_registered_user_and_should_not_be_removed = $this->dao->create(
            self::CREATED_ON_TIMESTAMP,
            101,
            'charlie@example.com',
            null,
            null,
            null,
            SplitTokenVerificationString::generateNewSplitTokenVerificationString(),
        );
        $this->dao->markAsSent($an_invitation_that_is_kept_in_sent_status_for_a_registered_user_and_should_not_be_removed);

        $this->dao->saveJustCreatedUserThanksToInvitation('charlie@example.com', 201, $a_used_invitation_that_should_not_be_removed);

        $this->instrumentation
            ->expects(self::once())
            ->method('incrementExpiredInvitations')
            ->with(2);

        $this->dao->removePendingInvitationsMadeByUser(101);

        self::assertEquals(
            [
                $a_used_invitation_that_should_not_be_removed,
                $an_invitation_that_is_kept_in_sent_status_for_a_registered_user_and_should_not_be_removed,
            ],
            DBFactory::getMainTuleapDBConnection()->getDB()->column("SELECT id FROM invitations ORDER BY id"),
        );
    }

    private function getNumberOfRemainingInvitations(): int
    {
        return DBFactory::getMainTuleapDBConnection()->getDB()->single("SELECT count(*) FROM invitations");
    }
}
