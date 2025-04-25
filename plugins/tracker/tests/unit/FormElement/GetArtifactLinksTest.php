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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElementFactory;
use Tracker_HierarchyFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class GetArtifactLinksTest extends TestCase
{
    private int $current_id = 100;
    private PFUser $user;
    private Tracker $tracker;
    private Tracker_FormElementFactory&MockObject $factory;
    private Tracker_Artifact_Changeset $changeset;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->user      = new PFUser(['language_id' => 'en']);
        $this->tracker   = TrackerTestBuilder::aTracker()->build();
        $this->factory   = $this->createMock(Tracker_FormElementFactory::class);
        $this->changeset = ChangesetTestBuilder::aChangeset(6541)->build();
        $this->artifact  = ArtifactTestBuilder::anArtifact($this->current_id)->inTracker($this->tracker)->withChangesets($this->changeset)->build();

        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $hierarchy_factory->method('getChildren')->willReturn([]);
        $this->artifact->setFormElementFactory($this->factory);
        $this->artifact->setHierarchyFactory($hierarchy_factory);
    }

    protected function tearDown(): void
    {
        $this->current_id++;
    }

    public function testItReturnsAnEmptyListWhenThereIsNoArtifactLinkField(): void
    {
        $this->factory->method('getAnArtifactLinkField')->with($this->user, $this->tracker)->willReturn(null);
        $links = $this->artifact->getLinkedArtifacts($this->user);
        self::assertEquals([], $links);
    }

    public function testItReturnsAlistOfTheLinkedArtifacts(): void
    {
        $artifact_111 = ArtifactTestBuilder::anArtifact(111)->withChangesets(ChangesetTestBuilder::aChangeset(111)->build())->userCanView($this->user)->build();
        $artifact_222 = ArtifactTestBuilder::anArtifact(222)->withChangesets(ChangesetTestBuilder::aChangeset(222)->build())->userCanView($this->user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(6541)->build();
        $this->changeset->setFieldValue($field, ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $field)
            ->withLinks([
                111 => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact_111, ''),
                222 => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact_222, ''),
            ])->build());

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->expects($this->exactly(2))->method('getArtifactById')->willReturnCallback(static fn(int $id) => match ($id) {
            111 => $artifact_111,
            222 => $artifact_222,
        });
        $field->setArtifactFactory($artifact_factory);

        $this->factory->method('getAnArtifactLinkField')->with($this->user, $this->tracker)->willReturn($field);

        self::assertEquals([$artifact_111, $artifact_222], $this->artifact->getLinkedArtifacts($this->user));
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
        $artifact3 = $this->giveMeAnArtifactWithChildren([]);
        $artifact2 = $this->giveMeAnArtifactWithChildren([]);
        $artifact1 = $this->giveMeAnArtifactWithChildren([$artifact2, $artifact3]);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(6541)->build();
        $this->changeset->setFieldValue($field, ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $field)
            ->withLinks([
                $artifact1->getId() => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact1, ''),
                $artifact2->getId() => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact2, ''),
            ])->build());

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->expects($this->exactly(2))->method('getArtifactById')->willReturnCallback(static fn(int $id) => match ($id) {
            $artifact1->getId() => $artifact1,
            $artifact2->getId() => $artifact2,
        });
        $field->setArtifactFactory($artifact_factory);

        $this->factory->method('getAnArtifactLinkField')->with($this->user, $this->tracker)->willReturn($field);

        self::assertEquals([$artifact1], $this->artifact->getUniqueLinkedArtifacts($this->user));
    }

    /**
     * Artifact Links
     * - art 1
     *     - art 2
     *     - art 3
     *         - art 4
     * - art 4 (should be hidden)
     */
    public function testItReturnsOnlyOneIfTwoLinksIdenticalInSubHierarchies(): void
    {
        $artifact4 = $this->giveMeAnArtifactWithChildren([]);
        $artifact3 = $this->giveMeAnArtifactWithChildren([$artifact4]);
        $artifact2 = $this->giveMeAnArtifactWithChildren([]);
        $artifact1 = $this->giveMeAnArtifactWithChildren([$artifact2, $artifact3]);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(6541)->build();
        $this->changeset->setFieldValue($field, ChangesetValueArtifactLinkTestBuilder::aValue(1, $this->changeset, $field)
            ->withLinks([
                $artifact1->getId() => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact1, ''),
                $artifact4->getId() => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact4, ''),
            ])->build());

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->expects($this->exactly(2))->method('getArtifactById')->willReturnCallback(static fn(int $id) => match ($id) {
            $artifact1->getId() => $artifact1,
            $artifact4->getId() => $artifact4,
        });
        $field->setArtifactFactory($artifact_factory);
        $this->factory->method('getAnArtifactLinkField')->with($this->user, $this->tracker)->willReturn($field);

        self::assertEquals([$artifact1], $this->artifact->getUniqueLinkedArtifacts($this->user));
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
        $us_tracker     = TrackerTestBuilder::aTracker()->withId(101)->build();
        $task_tracker   = TrackerTestBuilder::aTracker()->withId(102)->build();
        $bug_tracker    = TrackerTestBuilder::aTracker()->withId(103)->build();
        $sprint_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();

        $us_link_field     = ArtifactLinkFieldBuilder::anArtifactLinkField(1015)->build();
        $task_link_field   = ArtifactLinkFieldBuilder::anArtifactLinkField(1025)->build();
        $bug_link_field    = ArtifactLinkFieldBuilder::anArtifactLinkField(1035)->build();
        $sprint_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(1045)->build();

        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $hierarchy_factory->method('getChildren')->willReturnCallback(static fn(int $id) => match ($id) {
            $us_tracker->getId() => [$task_tracker],
            $task_tracker->getId(),
            $bug_tracker->getId(),
            $sprint_tracker->getId(),
            0                    => [],
        });

        $field_factory = $this->createMock(Tracker_FormElementFactory::class);
        $field_factory->method('getAnArtifactLinkField')->willReturnCallback(static fn(PFUser $user, Tracker $tracker) => match ($tracker) {
            $us_tracker     => $us_link_field,
            $task_tracker   => $task_link_field,
            $bug_tracker    => $bug_link_field,
            $sprint_tracker => $sprint_link_field,
        });

        $artifact4_changeset = ChangesetTestBuilder::aChangeset(4)->build();
        $artifact4           = ArtifactTestBuilder::anArtifact(4)->inTracker($bug_tracker)->withChangesets($artifact4_changeset)->userCanView($this->user)->build();
        $artifact4_changeset->setFieldValue(
            $bug_link_field,
            ChangesetValueArtifactLinkTestBuilder::aValue(4, $artifact4_changeset, $bug_link_field)->withLinks([])->build(),
        );

        $artifact3_changeset = ChangesetTestBuilder::aChangeset(3)->build();
        $artifact3           = ArtifactTestBuilder::anArtifact(3)->inTracker($bug_tracker)->withChangesets($artifact3_changeset)->userCanView($this->user)->build();
        $artifact3_changeset->setFieldValue(
            $bug_link_field,
            ChangesetValueArtifactLinkTestBuilder::aValue(3, $artifact3_changeset, $bug_link_field)->withLinks([])->build(),
        );

        $artifact2_changeset = ChangesetTestBuilder::aChangeset(2)->build();
        $artifact2           = ArtifactTestBuilder::anArtifact(2)->inTracker($task_tracker)->withChangesets($artifact2_changeset)->userCanView($this->user)->build();
        $artifact2_changeset->setFieldValue(
            $task_link_field,
            ChangesetValueArtifactLinkTestBuilder::aValue(2, $artifact2_changeset, $task_link_field)->withLinks([
                3 => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact3, ''),
                4 => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact4, ''),
            ])->build(),
        );

        $artifact1_changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $artifact1           = ArtifactTestBuilder::anArtifact(1)->inTracker($us_tracker)->withChangesets($artifact1_changeset)->userCanView($this->user)->build();
        $artifact1_changeset->setFieldValue(
            $us_link_field,
            ChangesetValueArtifactLinkTestBuilder::aValue(1, $artifact1_changeset, $us_link_field)->withLinks([
                2 => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact2, ''),
            ])->build(),
        );

        $artifact0_changeset = ChangesetTestBuilder::aChangeset(0)->build();
        $artifact0           = ArtifactTestBuilder::anArtifact(0)->inTracker($sprint_tracker)->withChangesets($artifact0_changeset)->userCanView($this->user)->build();
        $artifact0_changeset->setFieldValue(
            $sprint_link_field,
            ChangesetValueArtifactLinkTestBuilder::aValue(0, $artifact0_changeset, $sprint_link_field)->withLinks([
                1 => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact1, ''),
                3 => Tracker_ArtifactLinkInfo::buildFromArtifact($artifact3, ''),
            ])->build(),
        );

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->method('getArtifactById')->willReturnCallback(static fn(int $id) => match ($id) {
            0 => $artifact0,
            1 => $artifact1,
            2 => $artifact2,
            3 => $artifact3,
            4 => $artifact4,
        });

        $artifact0->setHierarchyFactory($hierarchy_factory);
        $artifact1->setHierarchyFactory($hierarchy_factory);
        $artifact2->setHierarchyFactory($hierarchy_factory);
        $artifact3->setHierarchyFactory($hierarchy_factory);
        $artifact4->setHierarchyFactory($hierarchy_factory);

        $artifact0->setFormElementFactory($field_factory);
        $artifact1->setFormElementFactory($field_factory);
        $artifact2->setFormElementFactory($field_factory);
        $artifact3->setFormElementFactory($field_factory);
        $artifact4->setFormElementFactory($field_factory);

        $us_link_field->setArtifactFactory($artifact_factory);
        $task_link_field->setArtifactFactory($artifact_factory);
        $bug_link_field->setArtifactFactory($artifact_factory);
        $sprint_link_field->setArtifactFactory($artifact_factory);

        self::assertEquals([$artifact1, $artifact3], $artifact0->getUniqueLinkedArtifacts($this->user));
    }

    /**
     * @param Artifact[] $children
     */
    public function giveMeAnArtifactWithChildren(array $children): Artifact
    {
        $sub_trackers   = [];
        $links          = [];
        $indexed_childs = [];
        foreach ($children as $child) {
            $child_tracker                           = $child->getTracker();
            $sub_trackers[$child_tracker->getId()][] = $child_tracker;
            $links[$child->getId()]                  = Tracker_ArtifactLinkInfo::buildFromArtifact($child, '');
            $indexed_childs[$child->getId()]         = $child;
        }

        $this->current_id++;
        $tracker = TrackerTestBuilder::aTracker()->withId($this->current_id)->build();

        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $hierarchy_factory->method('getChildren')->with($this->current_id)->willReturn($sub_trackers);

        $changeset = ChangesetTestBuilder::aChangeset(5341)->build();
        $field     = ArtifactLinkFieldBuilder::anArtifactLinkField(6541)->build();
        $changeset->setFieldValue(
            $field,
            ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset, $field)->withLinks($links)->build(),
        );

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifact_factory->expects($this->exactly(count($indexed_childs)))->method('getArtifactById')->willReturnCallback(static fn(int $id) => $indexed_childs[$id]);
        $field->setArtifactFactory($artifact_factory);

        $factory = $this->createMock(Tracker_FormElementFactory::class);
        $factory->method('getAnArtifactLinkField')->with($this->user, $tracker)->willReturn($field);

        $artifact_id = $this->current_id + 100;

        $artifact = ArtifactTestBuilder::anArtifact($artifact_id)->inTracker($tracker)->withChangesets($changeset)->userCanView($this->user)->build();
        $artifact->setFormElementFactory($factory);
        $artifact->setHierarchyFactory($hierarchy_factory);

        return $artifact;
    }
}
