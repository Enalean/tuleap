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

namespace Tuleap\CrossTracker\Query\Advanced\Select;

use Codendi_HTMLPurifier;
use PFUser;
use ProjectUGroup;
use Tuleap\CrossTracker\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\TextResultRepresentation;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TitleSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $user;
    /**
     * @var array<int, string>
     */
    private array $expected_results;

    #[\Override]
    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);
        $this->uuid = $this->addWidgetToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_text_field_id = $tracker_builder->buildTextField(
            $release_tracker->getId(),
            'text_field',
        );
        $tracker_builder->buildTitleSemantic($release_tracker->getId(), $release_text_field_id);
        $sprint_text_field_id = $tracker_builder->buildTextField(
            $sprint_tracker->getId(),
            'text_field',
        );
        $tracker_builder->buildTitleSemantic($sprint_tracker->getId(), $sprint_text_field_id);

        $tracker_builder->grantReadPermissionOnField(
            $release_text_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_text_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_with_text_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_with_text_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $release_artifact_with_text_changeset = $tracker_builder->buildLastChangeset($release_artifact_with_text_id);
        $sprint_artifact_with_text_changeset  = $tracker_builder->buildLastChangeset($sprint_artifact_with_text_id);

        $commonmark_interpreter = CommonMarkInterpreter::build(Codendi_HTMLPurifier::instance());

        $this->expected_results = [
            $release_artifact_with_text_id => 'Hello World!',
            $sprint_artifact_with_text_id  => $commonmark_interpreter->getInterpretedContentWithReferences('**Title**', $project_id),
        ];
        $tracker_builder->buildTextValue(
            $release_artifact_with_text_changeset,
            $release_text_field_id,
            $this->expected_results[$release_artifact_with_text_id],
            'text'
        );
        $tracker_builder->buildTextValue(
            $sprint_artifact_with_text_changeset,
            $sprint_text_field_id,
            '**Title**',
            'commonmark'
        );
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @title FROM @project = 'self' WHERE @title = '' OR @title != ''",
                )->build(),
            $this->user,
        );
        self::assertSame(2, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('@title', $result->selected[1]->name);
        self::assertSame('text', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@title', $artifact);
            $value = $artifact['@title'];
            self::assertInstanceOf(TextResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(
            array_values($this->expected_results),
            $values
        );
    }
}
