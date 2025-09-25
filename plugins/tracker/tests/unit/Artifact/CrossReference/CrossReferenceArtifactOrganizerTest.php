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

namespace Tuleap\Tracker\Artifact\CrossReference;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Color\ColorName;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveViewableArtifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossReferenceArtifactOrganizerTest extends TestCase
{
    private CrossReferenceArtifactOrganizer $organizer;
    private RetrieveViewableArtifact&MockObject $artifact_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact_factory = $this->createMock(RetrieveViewableArtifact::class);

        $this->organizer = new CrossReferenceArtifactOrganizer($this->artifact_factory);
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([CrossReferencePresenterBuilder::get(1)->withType('git')->build()]);
        $by_nature_organizer->expects($this->never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeArtifactReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeArtifactCrossReferencesIfArtifactCannotBeFound(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_tracker_artifact')
            ->withValue('123')
            ->build();

        $this->artifact_factory
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn(null);

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);
        $by_nature_organizer->expects($this->never())->method('moveCrossReferenceToSection');
        $by_nature_organizer->expects($this->once())->method('removeUnreadableCrossReference')->with($a_ref);

        $this->organizer->organizeArtifactReferences($by_nature_organizer);
    }

    public function testItMovesArtifactCrossReferenceToAnUnlabelledSectionWithATitleBadge(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_tracker_artifact')
            ->withValue('123')
            ->build();

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getXRef')->willReturn('bug #123');
        $artifact->method('getTitle')->willReturn('Issue on submit button. Please fix ASAP!');
        $artifact->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->withColor(ColorName::FIESTA_RED)->build());
        $this->artifact_factory
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn($artifact);

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);
        $by_nature_organizer->expects($this->once())->method('moveCrossReferenceToSection')
            ->with(
                self::callback(static fn(CrossReferencePresenter $presenter) => (
                    $presenter->id === 1
                    && $presenter->title === 'Issue on submit button. Please fix ASAP!'
                    && $presenter->title_badge->label === 'bug #123'
                    && $presenter->title_badge->color === 'fiesta-red'
                )),
                ''
            );
        $by_nature_organizer->expects($this->never())->method('removeUnreadableCrossReference');

        $this->organizer->organizeArtifactReferences($by_nature_organizer);
    }

    public function testItMovesArtifactCrossReferenceWithEmptyStringInsteadOfNullTitle(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_tracker_artifact')
            ->withValue('123')
            ->build();

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getXRef')->willReturn('bug #123');
        $artifact->method('getTitle')->willReturn(null);
        $artifact->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->withColor(ColorName::FIESTA_RED)->build());
        $this->artifact_factory
            ->method('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->willReturn($artifact);

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);
        $by_nature_organizer->expects($this->once())->method('moveCrossReferenceToSection')
            ->with(
                self::callback(static fn(CrossReferencePresenter $presenter) => (
                    $presenter->id === 1
                    && $presenter->title === ''
                    && $presenter->title_badge->label === 'bug #123'
                    && $presenter->title_badge->color === 'fiesta-red'
                )),
                ''
            );
        $by_nature_organizer->expects($this->never())->method('removeUnreadableCrossReference');

        $this->organizer->organizeArtifactReferences($by_nature_organizer);
    }
}
