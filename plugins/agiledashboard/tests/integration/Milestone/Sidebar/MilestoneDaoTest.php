<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use Tuleap\AgileDashboard\Builders\DatabaseBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class MilestoneDaoTest extends TestIntegrationTestCase
{
    private MilestoneDao $dao;
    private DatabaseBuilder $builder;

    private int $milestone_id             = 0;
    private int $release_tracker_id       = 0;
    private int $sprint_tracker_id        = 0;
    private int $other_tracker_id         = 0;
    private int $milestone_changeset_id   = 0;
    private int $project_id               = 0;
    private int $artifact_link_field_id   = 0;
    private int $sprint_id                = 0;
    private int $sprint_last_changeset_id = 0;
    private int $list_field_id            = 0;
    /**
     * @var array{
     *     open: array<int>,
     *     closed: array<int>,
     * }
     */
    private array $release_status_values = ['open' => [], 'closed' => []];
    /**
     * @var array{
     *     open: array<int>,
     *     closed: array<int>,
     * }
     */
    private array $sprint_status_values = ['open' => [], 'closed' => []];
    private TrackerDatabaseBuilder $tracker_builder;

    protected function setUp(): void
    {
        $this->dao = new MilestoneDao();

        $db                    = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->builder         = new DatabaseBuilder();
        $core_builder          = new CoreDatabaseBuilder($db);
        $this->tracker_builder = new TrackerDatabaseBuilder($db);

        $this->project_id             = (int) $core_builder->buildProject()->getID();
        $this->release_tracker_id     = $this->tracker_builder->buildTracker($this->project_id, "Release")->getId();
        $this->sprint_tracker_id      = $this->tracker_builder->buildTracker($this->project_id, "Sprint")->getId();
        $this->other_tracker_id       = $this->tracker_builder->buildTracker($this->project_id, "Other")->getId();
        $this->list_field_id          = $this->tracker_builder->buildListField($this->sprint_tracker_id);
        $this->artifact_link_field_id = $this->tracker_builder->buildArtifactLinkField($this->release_tracker_id);
        $this->release_status_values  = $this->tracker_builder->buildOpenAndClosedValuesForField($this->list_field_id, $this->release_tracker_id, ['Open'], ['Closed']);
        $this->sprint_status_values   = $this->tracker_builder->buildOpenAndClosedValuesForField($this->list_field_id, $this->sprint_tracker_id, ['Open'], ['Closed']);
        $this->builder->buildPlanning($this->project_id, $this->release_tracker_id);
        $this->builder->buildPlanning($this->project_id, $this->sprint_tracker_id);
    }

    public function testItRetrievesNoMilestone(): void
    {
        $result = $this->dao->retrieveMilestonesWithSubMilestones(-1, -1);

        self::assertEmpty($result);
    }

    public function testItRetrievesASingleMilestone(): void
    {
        $this->createMilestone();

        $result = $this->dao->retrieveMilestonesWithSubMilestones($this->project_id, $this->release_tracker_id);

        self::assertCount(1, $result);
        self::assertEquals([
            'parent_id'                             => $this->milestone_id,
            'parent_tracker'                        => $this->release_tracker_id,
            'parent_changeset'                      => $this->milestone_changeset_id,
            'parent_submitted_by'                   => 143,
            'parent_submitted_on'                   => 1234567890,
            'parent_use_artifact_permissions'       => 0,
            'parent_per_tracker_artifact_id'        => 1,
            'submilestone_id'                       => null,
            'submilestone_tracker'                  => null,
            'submilestone_changeset'                => null,
            'submilestone_submitted_by'             => null,
            'submilestone_submitted_on'             => null,
            'submilestone_use_artifact_permissions' => null,
            'submilestone_per_tracker_artifact_id'  => null,
        ], $result[0]);
    }

    public function testItRetrievesAMilestoneWithItsSubMilestone(): void
    {
        $this->createMilestoneWithOpenSubMilestone();

        $result = $this->dao->retrieveMilestonesWithSubMilestones($this->project_id, $this->release_tracker_id);

        self::assertCount(1, $result);
        self::assertEquals([
            'parent_id'                             => $this->milestone_id,
            'parent_tracker'                        => $this->release_tracker_id,
            'parent_changeset'                      => $this->milestone_changeset_id,
            'parent_submitted_by'                   => 143,
            'parent_submitted_on'                   => 1234567890,
            'parent_use_artifact_permissions'       => 0,
            'parent_per_tracker_artifact_id'        => 1,
            'submilestone_id'                       => $this->sprint_id,
            'submilestone_tracker'                  => $this->sprint_tracker_id,
            'submilestone_changeset'                => $this->sprint_last_changeset_id,
            'submilestone_submitted_by'             => 143,
            'submilestone_submitted_on'             => 1234567890,
            'submilestone_use_artifact_permissions' => 0,
            'submilestone_per_tracker_artifact_id'  => 1,
        ], $result[0]);
    }

    public function testItRetrievesSeveralMilestoneWithSubMilestones(): void
    {
        $this->createMilestone();
        $this->createMilestoneWithOpenSubMilestone();

        $result = $this->dao->retrieveMilestonesWithSubMilestones($this->project_id, $this->release_tracker_id);

        self::assertCount(2, $result);
        self::assertEquals([
            [
                'parent_id'                             => $this->milestone_id,
                'parent_tracker'                        => $this->release_tracker_id,
                'parent_changeset'                      => $this->milestone_changeset_id,
                'parent_submitted_by'                   => 143,
                'parent_submitted_on'                   => 1234567890,
                'parent_use_artifact_permissions'       => 0,
                'parent_per_tracker_artifact_id'        => 1,
                'submilestone_id'                       => $this->sprint_id,
                'submilestone_tracker'                  => $this->sprint_tracker_id,
                'submilestone_changeset'                => $this->sprint_last_changeset_id,
                'submilestone_submitted_by'             => 143,
                'submilestone_submitted_on'             => 1234567890,
                'submilestone_use_artifact_permissions' => 0,
                'submilestone_per_tracker_artifact_id'  => 1,
            ],
            [
                'parent_id'                             => $this->milestone_id - 1,
                'parent_tracker'                        => $this->release_tracker_id,
                'parent_changeset'                      => $this->milestone_changeset_id - 1,
                'parent_submitted_by'                   => 143,
                'parent_submitted_on'                   => 1234567890,
                'parent_use_artifact_permissions'       => 0,
                'parent_per_tracker_artifact_id'        => 1,
                'submilestone_id'                       => null,
                'submilestone_tracker'                  => null,
                'submilestone_changeset'                => null,
                'submilestone_submitted_by'             => null,
                'submilestone_submitted_on'             => null,
                'submilestone_use_artifact_permissions' => null,
                'submilestone_per_tracker_artifact_id'  => null,
            ],
        ], $result);
    }

    public function testItRetrievesOnlyOpenMilestone(): void
    {
        $this->createClosedMilestone();

        $result = $this->dao->retrieveMilestonesWithSubMilestones($this->project_id, $this->release_tracker_id);

        self::assertEmpty($result);
    }

    public function testItRetrievesOnlyTopMilestoneWhenSubIsClosed(): void
    {
        $this->createMilestoneWithClosedSubMilestone();

        $result = $this->dao->retrieveMilestonesWithSubMilestones($this->project_id, $this->release_tracker_id);

        self::assertCount(1, $result);
        self::assertEquals([
            'parent_id'                             => $this->milestone_id,
            'parent_tracker'                        => $this->release_tracker_id,
            'parent_changeset'                      => $this->milestone_changeset_id,
            'parent_submitted_by'                   => 143,
            'parent_submitted_on'                   => 1234567890,
            'parent_use_artifact_permissions'       => 0,
            'parent_per_tracker_artifact_id'        => 1,
            'submilestone_id'                       => null,
            'submilestone_tracker'                  => null,
            'submilestone_changeset'                => null,
            'submilestone_submitted_by'             => null,
            'submilestone_submitted_on'             => null,
            'submilestone_use_artifact_permissions' => null,
            'submilestone_per_tracker_artifact_id'  => null,
        ], $result[0]);
    }

    public function testItRetrievesOnlyMilestoneInBacklogPlanning(): void
    {
        $this->createMilestoneOutOfPlanning();

        $result = $this->dao->retrieveMilestonesWithSubMilestones($this->project_id, $this->release_tracker_id);

        self::assertCount(1, $result);
        self::assertEquals([
            'parent_id'                             => $this->milestone_id,
            'parent_tracker'                        => $this->release_tracker_id,
            'parent_changeset'                      => $this->milestone_changeset_id,
            'parent_submitted_by'                   => 143,
            'parent_submitted_on'                   => 1234567890,
            'parent_use_artifact_permissions'       => 0,
            'parent_per_tracker_artifact_id'        => 1,
            'submilestone_id'                       => null,
            'submilestone_tracker'                  => null,
            'submilestone_changeset'                => null,
            'submilestone_submitted_by'             => null,
            'submilestone_submitted_on'             => null,
            'submilestone_use_artifact_permissions' => null,
            'submilestone_per_tracker_artifact_id'  => null,
        ], $result[0]);
    }

    public function testItRetrievesOnlyNotDeletedMilestones(): void
    {
        $this->createMilestoneInDeletedTracker();

        $result = $this->dao->retrieveMilestonesWithSubMilestones($this->project_id, $this->release_tracker_id);

        self::assertCount(1, $result);
        self::assertEquals([
            'parent_id'                             => $this->milestone_id,
            'parent_tracker'                        => $this->release_tracker_id,
            'parent_changeset'                      => $this->milestone_changeset_id,
            'parent_submitted_by'                   => 143,
            'parent_submitted_on'                   => 1234567890,
            'parent_use_artifact_permissions'       => 0,
            'parent_per_tracker_artifact_id'        => 1,
            'submilestone_id'                       => null,
            'submilestone_tracker'                  => null,
            'submilestone_changeset'                => null,
            'submilestone_submitted_by'             => null,
            'submilestone_submitted_on'             => null,
            'submilestone_use_artifact_permissions' => null,
            'submilestone_per_tracker_artifact_id'  => null,
        ], $result[0]);
    }

    public function testItRetrievesOnlySubMilestonesInHierarchy(): void
    {
        $this->createMilestoneWithOpenSubMilestoneWithoutHierarchy();

        $result = $this->dao->retrieveMilestonesWithSubMilestones($this->project_id, $this->release_tracker_id);

        self::assertCount(1, $result);
        self::assertEquals([
            'parent_id'                             => $this->milestone_id,
            'parent_tracker'                        => $this->release_tracker_id,
            'parent_changeset'                      => $this->milestone_changeset_id,
            'parent_submitted_by'                   => 143,
            'parent_submitted_on'                   => 1234567890,
            'parent_use_artifact_permissions'       => 0,
            'parent_per_tracker_artifact_id'        => 1,
            'submilestone_id'                       => null,
            'submilestone_tracker'                  => null,
            'submilestone_changeset'                => null,
            'submilestone_submitted_by'             => null,
            'submilestone_submitted_on'             => null,
            'submilestone_use_artifact_permissions' => null,
            'submilestone_per_tracker_artifact_id'  => null,
        ], $result[0]);
    }

    private function createMilestone(): void
    {
        $this->milestone_id           = $this->tracker_builder->buildArtifact($this->release_tracker_id);
        $this->milestone_changeset_id = $this->tracker_builder->buildLastChangeset($this->milestone_id);
        $this->tracker_builder->addStatusValueForArtifact($this->list_field_id, $this->milestone_changeset_id, $this->release_status_values["open"][0]);
    }

    private function createMilestoneWithOpenSubMilestone(): void
    {
        $this->createMilestoneWithOpenSubMilestoneWithoutHierarchy();
        $this->builder->buildHierarchy($this->release_tracker_id, $this->sprint_tracker_id);
    }

    private function createMilestoneWithOpenSubMilestoneWithoutHierarchy(): void
    {
        $this->createMilestone();

        $this->sprint_id                = $this->tracker_builder->buildArtifact($this->sprint_tracker_id);
        $this->sprint_last_changeset_id = $this->tracker_builder->buildLastChangeset($this->sprint_id);

        $this->tracker_builder->addStatusValueForArtifact($this->list_field_id, $this->sprint_last_changeset_id, $this->sprint_status_values["open"][0]);
        $this->tracker_builder->buildArtifactLinkValue($this->project_id, $this->sprint_id, $this->milestone_changeset_id, $this->artifact_link_field_id, '_is_child');
    }

    private function createMilestoneWithClosedSubMilestone(): void
    {
        $this->createMilestone();

        $this->sprint_id                 = $this->tracker_builder->buildArtifact($this->sprint_tracker_id);
        $closed_sprint_last_changeset_id = $this->tracker_builder->buildLastChangeset($this->sprint_id);
        $this->tracker_builder->addStatusValueForArtifact($this->list_field_id, $closed_sprint_last_changeset_id, $this->sprint_status_values["closed"][0]);

        $this->tracker_builder->buildArtifactLinkValue($this->project_id, $this->sprint_id, $this->milestone_changeset_id, $this->artifact_link_field_id, '_is_child');
    }

    private function createClosedMilestone(): void
    {
        $this->milestone_id           = $this->tracker_builder->buildArtifact($this->release_tracker_id);
        $this->milestone_changeset_id = $this->tracker_builder->buildLastChangeset($this->milestone_id);
        $this->tracker_builder->addStatusValueForArtifact($this->list_field_id, $this->milestone_changeset_id, $this->release_status_values["closed"][0]);
    }

    private function createMilestoneOutOfPlanning(): void
    {
        $this->createMilestone();
        $artifact_is_child                  = $this->tracker_builder->buildArtifact($this->other_tracker_id);
        $artifact_is_child_changeset_id     = $this->tracker_builder->buildLastChangeset($artifact_is_child);
        $artifact_without_link              = $this->tracker_builder->buildArtifact($this->sprint_tracker_id);
        $artifact_without_link_changeset_id = $this->tracker_builder->buildLastChangeset($artifact_without_link);

        $this->tracker_builder->buildArtifactLinkValue($this->project_id, $artifact_is_child, $artifact_is_child_changeset_id, $this->artifact_link_field_id, '_is_child');
        $this->tracker_builder->buildArtifactLinkValue($this->project_id, $artifact_without_link, $artifact_without_link_changeset_id, $this->artifact_link_field_id, '');
    }

    private function createMilestoneInDeletedTracker(): void
    {
        $this->createMilestone();
        $db                             = DBFactory::getMainTuleapDBConnection()->getDB();
        $deleted_tracker_id             = (int) $db->insertReturnId(
            'tracker',
            [
                'group_id'      => $this->project_id,
                'name'          => "deleted",
                'status'        => 'A',
                'deletion_date' => '12234567890',
            ]
        );
        $artifact_id                    = $this->tracker_builder->buildArtifact($deleted_tracker_id);
        $artifact_is_child_changeset_id = $this->tracker_builder->buildLastChangeset($deleted_tracker_id);
        $this->tracker_builder->buildArtifactLinkValue($this->project_id, $artifact_id, $artifact_is_child_changeset_id, $this->artifact_link_field_id, '_is_child');
    }
}
