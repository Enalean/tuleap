<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use PHPUnit\Framework\MockObject\Stub;
use Project;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureRepresentationBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchFeaturesStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureHasAtLeastOneUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleByProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class FeatureContentRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID      = 202;
    private const BUG_ARTIFACT_ID           = 689;
    private const BUG_TITLE                 = 'alkalescent';
    private const USER_STORY_ID             = 337;
    private const USER_STORT_TITLE          = 'tracklessly';
    private const BUG_TRACKER_ID            = 32;
    private const USER_STORY_TRACKER_ID     = 34;
    private const BUG_TITLE_FIELD_ID        = 112;
    private const USER_STORY_TITLE_FIELD_ID = 798;
    /**
     * @var Stub&\Tracker_FormElementFactory
     */
    private $form_element_factory;
    private UserIdentifier $user;
    private RetrieveFullArtifactStub $artifact_retriever;

    protected function setUp(): void
    {
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);

        $this->user = UserIdentifierStub::buildGenericUser();
    }

    private function getRetriever(): FeatureContentRetriever
    {
        $pfuser_with_read_all_permission = new \Tracker_UserWithReadAllPermission(UserTestBuilder::aUser()->build());
        return new FeatureContentRetriever(
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            SearchFeaturesStub::withRows([
                [
                    'tracker_name'   => 'Irrelevant',
                    'artifact_id'    => self::BUG_ARTIFACT_ID,
                    'field_title_id' => self::BUG_TITLE_FIELD_ID,
                    'artifact_title' => self::BUG_TITLE,
                ],
                [
                    'tracker_name'   => 'Irrelevant',
                    'artifact_id'    => self::USER_STORY_ID,
                    'field_title_id' => self::USER_STORY_TITLE_FIELD_ID,
                    'artifact_title' => self::USER_STORT_TITLE,
                ],
            ]),
            new FeatureRepresentationBuilder(
                $this->artifact_retriever,
                $this->form_element_factory,
                RetrieveBackgroundColorStub::withDefaults(),
                VerifyFeatureIsVisibleByProgramStub::buildVisibleFeature(),
                VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
                VerifyFeatureHasAtLeastOneUserStoryStub::withoutStories(),
                RetrieveUserStub::withUser($pfuser_with_read_all_permission)
            ),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            RetrieveProgramOfProgramIncrementStub::withProgram(170),
            BuildProgramStub::stubValidProgram()
        );
    }

    public function testItBuildsACollectionOfOpenedElements(): void
    {
        $team_project       = ProjectTestBuilder::aProject()
            ->withId(153)
            ->withPublicName('Blue Team')
            ->build();
        $bug_tracker        = $this->buildTracker(self::BUG_TRACKER_ID, 'bug', $team_project);
        $user_story_tracker = $this->buildTracker(self::USER_STORY_TRACKER_ID, 'user stories', $team_project);

        $bug_artifact             = $this->buildArtifact(self::BUG_ARTIFACT_ID, self::BUG_TITLE, $bug_tracker);
        $user_story_artifact      = $this->buildArtifact(
            self::USER_STORY_ID,
            self::USER_STORT_TITLE,
            $user_story_tracker
        );
        $this->artifact_retriever = RetrieveFullArtifactStub::withSuccessiveArtifacts(
            $bug_artifact,
            $user_story_artifact
        );

        $first_title_field  = $this->getStringField(
            self::BUG_TITLE_FIELD_ID,
            self::BUG_TITLE,
            self::BUG_TRACKER_ID
        );
        $second_title_field = $this->getStringField(
            self::USER_STORY_TITLE_FIELD_ID,
            self::USER_STORT_TITLE,
            self::USER_STORY_TRACKER_ID
        );
        $this->form_element_factory->method('getFieldById')
            ->willReturnOnConsecutiveCalls($first_title_field, $second_title_field);

        $collection = [
            new FeatureRepresentation(
                self::BUG_ARTIFACT_ID,
                self::BUG_TITLE,
                'bug #' . self::BUG_ARTIFACT_ID,
                '/plugins/tracker/?aid=' . self::BUG_ARTIFACT_ID,
                MinimalTrackerRepresentation::build($bug_tracker),
                new BackgroundColor('lake-placid-blue'),
                false,
                false
            ),
            new FeatureRepresentation(
                self::USER_STORY_ID,
                self::USER_STORT_TITLE,
                'user stories #' . self::USER_STORY_ID,
                '/plugins/tracker/?aid=' . self::USER_STORY_ID,
                MinimalTrackerRepresentation::build($user_story_tracker),
                new BackgroundColor('lake-placid-blue'),
                false,
                false
            ),
        ];

        self::assertEquals(
            $collection,
            $this->getRetriever()->retrieveProgramIncrementContent(self::PROGRAM_INCREMENT_ID, $this->user)
        );
    }

    private function buildTracker(int $tracker_id, string $name, Project $project): \Tracker
    {
        return TrackerTestBuilder::aTracker()
            ->withId($tracker_id)
            ->withName($name)
            ->withColor(TrackerColor::fromName('deep-blue'))
            ->withProject($project)
            ->build();
    }

    private function buildArtifact(int $artifact_id, string $title, \Tracker $tracker): Artifact
    {
        return ArtifactTestBuilder::anArtifact($artifact_id)
            ->withTitle($title)
            ->inTracker($tracker)
            ->build();
    }

    private function getStringField(int $id, string $label, int $tracker_id): \Tracker_FormElement_Field_String
    {
        return new \Tracker_FormElement_Field_String(
            $id,
            $tracker_id,
            1,
            'irrelevant',
            $label,
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }
}
