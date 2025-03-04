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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneDaoTest extends TestIntegrationTestCase
{
    private MilestoneDao $dao;

    private int $milestone_id;
    private int $release_tracker_id;
    private int $sprint_tracker_id;
    private int $milestone_changeset_id;
    private int $project_id;
    private int $artifact_link_field_id;
    private int $sprint_id;
    private int $sprint_last_changeset_id;
    private int $release_list_field_id;
    private int $sprint_list_field_id;
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
        $builder               = new DatabaseBuilder();
        $core_builder          = new CoreDatabaseBuilder($db);
        $this->tracker_builder = new TrackerDatabaseBuilder($db);

        $this->project_id             = (int) $core_builder->buildProject('project_name')->getID();
        $this->release_tracker_id     = $this->tracker_builder->buildTracker($this->project_id, 'Release')->getId();
        $this->sprint_tracker_id      = $this->tracker_builder->buildTracker($this->project_id, 'Sprint')->getId();
        $this->release_list_field_id  = $this->tracker_builder->buildStaticListField($this->release_tracker_id, 'list_field', 'sb');
        $this->sprint_list_field_id   = $this->tracker_builder->buildStaticListField($this->sprint_tracker_id, 'list_field', 'sb');
        $this->artifact_link_field_id = $this->tracker_builder->buildArtifactLinkField($this->release_tracker_id);
        $this->release_status_values  = $this->tracker_builder->buildOpenAndClosedValuesForField(
            $this->release_list_field_id,
            $this->release_tracker_id,
            ['Open'],
            ['Closed']
        );
        $this->sprint_status_values   = $this->tracker_builder->buildOpenAndClosedValuesForField(
            $this->sprint_list_field_id,
            $this->sprint_tracker_id,
            ['Open'],
            ['Closed']
        );
        $builder->buildPlanning($this->project_id, $this->release_tracker_id);
        $builder->buildPlanning($this->project_id, $this->sprint_tracker_id);
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

    /**
     * release
     *  | -> sprint without is_child
     *  ` -> 'child' - other tracker NOT in planning
     */
    public function testItRetrievesOnlyMilestoneInBacklogPlanning(): void
    {
        $this->createSubMilestonesThatShouldNotBeFound();

        $result = $this->dao->retrieveMilestonesWithSubMilestones($this->project_id, $this->release_tracker_id);

        self::assertCount(2, $result);
        $expected_milestone_row = [
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
        ];
        self::assertSame($expected_milestone_row, $result[0]);
        self::assertSame($expected_milestone_row, $result[1]);
    }

    public function testItRetrievesOnlyNotDeletedMilestones(): void
    {
        $this->createSubMilestoneInDeletedTracker();

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
        $this->tracker_builder->buildListValue($this->milestone_changeset_id, $this->release_list_field_id, $this->release_status_values['open'][0]);
    }

    private function createMilestoneWithOpenSubMilestone(): void
    {
        $this->createMilestoneWithOpenSubMilestoneWithoutHierarchy();
        $this->tracker_builder->buildHierarchy($this->release_tracker_id, $this->sprint_tracker_id);
    }

    private function createMilestoneWithOpenSubMilestoneWithoutHierarchy(): void
    {
        $this->createMilestone();

        $this->sprint_id                = $this->tracker_builder->buildArtifact($this->sprint_tracker_id);
        $this->sprint_last_changeset_id = $this->tracker_builder->buildLastChangeset($this->sprint_id);

        $this->tracker_builder->buildListValue($this->sprint_last_changeset_id, $this->sprint_list_field_id, $this->sprint_status_values['open'][0]);
        $this->tracker_builder->buildArtifactLinkValue(
            $this->project_id,
            $this->milestone_changeset_id,
            $this->artifact_link_field_id,
            $this->sprint_id,
            '_is_child'
        );
    }

    private function createMilestoneWithClosedSubMilestone(): void
    {
        $this->createMilestone();

        $this->sprint_id                 = $this->tracker_builder->buildArtifact($this->sprint_tracker_id);
        $closed_sprint_last_changeset_id = $this->tracker_builder->buildLastChangeset($this->sprint_id);
        $this->tracker_builder->buildListValue($closed_sprint_last_changeset_id, $this->sprint_list_field_id, $this->sprint_status_values['closed'][0]);

        $this->tracker_builder->buildArtifactLinkValue(
            $this->project_id,
            $this->milestone_changeset_id,
            $this->artifact_link_field_id,
            $this->sprint_id,
            '_is_child'
        );
    }

    private function createClosedMilestone(): void
    {
        $this->milestone_id           = $this->tracker_builder->buildArtifact($this->release_tracker_id);
        $this->milestone_changeset_id = $this->tracker_builder->buildLastChangeset($this->milestone_id);
        $this->tracker_builder->buildListValue($this->milestone_changeset_id, $this->release_list_field_id, $this->release_status_values['closed'][0]);
    }

    private function createSubMilestoneWithUntypedArtifactLink(): void
    {
        $this->tracker_builder->buildHierarchy($this->release_tracker_id, $this->sprint_tracker_id);
        $this->sprint_id                = $this->tracker_builder->buildArtifact($this->sprint_tracker_id);
        $this->sprint_last_changeset_id = $this->tracker_builder->buildLastChangeset($this->sprint_id);
        $this->tracker_builder->buildListValue(
            $this->sprint_last_changeset_id,
            $this->sprint_list_field_id,
            $this->sprint_status_values['open'][0]
        );
        $this->tracker_builder->buildArtifactLinkValue(
            $this->project_id,
            $this->milestone_changeset_id,
            $this->artifact_link_field_id,
            $this->sprint_id,
            ''
        );
    }

    private function createSubMilestoneOutOfPlanning(): void
    {
        $other_tracker_id = $this->tracker_builder->buildTracker($this->project_id, 'Other')->getId();
        $this->tracker_builder->buildHierarchy($this->release_tracker_id, $other_tracker_id);
        $other_list_field_id = $this->tracker_builder->buildStaticListField($this->release_tracker_id, 'list_field', 'sb');
        $other_status_values = $this->tracker_builder->buildOpenAndClosedValuesForField(
            $other_list_field_id,
            $other_tracker_id,
            ['Open'],
            ['Closed']
        );

        $artifact_is_child  = $this->tracker_builder->buildArtifact($other_tracker_id);
        $other_changeset_id = $this->tracker_builder->buildLastChangeset($artifact_is_child);
        $this->tracker_builder->buildListValue(
            $other_changeset_id,
            $other_list_field_id,
            $other_status_values['open'][0]
        );
        $this->tracker_builder->buildArtifactLinkValue(
            $this->project_id,
            $this->milestone_changeset_id,
            $this->artifact_link_field_id,
            $artifact_is_child,
            '_is_child'
        );
    }

    private function createSubMilestonesThatShouldNotBeFound(): void
    {
        $this->createMilestone();
        $this->createSubMilestoneOutOfPlanning();
        $this->createSubMilestoneWithUntypedArtifactLink();
    }

    private function createSubMilestoneInDeletedTracker(): void
    {
        $this->createMilestoneWithOpenSubMilestone();
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->update('tracker', ['deletion_date' => '123456'], ['id' => $this->sprint_tracker_id]);
    }
}
