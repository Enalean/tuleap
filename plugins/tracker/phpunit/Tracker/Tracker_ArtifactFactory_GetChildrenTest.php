<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

declare(strict_types = 1);

namespace Tuleap\Tracker;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_ArtifactFactory_GetChildrenTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Tracker_ArtifactDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var PFUser */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = \Mockery::spy(\Tracker_ArtifactDao::class);
        $this->artifact_factory = \Mockery::mock(\Tracker_ArtifactFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->artifact_factory->shouldReceive('getDao')->andReturns($this->dao);

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns(48);
        // Needed to by pass Tracker_Artifact::userCanView
        $this->user->shouldReceive('isSuperUser')->andReturns(true);
    }

    public function testItFetchAllChildren(): void
    {
        $project = \Mockery::mock(\Project::class);

        $tracker = \Mockery::mock(Tracker::class);
        $tracker->shouldReceive('userIsAdmin')->with($this->user)->andReturn(true);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getProject')->andReturn($project);

        $artifact_01 = Mockery::mock(Tracker_Artifact::class);
        $artifact_01->shouldReceive('getId')->andReturn(11);
        $artifact_01->shouldReceive('getTracker')->andReturn($tracker);

        $artifact_02 = Mockery::mock(Tracker_Artifact::class);
        $artifact_02->shouldReceive('getId')->andReturn(12);
        $artifact_02->shouldReceive('getTracker')->andReturn($tracker);

        $artifacts = array(
            $artifact_01,
            $artifact_02,
        );

        $artiafct_as_dar1 = array(
            'id' => 55,
            'tracker_id' => '',
            'submitted_by' => '',
            'submitted_on' => '',
            'use_artifact_permissions' => false,
        );

        $artiafct_as_dar2 = array(
            'id' => 56,
            'tracker_id' => '',
            'submitted_by' => '',
            'submitted_on' => '',
            'use_artifact_permissions' => false,
        );

        $this->dao->shouldReceive('getChildrenForArtifacts')->with(array(11, 12))->andReturns(\TestHelper::arrayToDar($artiafct_as_dar1, $artiafct_as_dar2));

        $child_artifact1 = \Mockery::mock(Tracker_Artifact::class);
        $child_artifact1->shouldReceive('userCanView')->andReturn(true);
        $child_artifact2 = \Mockery::mock(Tracker_Artifact::class);
        $child_artifact2->shouldReceive('userCanView')->andReturn(true);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')->with($artiafct_as_dar1)->andReturns($child_artifact1);
        $this->artifact_factory->shouldReceive('getInstanceFromRow')->with($artiafct_as_dar2)->andReturns($child_artifact2);

        $this->artifact_factory->getChildrenForArtifacts($this->user, $artifacts);
    }
}
