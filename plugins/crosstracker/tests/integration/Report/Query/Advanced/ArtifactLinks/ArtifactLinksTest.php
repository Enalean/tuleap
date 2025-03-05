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

namespace Tuleap\CrossTracker\Report\Query\Advanced\ArtifactLinks;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinksTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $user;
    /** @var list<int> */
    private array $epics_ids;
    /** @var list<int> */
    private array $epics_with_children_ids;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);
        $this->uuid = $this->addReportToProject(1, $project_id);

        $epic_tracker  = $tracker_builder->buildTracker($project_id, 'Epic');
        $story_tracker = $tracker_builder->buildTracker($project_id, 'Story');
        $tracker_builder->setViewPermissionOnTracker($epic_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($story_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->buildHierarchy($epic_tracker->getId(), $story_tracker->getId());

        $link_field = $tracker_builder->buildArtifactLinkField($epic_tracker->getId());
        $tracker_builder->grantReadPermissionOnField($link_field, ProjectUGroup::PROJECT_MEMBERS);

        $epic_1  = $tracker_builder->buildArtifact($epic_tracker->getId());
        $epic_2  = $tracker_builder->buildArtifact($epic_tracker->getId());
        $story_1 = $tracker_builder->buildArtifact($story_tracker->getId());

        $tracker_builder->buildLastChangeset($epic_1);
        $epic_2_changeset = $tracker_builder->buildLastChangeset($epic_2);
        $tracker_builder->buildLastChangeset($story_1);

        $tracker_builder->buildArtifactLinkValue(
            $project_id,
            $epic_2_changeset,
            $link_field,
            $story_1,
            '_is_child',
        );

        $this->epics_ids               = [$epic_1, $epic_2];
        $this->epics_with_children_ids = [$epic_2];
    }

    public function testWithParent(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITH PARENT',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testWithParentArtifact(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITH PARENT ARTIFACT = 123',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testWithParentTracker(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITH PARENT TRACKER = "epic"',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testWithoutParent(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT PARENT',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testWithoutParentArtifact(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT PARENT ARTIFACT = 123',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testWithoutParentTracker(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT PARENT TRACKER = "epic"',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testIsLinkedFrom(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED FROM WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testIsLinkedFromArtifact(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED FROM ARTIFACT = 123 WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testIsLinkedFromTracker(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED FROM TRACKER = "epic" WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testIsNotLinkedFrom(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED FROM WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testIsNotLinkedFromArtifact(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED FROM ARTIFACT = 123 WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testIsNotLinkedFromTracker(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED FROM TRACKER = "epic" WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testWithChildren(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITH CHILDREN',
                )->build(),
            $this->user,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_with_children_ids, $artifacts);
    }

    public function testWithChildrenArtifact(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITH CHILDREN ARTIFACT = 123',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testWithChildrenTracker(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITH CHILDREN TRACKER = "epic"',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testWithoutChildren(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT CHILDREN',
                )->build(),
            $this->user,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing(array_diff($this->epics_ids, $this->epics_with_children_ids), $artifacts);
    }

    public function testWithoutChildrenArtifact(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT CHILDREN ARTIFACT = 123',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testWithoutChildrenTracker(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE WITHOUT CHILDREN TRACKER = "epic"',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testIsLinkedToWithType(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_with_children_ids, $artifacts);
    }

    public function testIsLinkedToArtifact(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO ARTIFACT = 123 WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testIsLinkedToTracker(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO TRACKER = "epic" WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertEmpty($artifacts);
    }

    public function testItLinkedToNotTracker(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO TRACKER != "epic" WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_with_children_ids, $artifacts);
    }

    public function testIsLinkedTo(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS LINKED TO',
                )->build(),
            $this->user,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_with_children_ids, $artifacts);
    }

    public function testIsNotLinkedToWithType(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing(array_diff($this->epics_ids, $this->epics_with_children_ids), $artifacts);
    }

    public function testIsNotLinkedToArtifact(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO ARTIFACT = 123 WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testIsNotLinkedToTracker(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO TRACKER = "epic" WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing($this->epics_ids, $artifacts);
    }

    public function testIsNotLinkedToNotTracker(): void
    {
        $this->expectException(SearchablesAreInvalidException::class);
        $this->expectExceptionMessage('Double negative like `IS NOT LINKED ... TRACKER != ...` or `WITHOUT ... TRACKER != ...` is not supported. Please use simpler form like `IS LINKED ... TRACKER = ...` or `WITH ... TRACKER = ...`');
        $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO TRACKER != "epic" WITH TYPE "_is_child"',
                )->build(),
            $this->user,
        );
    }

    public function testIsNotLinkedTo(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @id FROM @project = "self" AND @tracker.name = "epic" WHERE IS NOT LINKED TO',
                )->build(),
            $this->user,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing(array_diff($this->epics_ids, $this->epics_with_children_ids), $artifacts);
    }
}
