<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Reference;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\UserEmailCollection;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OrganizeableGitCrossReferencesAndTheContributorsCollectorTest extends TestCase
{
    private PFUser $user;
    private CrossReferenceByNatureOrganizer&MockObject $by_nature_organizer;
    private CommitDetailsCrossReferenceInformationBuilder&MockObject $information_builder;
    private UserManager&MockObject $user_manager;
    private OrganizeableGitCrossReferencesAndTheContributorsCollector $collector;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $this->by_nature_organizer->method('getCurrentUser')->willReturn($this->user);


        $this->information_builder = $this->createMock(CommitDetailsCrossReferenceInformationBuilder::class);
        $this->user_manager        = $this->createMock(UserManager::class);

        $this->collector = new OrganizeableGitCrossReferencesAndTheContributorsCollector($this->information_builder, $this->user_manager);
    }

    public function testItIgnoresCrossReferencesItDoesNotKnow(): void
    {
        $this->by_nature_organizer->method('getCrossReferencePresenters')
            ->willReturn([CrossReferencePresenterBuilder::get(1)->withType('tracker')->build()]);

        $this->by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');
        $this->by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');

        $this->user_manager->method('getUserCollectionByEmails')->with([])->willReturn(new UserEmailCollection());

        $collection = $this->collector->collectOrganizeableGitCrossReferencesAndTheContributorsCollection($this->by_nature_organizer);

        self::assertEmpty($collection->getOrganizeableCrossReferencesInformationCollection());
    }

    public function testItRemovesReferenceIfCommitInformationCannotBeFound(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->withType('git_commit')->build();

        $this->by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$ref]);

        $this->information_builder->method('getCommitDetailsCrossReferenceInformation')->with($this->user, $ref)->willReturn(null);

        $this->by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');
        $this->by_nature_organizer->expects($this->once())->method('removeUnreadableCrossReference')->with($ref);

        $this->user_manager->method('getUserCollectionByEmails')->with([])->willReturn(new UserEmailCollection());

        $collection = $this->collector->collectOrganizeableGitCrossReferencesAndTheContributorsCollection($this->by_nature_organizer);

        self::assertEmpty($collection->getOrganizeableCrossReferencesInformationCollection());
    }

    public function testItCollectsCollectionOfInformations(): void
    {
        $ref         = CrossReferencePresenterBuilder::get(1)->withType('git_commit')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('git_commit')->build();

        $this->by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$ref, $another_ref]);

        $information         = new CommitDetailsCrossReferenceInformation(
            new CommitDetails(
                '1a2b3c4d5e6f7g8h9i',
                'Add foo to stuff',
                '',
                '',
                'korben@example.com',
                'Korben Dallas',
                1234567890,
            ),
            $ref,
            '',
        );
        $another_information = new CommitDetailsCrossReferenceInformation(
            new CommitDetails(
                'a2b3c4d5e6f7g8h9i1',
                'Everything you create, you use to destroy',
                '',
                '',
                'leeloo@example.com',
                'Leeloominaï Lekatariba Lamina-Tchaï Ekbat De Sebat',
                1234567890,
            ),
            $another_ref,
            '',
        );
        $this->information_builder->method('getCommitDetailsCrossReferenceInformation')->with($this->user, self::anything())
            ->willReturnCallback(static fn($user, $reference) => match ($reference) {
                $ref         => $information,
                $another_ref => $another_information,
            });

        $this->by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');

        $leeloo = UserTestBuilder::aUser()->withEmail('leeloo@example.com')->build();
        $korben = UserTestBuilder::aUser()->withEmail('korben@example.com')->build();

        $this->user_manager->method('getUserCollectionByEmails')->with(['korben@example.com', 'leeloo@example.com'])
            ->willReturn(new UserEmailCollection($leeloo, $korben));

        $collection = $this->collector->collectOrganizeableGitCrossReferencesAndTheContributorsCollection($this->by_nature_organizer);

        self::assertEquals(
            [$information, $another_information],
            $collection->getOrganizeableCrossReferencesInformationCollection()
        );
        self::assertEquals(
            $leeloo,
            $collection->getContributorsEmailCollection()->getUserByEmail('leeloo@example.com')
        );
        self::assertEquals(
            $korben,
            $collection->getContributorsEmailCollection()->getUserByEmail('korben@example.com')
        );
    }

    public function testItDeduplicateAuthorEmails(): void
    {
        $ref         = CrossReferencePresenterBuilder::get(1)->withType('git_commit')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('git_commit')->build();

        $this->by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$ref, $another_ref]);

        $information         = new CommitDetailsCrossReferenceInformation(
            new CommitDetails(
                '1a2b3c4d5e6f7g8h9i',
                "What's the use in saving life when you see what you do with it?",
                '',
                '',
                'leeloo@example.com',
                'Leeloominaï Lekatariba Lamina-Tchaï Ekbat De Sebat',
                1234567890,
            ),
            $ref,
            '',
        );
        $another_information = new CommitDetailsCrossReferenceInformation(
            new CommitDetails(
                'a2b3c4d5e6f7g8h9i1',
                'Everything you create, you use to destroy',
                '',
                '',
                'leeloo@example.com',
                'Leeloominaï Lekatariba Lamina-Tchaï Ekbat De Sebat',
                1234567890,
            ),
            $ref,
            '',
        );
        $this->information_builder->method('getCommitDetailsCrossReferenceInformation')->with($this->user, self::anything())
            ->willReturnCallback(static fn($user, $reference) => match ($reference) {
                $ref         => $information,
                $another_ref => $another_information,
            });

        $this->by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');

        $leeloo = UserTestBuilder::aUser()->withEmail('leeloo@example.com')->build();

        $this->user_manager->method('getUserCollectionByEmails')->with(['leeloo@example.com'])->willReturn(new UserEmailCollection($leeloo));

        $this->collector->collectOrganizeableGitCrossReferencesAndTheContributorsCollection($this->by_nature_organizer);
    }
}
