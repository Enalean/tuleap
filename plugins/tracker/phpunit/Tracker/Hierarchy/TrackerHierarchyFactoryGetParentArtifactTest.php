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

declare(strict_types = 1);

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;

final class TrackerHierarchyFactoryGetParentArtifactTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalResponseMock, \Tuleap\GlobalLanguageMock;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HierarchyDAO
     */
    private $dao;
    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var \Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var int
     */
    private $artifact_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact_id = 123;
        $this->artifact    = Mockery::spy(Tracker_Artifact::class);
        $tracker              = Mockery::mock(Tracker::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact->shouldReceive('getId')->andReturn($this->artifact_id);
        $tracker->shouldReceive('isProjectAllowedToUseNature')->andReturnFalse();

        $this->dao               = Mockery::mock(HierarchyDAO::class);
        $this->artifact_factory  = Mockery::mock(Tracker_ArtifactFactory::class);
        $child_link_retriever    = Mockery::mock(NatureIsChildLinkRetriever::class);
        $this->hierarchy_factory = new Tracker_HierarchyFactory(
            $this->dao,
            Mockery::mock(TrackerFactory::class),
            $this->artifact_factory,
            $child_link_retriever
        );

        $this->user = new PFUser(['language_id' => 'en']);
    }

    public function testItReturnsTheParent(): void
    {
        $artifact_id  = 345;
        $artifact_row = ['id' => $artifact_id];
        $artifact         = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($artifact_id);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')->with($artifact_row)->andReturn(
            $artifact
        );
        $this->dao->shouldReceive('getParentsInHierarchy')->with($this->artifact_id)->andReturn([$artifact_row]);

        $parent = $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
        $this->assertEquals($artifact_id, $parent->getId());
    }

    public function testItReturnsNullWhenNoParents(): void
    {
        $this->dao->shouldReceive('getParentsInHierarchy')->with($this->artifact_id)->andReturn([]);

        $parent = $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
        $this->assertNull($parent);
    }

    public function testItGeneratesAWarningWhen2Parents(): void
    {
        $artifact_345_row = ['id' => '345'];
        $artifact_346_row = ['id' => '346'];
        $this->dao->shouldReceive('getParentsInHierarchy')->with($this->artifact_id)->andReturn([$artifact_345_row, $artifact_346_row]);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->with($artifact_345_row)->andReturn(Mockery::spy(Tracker_Artifact::class));
        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->with($artifact_346_row)->andReturn(Mockery::spy(Tracker_Artifact::class));

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(Feedback::WARN, Mockery::any(), Mockery::any())->once();

        $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
    }
}
