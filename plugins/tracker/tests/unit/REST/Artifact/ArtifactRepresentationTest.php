<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\Color\ItemColor;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const ARTIFACT_ID               = 56;
    private const EXPECTED_CROSS_REFERENCE  = 'story #' . self::ARTIFACT_ID;
    private const SUBMITTER_ID              = 158;
    private const SUBMITTER_NAME            = 'bpaola';
    private const SUBMITTED_ON_TIMESTAMP    = 1511784926; // 2017-11-27T13:15:26+01:00
    private const LAST_UPDATED_ON_TIMESTAMP = 1791688253; // 2026-10-11T05:10:53+02:00
    private const STATUS                    = 'In review';
    private const TITLE                     = 'draperied iodoform';
    private const TRACKER_ID                = 39;
    private const ASSIGNED_ID               = 164;
    private const ASSIGNED_NAME             = 'celreda';
    private const PROJECT_ID                = 135;
    private const EXPECTED_HTML_URI         = '/plugins/tracker/?aid=' . self::ARTIFACT_ID;
    private const EXPECTED_REST_URI         = ArtifactRepresentation::ROUTE . '/' . self::ARTIFACT_ID;
    private const EXPECTED_CHANGESETS_URI   = ArtifactRepresentation::ROUTE . '/' . self::ARTIFACT_ID . '/' . ChangesetRepresentation::ROUTE;
    private const EXPECTED_COLOR            = 'flamingo-pink';

    private function getRepresentationWithoutFields(): ArtifactRepresentation
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->build();
        $tracker = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withName('User Stories')
            ->withColor(ItemColor::fromName('plum-crazy'))
            ->withProject($project)
            ->build();

        $submitter_user = UserTestBuilder::aUser()
            ->withId(self::SUBMITTER_ID)
            ->withUserName(self::SUBMITTER_NAME)
            ->withRealName('Bernetta Paola')
            ->build();

        $assigned_user = UserTestBuilder::aUser()
            ->withId(self::ASSIGNED_ID)
            ->withUserName(self::ASSIGNED_NAME)
            ->withRealName('Curt Elreda')
            ->build();

        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn(self::ARTIFACT_ID);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getAssignedTo')->willReturn([$assigned_user]);
        $artifact->method('getXRef')->willReturn(self::EXPECTED_CROSS_REFERENCE);
        $artifact->method('getSubmittedBy')->willReturn(self::SUBMITTER_ID);
        $artifact->method('getSubmittedByUser')->willReturn($submitter_user);
        $artifact->method('getSubmittedOn')->willReturn(self::SUBMITTED_ON_TIMESTAMP);
        $artifact->method('getUri')->willReturn(self::EXPECTED_HTML_URI);
        $artifact->method('getLastUpdateDate')->willReturn(self::LAST_UPDATED_ON_TIMESTAMP);
        $artifact->method('getStatus')->willReturn(self::STATUS);
        $artifact->method('isOpen')->willReturn(true);
        $artifact->method('getTitle')->willReturn(self::TITLE);

        $tracker_representation      = MinimalTrackerRepresentation::build($tracker);
        $value_status_representation = StatusValueRepresentation::buildFromValues(self::STATUS, self::EXPECTED_COLOR);
        return ArtifactRepresentation::build($user, $artifact, null, null, $tracker_representation, $value_status_representation, ProvideUserAvatarUrlStub::build());
    }

    public function testItBuildsFromArtifact(): void
    {
        $representation = $this->getRepresentationWithoutFields();
        self::assertSame(self::ARTIFACT_ID, $representation->id);
        self::assertSame(ArtifactRepresentation::ROUTE . '/' . self::ARTIFACT_ID, $representation->uri);
        self::assertSame(self::EXPECTED_CROSS_REFERENCE, $representation->xref);
        self::assertSame(self::SUBMITTER_ID, $representation->submitted_by);
        self::assertSame(self::SUBMITTER_NAME, $representation->submitted_by_user->username);
        self::assertStringContainsString('2017', $representation->submitted_on);
        self::assertSame(self::EXPECTED_HTML_URI, $representation->html_url);
        self::assertSame(self::EXPECTED_REST_URI, $representation->uri);
        self::assertSame(self::EXPECTED_CHANGESETS_URI, $representation->changesets_uri);
        self::assertStringContainsString('2026', $representation->last_modified_date);
        self::assertSame(self::STATUS, $representation->status);
        self::assertTrue($representation->is_open);
        self::assertSame(self::TITLE, $representation->title);
        self::assertSame(self::TRACKER_ID, $representation->tracker->id);
        self::assertSame(self::PROJECT_ID, $representation->project->id);
        [$first_assignee] = $representation->assignees;
        self::assertSame(self::ASSIGNED_ID, $first_assignee->id);
        self::assertSame(self::EXPECTED_COLOR, $representation->full_status->color);
    }
}
