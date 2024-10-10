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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\User\UserEmailCollection;
use UserManager;

class OrganizeableGitCrossReferencesAndTheContributorsCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceByNatureOrganizer
     */
    private $by_nature_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CommitDetailsCrossReferenceInformationBuilder
     */
    private $information_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var OrganizeableGitCrossReferencesAndTheContributorsCollector
     */
    private $collector;

    protected function setUp(): void
    {
        $this->user = Mockery::mock(\PFUser::class);

        $this->by_nature_organizer = Mockery::mock(
            CrossReferenceByNatureOrganizer::class,
            ['getCurrentUser' => $this->user]
        );


        $this->information_builder = Mockery::mock(CommitDetailsCrossReferenceInformationBuilder::class);
        $this->user_manager        = Mockery::mock(UserManager::class);

        $this->collector = new OrganizeableGitCrossReferencesAndTheContributorsCollector(
            $this->information_builder,
            $this->user_manager,
        );
    }

    public function testItIgnoresCrossReferencesItDoesNotKnow(): void
    {
        $this->by_nature_organizer
            ->shouldReceive('getCrossReferencePresenters')
            ->andReturn(
                [
                    CrossReferencePresenterBuilder::get(1)->withType('tracker')->build(),
                ]
            );

        $this->by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();
        $this->by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->never();

        $this->user_manager
            ->shouldReceive('getUserCollectionByEmails')
            ->with([])
            ->andReturn(new UserEmailCollection());

        $collection = $this->collector->collectOrganizeableGitCrossReferencesAndTheContributorsCollection(
            $this->by_nature_organizer,
        );

        self::assertEmpty($collection->getOrganizeableCrossReferencesInformationCollection());
    }

    public function testItRemovesReferenceIfCommitInformationCannotBeFound(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->withType('git_commit')->build();

        $this->by_nature_organizer
            ->shouldReceive('getCrossReferencePresenters')
            ->andReturn([$ref]);

        $this->information_builder
            ->shouldReceive('getCommitDetailsCrossReferenceInformation')
            ->with($this->user, $ref)
            ->andReturnNull();

        $this->by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();
        $this->by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($ref)
            ->once();

        $this->user_manager
            ->shouldReceive('getUserCollectionByEmails')
            ->with([])
            ->andReturn(new UserEmailCollection());

        $collection = $this->collector->collectOrganizeableGitCrossReferencesAndTheContributorsCollection(
            $this->by_nature_organizer,
        );

        self::assertEmpty($collection->getOrganizeableCrossReferencesInformationCollection());
    }

    public function testItCollectsCollectionOfInformations(): void
    {
        $ref         = CrossReferencePresenterBuilder::get(1)->withType('git_commit')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('git_commit')->build();

        $this->by_nature_organizer
            ->shouldReceive('getCrossReferencePresenters')
            ->andReturn([$ref, $another_ref]);

        $information = new CommitDetailsCrossReferenceInformation(
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
        $this->information_builder
            ->shouldReceive('getCommitDetailsCrossReferenceInformation')
            ->with($this->user, $ref)
            ->andReturn($information);

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
        $this->information_builder
            ->shouldReceive('getCommitDetailsCrossReferenceInformation')
            ->with($this->user, $another_ref)
            ->andReturn($another_information);

        $this->by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->never();

        $leeloo = Mockery::mock(PFUser::class, ['getEmail' => 'leeloo@example.com']);
        $korben = Mockery::mock(PFUser::class, ['getEmail' => 'korben@example.com']);

        $this->user_manager
            ->shouldReceive('getUserCollectionByEmails')
            ->with(['korben@example.com', 'leeloo@example.com'])
            ->andReturn(new UserEmailCollection($leeloo, $korben));

        $collection = $this->collector->collectOrganizeableGitCrossReferencesAndTheContributorsCollection(
            $this->by_nature_organizer,
        );

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

        $this->by_nature_organizer
            ->shouldReceive('getCrossReferencePresenters')
            ->andReturn([$ref, $another_ref]);

        $information = new CommitDetailsCrossReferenceInformation(
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
        $this->information_builder
            ->shouldReceive('getCommitDetailsCrossReferenceInformation')
            ->with($this->user, $ref)
            ->andReturn($information);

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
        $this->information_builder
            ->shouldReceive('getCommitDetailsCrossReferenceInformation')
            ->with($this->user, $another_ref)
            ->andReturn($another_information);

        $this->by_nature_organizer->shouldReceive('removeUnreadableCrossReference')->never();

        $leeloo = Mockery::mock(PFUser::class, ['getEmail' => 'leeloo@example.com']);

        $this->user_manager
            ->shouldReceive('getUserCollectionByEmails')
            ->with(['leeloo@example.com'])
            ->andReturn(new UserEmailCollection($leeloo));

        $this->collector->collectOrganizeableGitCrossReferencesAndTheContributorsCollection(
            $this->by_nature_organizer,
        );
    }
}
