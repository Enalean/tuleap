<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\User\UserEmailCollection;

class CrossReferenceGitOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCollectsOrganizeableGitCrossReferencesToMoveThemInTheirSection(): void
    {
        $collector = Mockery::mock(OrganizeableGitCrossReferencesAndTheContributorsCollector::class);
        $enhancer  = Mockery::mock(CrossReferenceGitEnhancer::class);

        $ref         = CrossReferencePresenterBuilder::get(1)->withType('git_commit')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('git_commit')->build();

        $user                = Mockery::mock(\PFUser::class);
        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class, ['getCurrentUser' => $user]);

        $contributors_email_collection = new UserEmailCollection();

        $commit_details_for_ref         = new CommitDetails(
            '1a2b3c4d5e6f7g8h9i',
            'Another bites to dust',
            '',
            '',
            'korben@example.com',
            'Korben Dallas',
            1234567890,
        );
        $commit_details_for_another_ref = new CommitDetails(
            'a2b3c4d5e6f7g8h9i1',
            'Everything you create, you use to destroy',
            '',
            '',
            'leeloo@example.com',
            'Leeloominaï Lekatariba Lamina-Tchaï Ekbat De Sebat',
            1234567890,
        );
        $collector
            ->shouldReceive('collectOrganizeableGitCrossReferencesAndTheContributorsCollection')
            ->with($by_nature_organizer)
            ->once()
            ->andReturn(
                new OrganizeableGitCrossReferencesAndTheContributors(
                    [
                        new CommitDetailsCrossReferenceInformation($commit_details_for_ref, $ref, 'a'),
                        new CommitDetailsCrossReferenceInformation($commit_details_for_another_ref, $another_ref, 'b'),
                    ],
                    $contributors_email_collection,
                )
            );

        $enhanced_ref = $ref->withTitle('Another bites to dust', null);
        $enhancer
            ->shouldReceive('getCrossReferencePresenterWithCommitInformation')
            ->with(
                $ref,
                $commit_details_for_ref,
                $user,
                $contributors_email_collection
            )
            ->andReturn($enhanced_ref);

        $enhanced_another_ref = $another_ref->withTitle('Everything you create, you use to destroy', null);
        $enhancer
            ->shouldReceive('getCrossReferencePresenterWithCommitInformation')
            ->with(
                $another_ref,
                $commit_details_for_another_ref,
                $user,
                $contributors_email_collection
            )
            ->andReturn($enhanced_another_ref);

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($enhanced_ref, 'a')
            ->once();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($enhanced_another_ref, 'b')
            ->once();

        $organizer = new CrossReferenceGitOrganizer($collector, $enhancer);
        $organizer->organizeGitReferences($by_nature_organizer);
    }
}
