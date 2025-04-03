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

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();

        $tracker_parent = $tracker_builder->buildTracker($project_id, 'Parent');
        $tracker_child  = $tracker_builder->buildTracker($project_id, 'Child');

        $parent_link_field = $tracker_builder->buildArtifactLinkField($tracker_parent->getId());

        $this->parent_artifact = $tracker_builder->buildArtifact($tracker_parent->getId());
        $this->child_artifact  = $tracker_builder->buildArtifact($tracker_child->getId());
        $not_linked_artifact   = $tracker_builder->buildArtifact($tracker_child->getId());
        $this->linked_artifact = $tracker_builder->buildArtifact($tracker_child->getId());

        $tracker_builder->buildArtifactRank($this->child_artifact);

        $parent_changeset = $tracker_builder->buildLastChangeset($this->parent_artifact);
        $tracker_builder->buildLastChangeset($this->child_artifact);
        $tracker_builder->buildLastChangeset($not_linked_artifact);
        $tracker_builder->buildLastChangeset($this->linked_artifact);

        $tracker_builder->buildArtifactLinkValue(
            $project_id,
            $parent_changeset,
            $parent_link_field,
            $this->child_artifact,
            ArtifactLinkField::TYPE_IS_CHILD,
        );
        $tracker_builder->buildArtifactLinkValue(
            $project_id,
            $parent_changeset,
            $parent_link_field,
            $this->linked_artifact,
            '_some_link',
        );

        $this->dao = new ArtifactDao();
    }

    public function testItGetLinkedArtifacts(): void
    {
        $result = $this->dao->getLinkedArtifactsByIds([$this->parent_artifact]);

        self::assertCount(2, $result);
        self::assertEqualsCanonicalizing([$this->child_artifact, $this->linked_artifact], array_map(
            static fn(array $row) => $row['id'],
            $result,
        ));
    }

    public function testItGetLinkedArtifactsMinusExcluded(): void
    {
        $result = $this->dao->getLinkedArtifactsByIds([$this->parent_artifact], [$this->linked_artifact]);

        self::assertCount(1, $result);
        self::assertSame($this->child_artifact, $result[0]['id']);
    }

    public function testItGetChildArtifacts(): void
    {
        $result = $this->dao->getChildrenForArtifacts([$this->parent_artifact]);

        self::assertCount(1, $result);
        self::assertSame($this->child_artifact, $result[0]['id']);
    }
}
