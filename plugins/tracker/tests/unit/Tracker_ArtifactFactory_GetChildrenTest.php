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

declare(strict_types=1);

namespace Tuleap\Tracker;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_ArtifactFactory_GetChildrenTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_ArtifactDao&MockObject $dao;

    private Tracker_ArtifactFactory&MockObject $artifact_factory;

    private PFUser $user;

    protected function setUp(): void
    {
        $this->dao              = $this->createMock(\Tracker_ArtifactDao::class);
        $this->artifact_factory = $this->createPartialMock(\Tracker_ArtifactFactory::class, ['getDao', 'getInstanceFromRow']);
        $this->artifact_factory->method('getDao')->willReturn($this->dao);

        $this->user = UserTestBuilder::buildSiteAdministrator();
    }

    public function testItFetchAllChildren(): void
    {
        $project = $this->createMock(\Project::class);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userIsAdmin')->with($this->user)->willReturn(true);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getProject')->willReturn($project);

        $artifact_01 = $this->createMock(Artifact::class);
        $artifact_01->method('getId')->willReturn(11);
        $artifact_01->method('getTracker')->willReturn($tracker);

        $artifact_02 = $this->createMock(Artifact::class);
        $artifact_02->method('getId')->willReturn(12);
        $artifact_02->method('getTracker')->willReturn($tracker);

        $artifacts = [
            $artifact_01,
            $artifact_02,
        ];

        $artiafct_as_dar1 = [
            'id' => 55,
            'tracker_id' => '',
            'submitted_by' => '',
            'submitted_on' => '',
            'use_artifact_permissions' => false,
        ];

        $artiafct_as_dar2 = [
            'id' => 56,
            'tracker_id' => '',
            'submitted_by' => '',
            'submitted_on' => '',
            'use_artifact_permissions' => false,
        ];

        $this->dao->method('getChildrenForArtifacts')->with([11, 12])->willReturn([$artiafct_as_dar1, $artiafct_as_dar2]);

        $child_artifact1 = $this->createMock(Artifact::class);
        $child_artifact1->method('userCanView')->willReturn(true);
        $child_artifact2 = $this->createMock(Artifact::class);
        $child_artifact2->method('userCanView')->willReturn(true);

        $this->artifact_factory->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row) {
                $artiafct_as_dar1 => $child_artifact1,
                $artiafct_as_dar2 => $child_artifact2,
            });

        self::assertSame(
            [$child_artifact1, $child_artifact2],
            $this->artifact_factory->getChildrenForArtifacts($this->user, $artifacts),
        );
    }
}
