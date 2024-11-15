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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;

final class TrackerHierarchyFactoryGetParentArtifactTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

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
     * @var \Mockery\MockInterface|Artifact
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
        $this->artifact    = Mockery::spy(Artifact::class);
        $tracker           = Mockery::mock(Tracker::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact->shouldReceive('getId')->andReturn($this->artifact_id);
        $tracker->shouldReceive('isProjectAllowedToUseType')->andReturnFalse();

        $this->dao               = Mockery::mock(HierarchyDAO::class);
        $this->artifact_factory  = Mockery::mock(Tracker_ArtifactFactory::class);
        $child_link_retriever    = Mockery::mock(TypeIsChildLinkRetriever::class);
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
        $artifact     = Mockery::mock(Artifact::class);
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
            ->with($artifact_345_row)->andReturn(Mockery::spy(Artifact::class));
        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->with($artifact_346_row)->andReturn(Mockery::spy(Artifact::class));

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(Feedback::WARN);

        $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
    }
}
