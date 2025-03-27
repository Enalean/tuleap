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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerHierarchyFactoryGetParentArtifactTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,
{
    use GlobalResponseMock;

    private HierarchyDAO&MockObject $dao;
    private Tracker_HierarchyFactory $hierarchy_factory;
    private PFUser $user;
    private Artifact $artifact;
    private int $artifact_id;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;

    protected function setUp(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('getGroupId')->willReturn(1001);
        $tracker->method('getItemName')->willReturn('foo');
        $tracker->method('isProjectAllowedToUseType')->willReturn(false);

        $this->artifact_id = 123;
        $this->artifact    = ArtifactTestBuilder::anArtifact($this->artifact_id)->inTracker($tracker)->build();

        $this->dao               = $this->createMock(HierarchyDAO::class);
        $this->artifact_factory  = $this->createMock(Tracker_ArtifactFactory::class);
        $child_link_retriever    = $this->createMock(TypeIsChildLinkRetriever::class);
        $this->hierarchy_factory = new Tracker_HierarchyFactory(
            $this->dao,
            $this->createMock(TrackerFactory::class),
            $this->artifact_factory,
            $child_link_retriever
        );

        $this->user = new PFUser(['language_id' => 'en']);
    }

    public function testItReturnsTheParent(): void
    {
        $artifact_id  = 345;
        $artifact_row = ['id' => $artifact_id];
        $artifact     = ArtifactTestBuilder::anArtifact($artifact_id)->build();

        $this->artifact_factory->method('getInstanceFromRow')->with($artifact_row)->willReturn(
            $artifact
        );
        $this->dao->method('getParentsInHierarchy')->with($this->artifact_id)->willReturn([$artifact_row]);

        $parent = $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
        $this->assertEquals($artifact_id, $parent->getId());
    }

    public function testItReturnsNullWhenNoParents(): void
    {
        $this->dao->method('getParentsInHierarchy')->with($this->artifact_id)->willReturn([]);

        $parent = $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
        $this->assertNull($parent);
    }

    public function testItGeneratesAWarningWhen2Parents(): void
    {
        $artifact_345_row = ['id' => '345'];
        $artifact_346_row = ['id' => '346'];
        $this->dao->method('getParentsInHierarchy')->with($this->artifact_id)->willReturn([$artifact_345_row, $artifact_346_row]);

        $this->artifact_factory
            ->method('getInstanceFromRow')
            ->willReturnCallback(static fn(array $row) => ArtifactTestBuilder::anArtifact((int) $row['id'])->build());

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(Feedback::WARN);

        $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
    }
}
