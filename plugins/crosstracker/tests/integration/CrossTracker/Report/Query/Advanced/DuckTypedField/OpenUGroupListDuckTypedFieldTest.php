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

use BaseLanguageFactory;
use ForgeConfig;
use PFUser;
use ProjectUGroup;
use Tracker;
use Tracker_FormElement_Field_List;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\SearchOnDuckTypedFieldsConfig;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class OpenUGroupListDuckTypedFieldTest extends CrossTrackerFieldTestCase
{
    private PFUser $project_member;
    private Tracker $release_tracker;
    private Tracker $sprint_tracker;
    private int $release_artifact_empty_id;
    private int $release_artifact_with_members_id;
    private int $sprint_artifact_empty_id;
    private int $sprint_artifact_with_members_static_id;

    protected function setUp(): void
    {
        $GLOBALS['Language'] = (new BaseLanguageFactory())->getBaseLanguage('en_US');

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        ForgeConfig::setFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');
        $tracker_builder      = new TrackerDatabaseBuilder($db);
        $core_builder         = new CoreDatabaseBuilder($db);
        $project              = $core_builder->buildProject();
        $project_id           = (int) $project->getID();
        $this->project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);

        $static_ugroup_id = $core_builder->buildStaticUserGroup($project_id, 'MyStaticUGroup');

        $this->release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $this->sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');

        $release_ugroup_field_id = $tracker_builder->buildUserGroupListField($this->release_tracker->getId(), 'ugroup_field', 'sb');
        $release_bind_ids        = $tracker_builder->buildValuesForUserGroupListField($release_ugroup_field_id, [
            ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, $static_ugroup_id,
        ]);
        $sprint_ugroup_field_id  = $tracker_builder->buildUserGroupListField($this->sprint_tracker->getId(), 'ugroup_field', 'tbl');
        $sprint_bind_ids         = $tracker_builder->buildValuesForUserGroupListField($sprint_ugroup_field_id, [
            ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, $static_ugroup_id,
        ]);

        $tracker_builder->setReadPermission(
            $release_ugroup_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_ugroup_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $this->release_artifact_empty_id              = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->release_artifact_with_members_id       = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->sprint_artifact_empty_id               = $tracker_builder->buildArtifact($this->sprint_tracker->getId());
        $this->sprint_artifact_with_members_static_id = $tracker_builder->buildArtifact($this->sprint_tracker->getId());

        $release_artifact_empty_changeset              = $tracker_builder->buildLastChangeset($this->release_artifact_empty_id);
        $release_artifact_with_members_changeset       = $tracker_builder->buildLastChangeset($this->release_artifact_with_members_id);
        $sprint_artifact_empty_changeset               = $tracker_builder->buildLastChangeset($this->sprint_artifact_empty_id);
        $sprint_artifact_with_members_static_changeset = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_members_static_id);

        $tracker_builder->buildListValue(
            $release_artifact_empty_changeset,
            $release_ugroup_field_id,
            Tracker_FormElement_Field_List::NONE_VALUE,
        );
        $tracker_builder->buildListValue(
            $release_artifact_with_members_changeset,
            $release_ugroup_field_id,
            $release_bind_ids[ProjectUGroup::PROJECT_MEMBERS],
        );
        $tracker_builder->buildOpenValue(
            $sprint_artifact_empty_changeset,
            $sprint_ugroup_field_id,
            Tracker_FormElement_Field_List::NONE_VALUE,
            false,
        );
        $tracker_builder->buildOpenValue(
            $sprint_artifact_with_members_static_changeset,
            $sprint_ugroup_field_id,
            $sprint_bind_ids[ProjectUGroup::PROJECT_MEMBERS],
            false,
        );
        $tracker_builder->buildOpenValue(
            $sprint_artifact_with_members_static_changeset,
            $sprint_ugroup_field_id,
            $sprint_bind_ids[$static_ugroup_id],
            false,
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
                "ugroup_field = ''",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testEqualUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field = 'Project members'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testEqualStaticUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field = 'MyStaticUGroup'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field = 'MyStaticUGroup' AND ugroup_field = 'Project members'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testNotEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field != ''",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testNotEqualUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field != 'Project administrators'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_members_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_members_static_id,
        ], $artifacts);
    }

    public function testNotEqualStaticGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field != 'MyStaticUGroup'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_members_id,
            $this->sprint_artifact_empty_id,
        ], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field != 'MyStaticUGroup' AND ugroup_field != 'Project members'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testInUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field IN('Project members')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testInMultipleUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field IN('MyStaticUGroup', 'Project members')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testMultipleIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field IN('MyStaticUGroup') AND ugroup_field IN('Project members')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testNotInUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field NOT IN('MyStaticUGroup')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->release_artifact_with_members_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testNotInMultipleUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field NOT IN('MyStaticUGroup', 'Project members')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testMultipleNotIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "ugroup_field NOT IN('MyStaticUGroup') AND ugroup_field NOT IN('Project members')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }
}
