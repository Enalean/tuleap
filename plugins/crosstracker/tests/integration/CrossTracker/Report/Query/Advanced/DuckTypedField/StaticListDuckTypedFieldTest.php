<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tracker_FormElement_Field_List;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class StaticListDuckTypedFieldTest extends CrossTrackerFieldTestCase
{
    use GlobalLanguageMock;

    private PFUser $project_member;
    private PFUser $project_admin;
    private Tracker $release_tracker;
    private Tracker $sprint_tracker;
    private Tracker $task_tracker;
    private int $release_artifact_empty_id;
    private int $release_artifact_with_cheese_id;
    private int $release_artifact_with_lead_id;
    private int $sprint_artifact_empty_id;
    private int $sprint_artifact_with_cheese_id;
    private int $sprint_artifact_with_cheese_lead_id;
    private int $task_artifact_with_cheese_id;

    protected function setUp(): void
    {
        $db                   = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder      = new TrackerDatabaseBuilder($db);
        $core_builder         = new CoreDatabaseBuilder($db);
        $project              = $core_builder->buildProject();
        $project_id           = (int) $project->getID();
        $this->project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $this->project_admin  = $core_builder->buildUser('project_admin', 'Project Admin', 'project_admin@example.com');
        $core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $this->project_admin->getId(), $project_id);
        $core_builder->addUserToProjectAdmins((int) $this->project_admin->getId(), $project_id);

        $this->release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $this->sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $this->task_tracker    = $tracker_builder->buildTracker($project_id, 'Task');

        $release_list_field_id = $tracker_builder->buildStaticListField($this->release_tracker->getId(), 'list_field', 'sb');
        $release_bind_ids      = $tracker_builder->buildValuesForStaticListField($release_list_field_id, ['lead', 'management', 'cheese']);
        $sprint_list_field_id  = $tracker_builder->buildStaticListField($this->sprint_tracker->getId(), 'list_field', 'msb');
        $sprint_bind_ids       = $tracker_builder->buildValuesForStaticListField($sprint_list_field_id, ['lead', 'management', 'cheese']);
        $task_list_field_id    = $tracker_builder->buildStaticListField($this->task_tracker->getId(), 'list_field', 'sb');
        $task_bind_ids         = $tracker_builder->buildValuesForStaticListField($task_list_field_id, ['lead', 'management', 'cheese']);

        $tracker_builder->setReadPermission(
            $release_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $task_list_field_id,
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->release_artifact_empty_id           = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->release_artifact_with_cheese_id     = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->release_artifact_with_lead_id       = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->sprint_artifact_empty_id            = $tracker_builder->buildArtifact($this->sprint_tracker->getId());
        $this->sprint_artifact_with_cheese_id      = $tracker_builder->buildArtifact($this->sprint_tracker->getId());
        $this->sprint_artifact_with_cheese_lead_id = $tracker_builder->buildArtifact($this->sprint_tracker->getId());
        $this->task_artifact_with_cheese_id        = $tracker_builder->buildArtifact($this->task_tracker->getId());

        $release_artifact_empty_changeset           = $tracker_builder->buildLastChangeset($this->release_artifact_empty_id);
        $release_artifact_with_cheese_changeset     = $tracker_builder->buildLastChangeset($this->release_artifact_with_cheese_id);
        $release_artifact_with_lead_changeset       = $tracker_builder->buildLastChangeset($this->release_artifact_with_lead_id);
        $sprint_artifact_empty_changeset            = $tracker_builder->buildLastChangeset($this->sprint_artifact_empty_id);
        $sprint_artifact_with_cheese_changeset      = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_cheese_id);
        $sprint_artifact_with_cheese_lead_changeset = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_cheese_lead_id);
        $task_artifact_with_cheese_changeset        = $tracker_builder->buildLastChangeset($this->task_artifact_with_cheese_id);

        $tracker_builder->buildListValue(
            $release_artifact_empty_changeset,
            $release_list_field_id,
            Tracker_FormElement_Field_List::NONE_VALUE
        );
        $tracker_builder->buildListValue(
            $release_artifact_with_cheese_changeset,
            $release_list_field_id,
            $release_bind_ids['cheese']
        );
        $tracker_builder->buildListValue(
            $release_artifact_with_lead_changeset,
            $release_list_field_id,
            $release_bind_ids['lead']
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_empty_changeset,
            $sprint_list_field_id,
            Tracker_FormElement_Field_List::NONE_VALUE
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_cheese_changeset,
            $sprint_list_field_id,
            $sprint_bind_ids['cheese']
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_cheese_lead_changeset,
            $sprint_list_field_id,
            $sprint_bind_ids['cheese']
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_cheese_lead_changeset,
            $sprint_list_field_id,
            $sprint_bind_ids['lead']
        );
        $tracker_builder->buildListValue(
            $task_artifact_with_cheese_changeset,
            $task_list_field_id,
            $task_bind_ids['cheese']
        );
    }

    /**
     * @return list<int>
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     */
    private function getMatchingArtifactIds(CrossTrackerReport $report, PFUser $user): array
    {
        $artifacts = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 5, 0)
            ->getArtifacts();
        return array_values(array_map(static fn(Artifact $artifact) => $artifact->getId(), $artifacts));
    }

    public function testEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field = ''",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field = 'cheese'",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id], $artifacts);
    }

    public function testPermissionsEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field = 'cheese'",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_admin
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_cheese_id,
            $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,
            $this->task_artifact_with_cheese_id,
        ], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field = 'cheese' OR list_field = 'lead'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_cheese_id, $this->release_artifact_with_lead_id,
            $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,
        ], $artifacts);
    }

    public function testMultipleEqualAnd(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field = 'cheese' AND list_field = 'lead'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_cheese_lead_id], $artifacts);
    }

    public function testNotEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field != ''",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_cheese_id, $this->release_artifact_with_lead_id,
            $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,
        ], $artifacts);
    }

    public function testNotEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field != 'lead'",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_cheese_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_cheese_id,
        ], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field != 'lead'",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_admin
        );

        self::assertCount(5, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_cheese_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_cheese_id,
            $this->task_artifact_with_cheese_id,
        ], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field != 'cheese' AND list_field != 'lead'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testInValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field IN('cheese')",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,], $artifacts);
    }

    public function testPermissionsIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field IN('cheese')",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_admin
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_cheese_id,
            $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,
            $this->task_artifact_with_cheese_id,
        ], $artifacts);
    }

    public function testInValues(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field IN('lead', 'cheese')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_cheese_id, $this->release_artifact_with_lead_id,
            $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,
        ], $artifacts);
    }

    public function testMultipleIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field IN('lead') AND list_field IN('cheese')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_cheese_lead_id], $artifacts);
    }

    public function testNotInValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field NOT IN('lead')",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_cheese_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_cheese_id,
        ], $artifacts);
    }

    public function testPermissionsNotIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field NOT IN('lead')",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_admin
        );

        self::assertCount(5, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_cheese_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_cheese_id,
            $this->task_artifact_with_cheese_id,
        ], $artifacts);
    }

    public function testNotInValues(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field NOT IN('lead', 'cheese')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testMultipleNotInValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "list_field NOT IN('lead') AND list_field NOT IN ('cheese')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }
}
