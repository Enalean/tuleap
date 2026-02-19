<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Dao;

use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactDaoTest extends TestIntegrationTestCase
{
    private ArtifactDao $dao;
    private int $parent_artifact;
    private int $child_artifact;
    private int $linked_artifact;
    private int $tracker_child_id;
    private int $not_linked_artifact;

    #[\Override]
    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();

        $tracker_parent = $tracker_builder->buildTracker($project_id, 'Parent');
        $tracker_child  = $tracker_builder->buildTracker($project_id, 'Child');

        $this->tracker_child_id = $tracker_child->getId();

        $parent_link_field = $tracker_builder->buildArtifactLinkField($tracker_parent->getId());

        $this->parent_artifact     = $tracker_builder->buildArtifact($tracker_parent->getId());
        $this->child_artifact      = $tracker_builder->buildArtifact($this->tracker_child_id);
        $this->not_linked_artifact = $tracker_builder->buildArtifact($this->tracker_child_id);
        $this->linked_artifact     = $tracker_builder->buildArtifact($this->tracker_child_id);

        $tracker_builder->buildArtifactRank($this->child_artifact, 1);
        $tracker_builder->buildArtifactRank($this->not_linked_artifact, 2);
        $tracker_builder->buildArtifactRank($this->linked_artifact, 3);

        $parent_changeset = $tracker_builder->buildLastChangeset($this->parent_artifact);
        $tracker_builder->buildLastChangeset($this->child_artifact);
        $tracker_builder->buildLastChangeset($this->not_linked_artifact);
        $tracker_builder->buildLastChangeset($this->linked_artifact);

        $tracker_builder->buildArtifactLinkValue(
            $parent_changeset,
            $parent_link_field,
            $this->child_artifact,
            ArtifactLinkField::TYPE_IS_CHILD,
        );
        $tracker_builder->buildArtifactLinkValue(
            $parent_changeset,
            $parent_link_field,
            $this->linked_artifact,
            '_some_link',
        );

        $this->dao = new ArtifactDao();
    }

    public function testItGetLinkedArtifacts(): void
    {
        $this->assertResults(
            [$this->child_artifact, $this->linked_artifact],
            $this->dao->getLinkedArtifactsByIds([$this->parent_artifact])
        );
    }

    public function testItGetLinkedArtifactsMinusExcluded(): void
    {
        $this->assertResults(
            [$this->child_artifact],
            $this->dao->getLinkedArtifactsByIds([$this->parent_artifact], [$this->linked_artifact])
        );
    }

    public function testItGetChildArtifacts(): void
    {
        $this->assertResults(
            [
                $this->child_artifact,
            ],
            $this->dao->getChildrenForArtifacts([$this->parent_artifact])
        );
    }

    public function testGetLinkedArtifactsOfTrackersConcatenatedToCustomList(): void
    {
        $this->assertResults(
            [
                $this->child_artifact,
                $this->linked_artifact,
            ],
            $this->dao->getLinkedArtifactsOfTrackersConcatenatedToCustomList(
                $this->parent_artifact,
                [$this->tracker_child_id],
                [],
            )
        );

        $this->assertResults(
            [
                $this->child_artifact,
                $this->not_linked_artifact,
                $this->linked_artifact,
            ],
            $this->dao->getLinkedArtifactsOfTrackersConcatenatedToCustomList(
                $this->parent_artifact,
                [$this->tracker_child_id],
                [$this->not_linked_artifact],
            )
        );
    }

    public function testGetLinkedOpenArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(): void
    {
        $this->assertResults(
            [
                $this->child_artifact,
                $this->linked_artifact,
            ],
            $this->dao->getLinkedOpenArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
                $this->parent_artifact,
                [$this->tracker_child_id],
                [],
                [],
                100,
                0,
            )
        );
        self::assertSame(2, $this->dao->foundRows());

        $this->assertResults(
            [
                $this->child_artifact,
                $this->not_linked_artifact,
            ],
            $this->dao->getLinkedOpenArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
                $this->parent_artifact,
                [$this->tracker_child_id],
                [],
                [$this->not_linked_artifact],
                2,
                0,
            )
        );
        self::assertSame(3, $this->dao->foundRows());

        $this->assertResults(
            [
                $this->linked_artifact,
            ],
            $this->dao->getLinkedOpenArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
                $this->parent_artifact,
                [$this->tracker_child_id],
                [],
                [$this->not_linked_artifact],
                2,
                2,
            )
        );
        self::assertSame(3, $this->dao->foundRows());
    }

    private function assertResults(array $expected, array $actual): void
    {
        self::assertEqualsCanonicalizing($expected, array_map(
            static fn(array $row): int => $row['id'],
            $actual,
        ));
    }
}
