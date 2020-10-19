<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

final class Tracker_Artifact_getArtifactLinks_Test extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $current_id = 100;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var Tracker
     */
    private $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;
    /**
     * @var Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        $this->user      = new PFUser(['language_id' => 'en']);
        $this->tracker   = Mockery::spy(Tracker::class);
        $this->factory   = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $this->artifact = Mockery::mock(
            Artifact::class,
            [$this->current_id + 100, $this->current_id, null, 10, null]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getChildren')->andReturns([]);

        $this->artifact->shouldReceive('getFormElementFactory')->andReturn($this->factory);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($this->changeset);
        $this->artifact->shouldReceive('getHierarchyFactory')->andReturn($hierarchy_factory);
    }

    protected function tearDown(): void
    {
        $this->current_id ++;
    }

    public function testItReturnsAnEmptyListWhenThereIsNoArtifactLinkField(): void
    {
        $this->factory->shouldReceive('getUsedArtifactLinkFields')->with($this->tracker)->andReturns([]);
        $links = $this->artifact->getLinkedArtifacts($this->user);
        $this->assertEquals([], $links);
    }

    public function testItReturnsAlistOfTheLinkedArtifacts(): void
    {
        $this->artifact->shouldReceive('getLastChangeset')->andReturn(null);
        $expected_list = [
            new Artifact(111, null, null, null, null),
            new Artifact(222, null, null, null, null)
        ];

        $field = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('getLinkedArtifacts')->with($this->changeset, $this->user)->andReturns($expected_list);

        $this->factory->shouldReceive('getAnArtifactLinkField')->with($this->user, $this->tracker)->andReturns($field);

        $this->assertEquals($expected_list, $this->artifact->getLinkedArtifacts($this->user));
    }

    /**
     * Artifact Links
     * - art 1
     *   - art 2
     *   - art 3
     * - art 2 (should be hidden)
     */
    public function testItReturnsOnlyOneIfTwoLinksIdentical(): void
    {
        $artifact3 = $this->giveMeAnArtifactWithChildren();
        $artifact2 = $this->giveMeAnArtifactWithChildren();
        $artifact1 = $this->giveMeAnArtifactWithChildren($artifact2, $artifact3);

        $field     = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('getLinkedArtifacts')->with($this->changeset, $this->user)->andReturns([$artifact1, $artifact2]);

        $this->factory->shouldReceive('getAnArtifactLinkField')->with($this->user, $this->tracker)->andReturns($field);

        $expected_result = [$artifact1];
        $this->assertEquals($expected_result, $this->artifact->getUniqueLinkedArtifacts($this->user));
    }

    /**
     * Artifact Links
     * - art 1
     *     - art 2
     *     - art 3
     *         -art 4
     * - art 4 (should be hidden)
     */
    public function testItReturnsOnlyOneIfTwoLinksIdenticalInSubHierarchies(): void
    {
        $artifact4 = $this->giveMeAnArtifactWithChildren();
        $artifact3 = $this->giveMeAnArtifactWithChildren($artifact4);
        $artifact2 = $this->giveMeAnArtifactWithChildren();
        $artifact1 = $this->giveMeAnArtifactWithChildren($artifact2, $artifact3);

        $field     = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('getLinkedArtifacts')->with($this->changeset, $this->user)->andReturns([$artifact1, $artifact4]);
        $this->factory->shouldReceive('getAnArtifactLinkField')->with($this->user, $this->tracker)->andReturns($field);

        $expected_result = [$artifact1];
        $this->assertEquals($expected_result, $this->artifact->getUniqueLinkedArtifacts($this->user));
    }

    /**
     * Artifact Links
     * └ art 0 (Sprint)
     *   ┝ art 1 (US)
     *   │ └ art 2 (Task)
     *   │   ┝ art 3 (Bug)
     *   │   └ art 4 (Bug)
     *   └ art 3
     *
     * Tracker hierarchy:
     * - US
     *   - Task
     * - Bug
     * - Sprint
     *
     * As Bug is not a child of Task, we should not get art 3 and 4 under task 2
     * However as art 3 is linked to art 0 we should get it under art 0
     */
    public function testItDoesNotReturnArtifactsThatAreNotInTheHierarchy(): void
    {
        $us_tracker     = Mockery::mock(\Tracker::class);
        $us_tracker->shouldReceive('getId')->andReturns(101);
        $task_tracker   = Mockery::mock(\Tracker::class);
        $task_tracker->shouldReceive('getId')->andReturns(102);
        $bug_tracker    = Mockery::mock(\Tracker::class);
        $bug_tracker->shouldReceive('getId')->andReturns(103);
        $sprint_tracker = Mockery::mock(\Tracker::class);
        $sprint_tracker->shouldReceive('getId')->andReturns(104);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getChildren')->with($us_tracker->getId())->andReturn([$task_tracker]);
        $hierarchy_factory->shouldReceive('getChildren')->with($task_tracker->getId())->andReturn([]);
        $hierarchy_factory->shouldReceive('getChildren')->with($bug_tracker->getId())->andReturn([]);
        $hierarchy_factory->shouldReceive('getChildren')->with($sprint_tracker->getId())->andReturn([]);
        $hierarchy_factory->shouldReceive('getChildren')->with(0)->andReturn([]);

        $artifact4 = \Mockery::mock(
            Artifact::class,
            [
                'getLastChangeset' => \Mockery::mock(Tracker_Artifact_Changeset::class),
                'getAnArtifactLinkField' => \Mockery::mock(
                    Tracker_FormElement_Field_ArtifactLink::class,
                    [
                        'getLinkedArtifacts' => []
                    ]
                ),
            ]
        )->makePartial();

        $artifact3 = \Mockery::mock(
            Artifact::class,
            [
                'getLastChangeset' => \Mockery::mock(Tracker_Artifact_Changeset::class),
                'getAnArtifactLinkField' => \Mockery::mock(
                    Tracker_FormElement_Field_ArtifactLink::class,
                    [
                        'getLinkedArtifacts' => []
                    ]
                ),
            ]
        )->makePartial();

        $artifact2 = \Mockery::mock(
            Artifact::class,
            [
                'getLastChangeset' => \Mockery::spy(Tracker_Artifact_Changeset::class),
            ]
        )->makePartial();

        $artifact1 = \Mockery::mock(
            Artifact::class,
            [
                'getLastChangeset' => \Mockery::spy(Tracker_Artifact_Changeset::class),
                'getAnArtifactLinkField' => \Mockery::mock(
                    Tracker_FormElement_Field_ArtifactLink::class,
                    [
                        'getLinkedArtifacts' => [$artifact2]
                    ]
                ),
            ]
        )->makePartial();

        $artifact0 = \Mockery::mock(
            Artifact::class,
            [
                'getLastChangeset' => \Mockery::spy(Tracker_Artifact_Changeset::class),
                'getAnArtifactLinkField' => \Mockery::mock(
                    Tracker_FormElement_Field_ArtifactLink::class,
                    [
                        'getLinkedArtifacts' => [$artifact1, $artifact3]
                    ]
                ),
            ]
        )->makePartial();

        $artifact2->shouldReceive('getAnArtifactLinkField')->andReturns(
            \Mockery::mock(
                Tracker_FormElement_Field_ArtifactLink::class,
                [
                    'getLinkedArtifacts' => [$artifact2, $artifact4]
                ]
            )
        );

        $artifact0->shouldReceive('getHierarchyFactory')->andReturn($hierarchy_factory);
        $artifact1->shouldReceive('getHierarchyFactory')->andReturn($hierarchy_factory);
        $artifact2->shouldReceive('getHierarchyFactory')->andReturn($hierarchy_factory);
        $artifact3->shouldReceive('getHierarchyFactory')->andReturn($hierarchy_factory);
        $artifact4->shouldReceive('getHierarchyFactory')->andReturn($hierarchy_factory);

        $artifact0->shouldReceive("getId")->andReturn(0);
        $artifact1->shouldReceive("getId")->andReturn(1);
        $artifact2->shouldReceive("getId")->andReturn(2);
        $artifact3->shouldReceive("getId")->andReturn(3);
        $artifact4->shouldReceive("getId")->andReturn(4);

        $artifact0->shouldReceive('getTracker')->andReturn($sprint_tracker);
        $artifact1->shouldReceive('getTracker')->andReturn($us_tracker);
        $artifact2->shouldReceive('getTracker')->andReturn($task_tracker);
        $artifact3->shouldReceive('getTracker')->andReturn($bug_tracker);
        $artifact4->shouldReceive('getTracker')->andReturn($bug_tracker);

        $expected_result = [$artifact1, $artifact3];
        $this->assertEquals($expected_result, $artifact0->getUniqueLinkedArtifacts($this->user));
    }

    /**
     * @return Artifact | Mockery\Mock
     */
    public function giveMeAnArtifactWithChildren()
    {
        $children  = func_get_args();
        $sub_trackers = [];
        foreach ($children as $child) {
            $child_tracker                           = $child->getTracker();
            $sub_trackers[$child_tracker->getId()][] = $child_tracker;
        }

        $this->current_id++;
        $tracker   = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($this->current_id);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getChildren')->with($this->current_id)->andReturns($sub_trackers);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $field     = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('getLinkedArtifacts')->with($changeset, $this->user)->andReturns($children);
        $this->factory->shouldReceive('getAnArtifactLinkField')->with($this->user, $tracker)->andReturns($field);

        $artifact_id = $this->current_id + 100;
        $this->artifact_collaborators[$artifact_id] = [
            'field'     => $field,
            'changeset' => $changeset,
        ];


        $artifact = Mockery::mock(
            Artifact::class,
            [$artifact_id, $this->current_id, null, 10, null]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->shouldReceive('getFormElementFactory')->andReturn($this->factory);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getLastChangeset')->andReturn($changeset);
        $artifact->shouldReceive('getHierarchyFactory')->andReturn($hierarchy_factory);

        return $artifact;
    }
}
