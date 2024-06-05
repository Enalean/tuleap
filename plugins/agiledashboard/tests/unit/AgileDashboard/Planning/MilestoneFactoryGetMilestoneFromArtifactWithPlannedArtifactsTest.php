<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\AgileDashboard\Planning;

use ArtifactNode;
use Planning_MilestoneFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class MilestoneFactoryGetMilestoneFromArtifactWithPlannedArtifactsTest extends TestCase
{
    public function testItCreateMilestoneFromArtifactAndLoadsItsPlannedArtifacts(): void
    {
        $milestone_factory = $this->createPartialMock(Planning_MilestoneFactory::class, [
            'getPlannedArtifacts',
            'getMilestoneFromArtifact',
        ]);

        $user      = UserTestBuilder::buildWithDefaults();
        $artifact  = ArtifactTestBuilder::anArtifact(101)->build();
        $artifact2 = ArtifactTestBuilder::anArtifact(102)->build();
        $artifact3 = ArtifactTestBuilder::anArtifact(103)->build();

        $node = new ArtifactNode($artifact);
        $node->addChild(new ArtifactNode($artifact2));
        $node->addChild(new ArtifactNode($artifact3));

        $milestone_factory->expects(self::once())->method('getPlannedArtifacts')->with($user, $artifact)->willReturn($node);
        $milestone_factory->expects(self::once())->method('getMilestoneFromArtifact')->with($artifact, $node);

        $milestone_factory->getMilestoneFromArtifactWithPlannedArtifacts($artifact, $user);
    }
}
