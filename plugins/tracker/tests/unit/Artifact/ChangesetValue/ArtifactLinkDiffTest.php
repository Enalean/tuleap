<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_ChangesetValue_ArtifactLinkDiff;
use Tracker_ArtifactLinkInfo;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkDiffTest extends TestCase
{
    private Tracker&MockObject $tracker;
    private TypePresenterFactory&MockObject $factory;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $this->factory = $this->createMock(TypePresenterFactory::class);
        $this->user    = UserTestBuilder::buildWithDefaults();
    }

    public function testHasNoChangesWithEmptyArrays(): void
    {
        $previous           = [];
        $next               = [];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertFalse($artifact_link_diff->hasChanges());
    }

    public function testHasNoChangesWhenTheTwoArtifactsAreEquals(): void
    {
        $previous           = [
            122 => new Tracker_ArtifactLinkInfo(122, '*', '*', '*', '*', ''),
        ];
        $next               = [
            122 => new Tracker_ArtifactLinkInfo(122, '*', '*', '*', '*', ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertFalse($artifact_link_diff->hasChanges());
    }

    public function testHasChangesWithANewArtifact(): void
    {
        $this->factory->method('getFromShortname')->willReturn(new TypePresenter('', '', '', true));
        $previous           = [];
        $next               = [
            122 => new Tracker_ArtifactLinkInfo(122, '*', '*', '*', '*', ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertTrue($artifact_link_diff->hasChanges());
    }

    public function testHasChangesWhenAnArtifactIsRemoved(): void
    {
        $previous           = [
            122 => new Tracker_ArtifactLinkInfo(122, '*', '*', '*', '*', ''),
        ];
        $next               = [];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertTrue($artifact_link_diff->hasChanges());
    }

    public function testGetFormattedWithoutChanges(): void
    {
        $previous           = [];
        $next               = [];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertEquals('', $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithAnAddedArtifactWithoutType(): void
    {
        $this->factory->method('getFromShortname')->with('')->willReturn(new TypePresenter('', '', '', true));
        $previous           = [];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertEquals("\n    * Added: bug #122\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithRemovedAllArtifactsWithoutType(): void
    {
        $this->factory->method('getFromShortname')->with('')->willReturn(new TypePresenter('', '', '', true));
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, ''),
        ];
        $next               = [];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertEquals(' cleared', $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithRemovedOneArtifactWithoutType(): void
    {
        $this->factory->method('getFromShortname')->with('')->willReturn(new TypePresenter('', '', '', true));
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, ''),
            123 => $this->getTrackerArtifactLinkInfo(123, ''),
        ];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertEquals("\n    * Removed: bug #123\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithAnAddedArtifactWithRandomType(): void
    {
        $this->factory->method('getFromShortname')->with('fixed_in')->willReturn(new TypePresenter('fixed_in', 'Fixed in', '', true));
        $previous           = [];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'fixed_in'),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertEquals("\n    * Added Fixed in: bug #122\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithRemovedOneArtifactWithRandomType(): void
    {
        $this->factory->method('getFromShortname')->willReturnCallback(static fn(string $shortname) => match ($shortname) {
            ''         => new TypePresenter('', '', '', true),
            'fixed_in' => new TypePresenter('fixed_in', 'Fixed in', '', true),
        });
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, ''),
            123 => $this->getTrackerArtifactLinkInfo(123, 'fixed_in'),
        ];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertEquals("\n    * Removed: bug #123\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testChangedArtifactLinkSetType(): void
    {
        $this->factory->method('getFromShortname')->willReturnCallback(static fn(string $shortname) => match ($shortname) {
            ''            => new TypePresenter('', '', '', true),
            'fixed_in'    => new TypePresenter('fixed_in', 'Fixed in', '', true),
            'reported_in' => new TypePresenter('reported_in', 'Reported in', '', true),
        });
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, ''),
            123 => $this->getTrackerArtifactLinkInfo(123, 'fixed_in'),
        ];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'reported_in'),
            123 => $this->getTrackerArtifactLinkInfo(123, 'fixed_in'),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertEquals("\n    * Changed type from no type to Reported in: bug #122\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testChangedArtifactLinkRemoveType(): void
    {
        $this->factory->method('getFromShortname')->willReturnCallback(static fn(string $shortname) => match ($shortname) {
            ''            => new TypePresenter('', '', '', true),
            'fixed_in'    => new TypePresenter('fixed_in', 'Fixed in', '', true),
            'reported_in' => new TypePresenter('reported_in', 'Reported in', '', true),
        });
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'reported_in'),
            123 => $this->getTrackerArtifactLinkInfo(123, 'fixed_in'),
        ];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, ''),
            123 => $this->getTrackerArtifactLinkInfo(123, 'fixed_in'),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        self::assertEquals("\n    * Changed type from Reported in to no type: bug #122\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    private function getTrackerArtifactLinkInfo(int $artifact_id, string $type): Tracker_ArtifactLinkInfo
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(888)->withName('bug')->withProject(ProjectTestBuilder::aProject()->build())->build();
        $changeset = ChangesetTestBuilder::aChangeset(15)->build();
        $artifact  = ArtifactTestBuilder::anArtifact($artifact_id)
            ->inTracker($tracker)
            ->withChangesets($changeset)
            ->userCanView($this->user)
            ->build();
        return Tracker_ArtifactLinkInfo::buildFromArtifact($artifact, $type);
    }
}
