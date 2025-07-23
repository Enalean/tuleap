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

namespace Tuleap\Tracker\FormElement\FieldSpecificProperties;

use Project;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkFieldSpecificPropertiesDAOTest extends TestIntegrationTestCase
{
    private ArtifactLinkFieldSpecificPropertiesDAO $dao;
    private TrackerDatabaseBuilder $tracker_builder;
    private CoreDatabaseBuilder $core_builder;

    #[\Override]
    protected function setUp(): void
    {
        $db                    = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->tracker_builder = new TrackerDatabaseBuilder($db);
        $this->core_builder    = new CoreDatabaseBuilder($db);
        $this->dao             = new ArtifactLinkFieldSpecificPropertiesDAO();
    }

    private function createTrackerInProject(Project $project, string $name): \Tuleap\Tracker\Tracker
    {
        return $this->tracker_builder->buildTracker((int) $project->getId(), $name);
    }

    private function createArtifactLinksFieldInTracker(\Tuleap\Tracker\Tracker $tracker): int
    {
        return $this->tracker_builder->buildArtifactLinkField($tracker->getId());
    }

    public function testSaveAndSearchProperties(): void
    {
        $project                = $this->core_builder->buildProjectWithStatus(
            'Save and search properties',
            Project::STATUS_ACTIVE
        );
        $artifact_link_field_id =  $this->createArtifactLinksFieldInTracker(
            $this->createTrackerInProject($project, 'Story'),
        );

        $empty_properties = $this->dao->searchByFieldId($artifact_link_field_id);
        self::assertEmpty($empty_properties);

        $this->dao->saveSpecificProperties($artifact_link_field_id, []);
        $properties = $this->dao->searchByFieldId($artifact_link_field_id);
        self::assertSame(['field_id' => $artifact_link_field_id, 'can_edit_reverse_links' => 0], $properties);

        $this->dao->saveSpecificProperties($artifact_link_field_id, ['can_edit_reverse_links' => 1]);
        $properties = $this->dao->searchByFieldId($artifact_link_field_id);
        self::assertSame(['field_id' => $artifact_link_field_id, 'can_edit_reverse_links' => 1], $properties);

        $this->dao->saveSpecificProperties($artifact_link_field_id, ['can_edit_reverse_links' => 0]);
        $properties = $this->dao->searchByFieldId($artifact_link_field_id);
        self::assertSame(['field_id' => $artifact_link_field_id, 'can_edit_reverse_links' => 0], $properties);
    }

    public function testDuplicateProperties(): void
    {
        $source_project    = $this->core_builder->buildProjectWithStatus('Source project', Project::STATUS_ACTIVE);
        $duplicate_project = $this->core_builder->buildProjectWithStatus('Duplicate project', Project::STATUS_ACTIVE);

        $source_artifact_link_field_id =  $this->createArtifactLinksFieldInTracker(
            $this->createTrackerInProject($source_project, 'Bug'),
        );

        $duplicate_link_field_id =  $this->createArtifactLinksFieldInTracker(
            $this->createTrackerInProject($duplicate_project, 'Bug2'),
        );

        $this->dao->saveSpecificProperties($source_artifact_link_field_id, ['can_edit_reverse_links' => 1]);
        $properties = $this->dao->searchByFieldId($source_artifact_link_field_id);
        self::assertSame(['field_id' => $source_artifact_link_field_id, 'can_edit_reverse_links' => 1], $properties);

        $this->dao->duplicate($source_artifact_link_field_id, $duplicate_link_field_id);
        $properties = $this->dao->searchByFieldId($duplicate_link_field_id);
        self::assertSame(['field_id' => $duplicate_link_field_id, 'can_edit_reverse_links' => 1], $properties);
    }

    public function testItCountsAndActivatesOnlyActiveTrackersFromActiveProjectsWithAnArtifactLinkField(): void
    {
        $tracker_dao = new \TrackerDao();

        $suspended_project = $this->core_builder->buildProjectWithStatus('Suspended project', Project::STATUS_SUSPENDED);

        $active_tracker_in_suspended_project = $this->createTrackerInProject($suspended_project, 'Active tracker in suspended project');
        $this->createArtifactLinksFieldInTracker($active_tracker_in_suspended_project);

        $deleted_tracker_in_suspended_project = $this->createTrackerInProject($suspended_project, 'Deleted tracker in suspended project');
        $tracker_dao->markAsDeleted($deleted_tracker_in_suspended_project->getId());
        $this->createArtifactLinksFieldInTracker($deleted_tracker_in_suspended_project);

        $active_project = $this->core_builder->buildProjectWithStatus('Active project', Project::STATUS_ACTIVE);

        $active_tracker_in_active_project = $this->createTrackerInProject($active_project, 'Active tracker in active project');
        $this->createArtifactLinksFieldInTracker($active_tracker_in_active_project);

        $active_tracker_in_active_project_2 = $this->createTrackerInProject($active_project, 'Second active tracker in active project');
        $this->createArtifactLinksFieldInTracker($active_tracker_in_active_project_2);

        $deleted_tracker_in_active_project = $this->createTrackerInProject($active_project, 'Deleted tracker in active project');
        $tracker_dao->markAsDeleted($deleted_tracker_in_active_project->getId());
        $this->createArtifactLinksFieldInTracker($deleted_tracker_in_active_project);
        $this->createTrackerInProject($active_project, 'Active tracker without art link field in active project');

        self::assertSame(2, $this->dao->countNumberOfTrackersWithoutTheFeature());

        $this->dao->massActivateForActiveTrackers();

        self::assertSame(0, $this->dao->countNumberOfTrackersWithoutTheFeature());

        $this->core_builder->changeProjectStatus($suspended_project, Project::STATUS_ACTIVE);

        self::assertSame(1, $this->dao->countNumberOfTrackersWithoutTheFeature());
    }
}
