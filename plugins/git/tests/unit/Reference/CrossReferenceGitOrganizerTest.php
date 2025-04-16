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

use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\UserEmailCollection;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossReferenceGitOrganizerTest extends TestCase
{
    public function testItCollectsOrganizeableGitCrossReferencesToMoveThemInTheirSection(): void
    {
        $collector = $this->createMock(OrganizeableGitCrossReferencesAndTheContributorsCollector::class);
        $enhancer  = $this->createMock(CrossReferenceGitEnhancer::class);

        $ref         = CrossReferencePresenterBuilder::get(1)->withType('git_commit')->build();
        $another_ref = CrossReferencePresenterBuilder::get(2)->withType('git_commit')->build();

        $user                = UserTestBuilder::buildWithDefaults();
        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);

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
        $collector->expects($this->once())->method('collectOrganizeableGitCrossReferencesAndTheContributorsCollection')
            ->with($by_nature_organizer)
            ->willReturn(new OrganizeableGitCrossReferencesAndTheContributors([
                new CommitDetailsCrossReferenceInformation($commit_details_for_ref, $ref, 'a'),
                new CommitDetailsCrossReferenceInformation($commit_details_for_another_ref, $another_ref, 'b'),
            ], $contributors_email_collection));

        $enhanced_ref         = $ref->withTitle('Another bites to dust', null);
        $enhanced_another_ref = $another_ref->withTitle('Everything you create, you use to destroy', null);
        $enhancer->method('getCrossReferencePresenterWithCommitInformation')
            ->with(self::anything(), self::anything(), $user, $contributors_email_collection)
            ->willReturnCallback(static fn($reference) => match ($reference) {
                $ref         => $enhanced_ref,
                $another_ref => $enhanced_another_ref,
            });

        $by_nature_organizer->expects($this->exactly(2))->method('moveCrossReferenceToSection')
            ->with(
                self::callback(static fn($reference) => $reference === $enhanced_ref || $reference === $enhanced_another_ref),
                self::callback(static fn($label) => $label === 'a' || $label === 'b'),
            );

        $organizer = new CrossReferenceGitOrganizer($collector, $enhancer);
        $organizer->organizeGitReferences($by_nature_organizer);
    }
}
