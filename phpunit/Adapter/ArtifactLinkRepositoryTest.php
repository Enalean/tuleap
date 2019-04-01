<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\GlobalLanguageMock;

class ArtifactLinkRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var ArtifactLinkRepository */
    private $repository;

    /** @before */
    protected function createInstance()
    {
        $this->repository = new ArtifactLinkRepository();
    }

    /** @var PFUser */
    private $current_user;

    /** @var Tracker_Artifact_Changeset|MockInterface */
    protected $changeset;

    /** @before */
    protected function createEntities(): void
    {
        $this->current_user = new PFUser();
        $this->changeset    = Mockery::mock(Tracker_Artifact_Changeset::class);
    }

    public function testFindLinkedArtifactIds()
    {
        $artifact_link = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class)
            ->shouldReceive('getLinkedArtifacts')
            ->with($this->changeset, $this->current_user)
            ->andReturn(
                [
                    $this->mockArtifactWithId(1),
                    $this->mockArtifactWithId(2),
                    $this->mockArtifactWithId(3)
                ]
            )
            ->getMock();

        $artifact = Mockery::mock(Tracker_Artifact::class)
            ->shouldReceive('getAnArtifactLinkField')
            ->with($this->current_user)
            ->andReturn($artifact_link)
            ->getMock();

        $this->changeset
            ->shouldReceive('getArtifact')
            ->andReturn($artifact);

        $artifact_ids = $this->repository->findLinkedArtifactIds($this->current_user, $this->changeset);

        $this->assertEquals([1, 2, 3], $artifact_ids);
    }

    public function testFindLinkedArtifactIdsReturnsEmptyArrayWhenNoLinkField()
    {
        $artifact = Mockery::mock(Tracker_Artifact::class)
            ->shouldReceive('getAnArtifactLinkField')
            ->with($this->current_user)
            ->andReturn(null)
            ->getMock();

        $this->changeset
            ->shouldReceive('getArtifact')
            ->andReturn($artifact);

        $artifact_ids = $this->repository->findLinkedArtifactIds($this->current_user, $this->changeset);

        $this->assertEquals([], $artifact_ids);
    }

    /**
     * @return Tracker_Artifact|MockInterface
     */
    private function mockArtifactWithId(int $id): Tracker_Artifact
    {
        return Mockery::mock(Tracker_Artifact::class)
            ->shouldReceive('getId')
            ->andReturn($id)
            ->getMock();
    }
}
