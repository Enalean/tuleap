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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker;
use Tracker_Artifact_ChangesetValue_ArtifactLinkDiff;
use Tracker_ArtifactLinkInfo;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;

class ArtifactLinkDiffTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var M\MockInterface|TypePresenterFactory
     */
    private $factory;
    /**
     * @var M\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker = M::mock(Tracker::class, ['isProjectAllowedToUseType' => true]);
        $this->factory = M::mock(TypePresenterFactory::class);
        $this->user    = M::mock(PFUser::class);
    }

    public function testHasNoChangesWithEmptyArrays()
    {
        $previous           = [];
        $next               = [];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertFalse($artifact_link_diff->hasChanges());
    }

    public function testHasNoChangesWhenTheTwoArtifactsAreEquals()
    {
        $previous           = [
            122 => new Tracker_ArtifactLinkInfo(122, '*', '*', '*', '*', ''),
        ];
        $next               = [
            122 => new Tracker_ArtifactLinkInfo(122, '*', '*', '*', '*', ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertFalse($artifact_link_diff->hasChanges());
    }

    public function testHasChangesWithANewArtifact()
    {
        $this->factory->shouldReceive('getFromShortname')->andReturn(new TypePresenter('', '', '', true));
        $previous           = [];
        $next               = [
            122 => new Tracker_ArtifactLinkInfo(122, '*', '*', '*', '*', ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertTrue($artifact_link_diff->hasChanges());
    }

    public function testHasChangesWhenAnArtifactIsRemoved()
    {
        $previous           = [
            122 => new Tracker_ArtifactLinkInfo(122, '*', '*', '*', '*', ''),
        ];
        $next               = [];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertTrue($artifact_link_diff->hasChanges());
    }

    public function testGetFormattedWithoutChanges()
    {
        $previous           = [];
        $next               = [];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertEquals('', $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithAnAddedArtifactWithoutType()
    {
        $this->factory->shouldReceive('getFromShortname')->with('')->andReturn(new TypePresenter('', '', '', true));
        $previous           = [];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertEquals("\n    * Added: bug #122\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithRemovedAllArtifactsWithoutType()
    {
        $this->factory->shouldReceive('getFromShortname')->with('')->andReturn(new TypePresenter('', '', '', true));
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', ''),
        ];
        $next               = [];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertEquals(' cleared', $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithRemovedOneArtifactWithoutType()
    {
        $this->factory->shouldReceive('getFromShortname')->with('')->andReturn(new TypePresenter('', '', '', true));
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', ''),
            123 => $this->getTrackerArtifactLinkInfo(123, 'bug', ''),
        ];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertEquals("\n    * Removed: bug #123\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithAnAddedArtifactWithRandomType()
    {
        $this->factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn(new TypePresenter('fixed_in', 'Fixed in', '', true));
        $previous           = [];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', 'fixed_in'),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertEquals("\n    * Added Fixed in: bug #122\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testGetFormattedWithRemovedOneArtifactWithRandomType()
    {
        $this->factory->shouldReceive('getFromShortname')->with('')->andReturn(new TypePresenter('', '', '', true));
        $this->factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn(new TypePresenter('fixed_in', 'Fixed in', '', true));
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', ''),
            123 => $this->getTrackerArtifactLinkInfo(123, 'bug', 'fixed_in'),
        ];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', ''),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertEquals("\n    * Removed: bug #123\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testChangedArtifactLinkSetType()
    {
        $this->factory->shouldReceive('getFromShortname')->with('')->andReturn(new TypePresenter('', '', '', true));
        $this->factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn(new TypePresenter('fixed_in', 'Fixed in', '', true));
        $this->factory->shouldReceive('getFromShortname')->with('reported_in')->andReturn(new TypePresenter('reported_in', 'Reported in', '', true));
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', ''),
            123 => $this->getTrackerArtifactLinkInfo(123, 'bug', 'fixed_in'),
        ];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', 'reported_in'),
            123 => $this->getTrackerArtifactLinkInfo(123, 'bug', 'fixed_in'),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertEquals("\n    * Changed type from no type to Reported in: bug #122\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    public function testChangedArtifactLinkRemoveType()
    {
        $this->factory->shouldReceive('getFromShortname')->with('')->andReturn(new TypePresenter('', '', '', true));
        $this->factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn(new TypePresenter('fixed_in', 'Fixed in', '', true));
        $this->factory->shouldReceive('getFromShortname')->with('reported_in')->andReturn(new TypePresenter('reported_in', 'Reported in', '', true));
        $previous           = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', 'reported_in'),
            123 => $this->getTrackerArtifactLinkInfo(123, 'bug', 'fixed_in'),
        ];
        $next               = [
            122 => $this->getTrackerArtifactLinkInfo(122, 'bug', ''),
            123 => $this->getTrackerArtifactLinkInfo(123, 'bug', 'fixed_in'),
        ];
        $artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($previous, $next, $this->tracker, $this->factory);
        $this->assertEquals("\n    * Changed type from Reported in to no type: bug #122\n", $artifact_link_diff->fetchFormatted($this->user, '*', false));
    }

    private function getTrackerArtifactLinkInfo(int $artifact_id, string $keyword, string $type): Tracker_ArtifactLinkInfo
    {
        $tracker   = M::mock(Tracker::class, ['getItemName' => $keyword, 'getGroupId' => '*', 'getId' => 888]);
        $changeset = M::mock(\Tracker_Artifact_Changeset::class, ['getId' => '*']);
        $artifact  = M::mock(
            \Tuleap\Tracker\Artifact\Artifact::class,
            [
                'getId' => $artifact_id,
                'userCanView' => true,
                'getTracker' => $tracker,
                'getLastChangeset' => $changeset,
            ]
        );
        return Tracker_ArtifactLinkInfo::buildFromArtifact($artifact, $type);
    }
}
