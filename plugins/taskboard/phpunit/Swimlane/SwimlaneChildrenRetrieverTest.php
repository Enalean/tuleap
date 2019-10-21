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
 */

declare(strict_types=1);

namespace Tuleap\Taskboard\Swimlane;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class SwimlaneChildrenRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SwimlaneChildrenRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->retriever = new SwimlaneChildrenRetriever();
    }

    public function testGetSwimlaneArtifactIdsReturnsLinkedArtifactIds(): void
    {
        $first_linked_artifact  = M::mock(\Tracker_Artifact::class)->shouldReceive(['getId' => 123])->getMock();
        $second_linked_artifact = M::mock(\Tracker_Artifact::class)->shouldReceive(['getId' => 456])->getMock();
        $swimlane_artifact      = M::mock(\Tracker_Artifact::class);
        $swimlane_artifact->shouldReceive('getLinkedArtifacts')
            ->once()
            ->andReturn([$first_linked_artifact, $second_linked_artifact]);

        $current_user = M::mock(\PFUser::class);
        $result       = $this->retriever->getSwimlaneArtifactIds($swimlane_artifact, $current_user);
        $this->assertSame(2, count($result));
        $this->assertSame(123, $result[0]);
        $this->assertSame(456, $result[1]);
    }
}
