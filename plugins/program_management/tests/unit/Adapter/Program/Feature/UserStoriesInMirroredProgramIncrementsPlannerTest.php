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

namespace Adapter\Program\Feature;

use Monolog\Test\TestCase;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoriesInMirroredProgramIncrementsPlanner;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FieldData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUnlinkedUserStoriesOfMirroredProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class UserStoriesInMirroredProgramIncrementsPlannerTest extends TestCase
{
    private const MIRRORED_TIMEBOX_ID    = 666;
    private const USER_STORY_ID          = 1234;
    private const FEATURE_ID             = 101;
    private const TEAM_ID                = 172;
    private const ARTIFACT_LINK_FIELD_ID = 1;
    /**
     * @var Stub&ContentDao
     */
    private $content_dao;
    /**
     * @var Stub&Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;
    /**
     * @var Stub&ArtifactsLinkedToParentDao
     */
    private $artifacts_linked_dao;
    private ProgramIncrementChanged $program_increment_changed;

    protected function setUp(): void
    {
        $this->artifacts_linked_dao     = $this->createStub(ArtifactsLinkedToParentDao::class);
        $this->tracker_artifact_factory = $this->createStub(Tracker_ArtifactFactory::class);
        $this->content_dao              = $this->createStub(ContentDao::class);

        $update                          = ProgramIncrementUpdateBuilder::build();
        $this->program_increment_changed = ProgramIncrementChanged::fromUpdate($update);
    }

    private function getPlanner(): UserStoriesInMirroredProgramIncrementsPlanner
    {
        $pfuser = UserTestBuilder::aUser()->build();
        return new UserStoriesInMirroredProgramIncrementsPlanner(
            new DBTransactionExecutorPassthrough(),
            $this->artifacts_linked_dao,
            $this->tracker_artifact_factory,
            SearchMirroredTimeboxesStub::withIds(self::MIRRORED_TIMEBOX_ID),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            $this->content_dao,
            new NullLogger(),
            RetrieveUserStub::withUser($pfuser),
            RetrieveUnlinkedUserStoriesOfMirroredProgramIncrementStub::buildEmptyUserStories()
        );
    }

    public function testItAddLinksToMirroredMilestones(): void
    {
        $raw_link = ['id' => self::USER_STORY_ID, 'project_id' => self::TEAM_ID];
        $this->content_dao->method('searchContent')->willReturn([['artifact_id' => self::FEATURE_ID]]);
        $this->artifacts_linked_dao->method('getArtifactsLinkedToId')->willReturn([$raw_link]);

        $milestone = $this->createMock(Artifact::class);
        $milestone->method('getId')->willReturn(self::MIRRORED_TIMEBOX_ID);
        $team_project = ProjectTestBuilder::aProject()->withId(self::TEAM_ID)->build();
        $milestone->method('getTracker')->willReturn(
            TrackerTestBuilder::aTracker()->withProject($team_project)->build()
        );
        $field_artifact_link = $this->createStub(Tracker_FormElement_Field_ArtifactLink::class);
        $field_artifact_link->method('getId')->willReturn(self::ARTIFACT_LINK_FIELD_ID);
        $milestone->method('getAnArtifactLinkField')->willReturn($field_artifact_link);
        $this->tracker_artifact_factory->method('getArtifactById')->willReturn($milestone);

        $this->artifacts_linked_dao->method('getUserStoriesOfMirroredProgramIncrementThatAreNotLinkedToASprint')
            ->willReturn([['id' => self::USER_STORY_ID]]);

        $fields_data = new FieldData(
            [FeatureChange::fromRaw($raw_link)],
            [],
            self::ARTIFACT_LINK_FIELD_ID
        );

        $milestone->expects(self::once())
            ->method('createNewChangeset')->with(
                $fields_data->getFieldDataForChangesetCreationFormat(self::TEAM_ID),
                '',
                self::isInstanceOf(\PFUser::class)
            );

        $this->getPlanner()->plan($this->program_increment_changed);
    }

    public function testItDoesNothingWhenArtifactLinkIsNotFound(): void
    {
        $this->content_dao->method('searchContent')->willReturn([['artifact_id' => self::FEATURE_ID]]);
        $this->artifacts_linked_dao->method('getArtifactsLinkedToId')
            ->willReturn([['id' => self::USER_STORY_ID, 'project_id' => self::TEAM_ID]]);

        $milestone = $this->createMock(Artifact::class);
        $milestone->method('getAnArtifactLinkField')->willReturn(null);

        $this->tracker_artifact_factory->method('getArtifactById')->willReturn($milestone);

        $milestone->expects(self::never())->method('createNewChangeset');
        $this->getPlanner()->plan($this->program_increment_changed);
    }

    public function testItDoesNothingWhenMilestoneIsNotFound(): void
    {
        $this->content_dao->method('searchContent')->willReturn([['artifact_id' => self::FEATURE_ID]]);
        $this->artifacts_linked_dao->method('getArtifactsLinkedToId')
            ->willReturn([['id' => self::USER_STORY_ID, 'project_id' => self::TEAM_ID]]);

        $this->tracker_artifact_factory->method('getArtifactById')->willReturn(null);

        $this->expectNotToPerformAssertions();
        $this->getPlanner()->plan($this->program_increment_changed);
    }

    public function testItDoesNotAddUserStoryIfUserStoryIsNotInProject(): void
    {
        $other_project_id = 122;
        $this->content_dao->method('searchContent')->willReturn([['artifact_id' => self::FEATURE_ID]]);
        $this->artifacts_linked_dao->method('getArtifactsLinkedToId')
            ->willReturn([['id' => self::USER_STORY_ID, 'project_id' => $other_project_id]]);

        $milestone = $this->createMock(Artifact::class);
        $milestone->method('getId')->willReturn(self::MIRRORED_TIMEBOX_ID);
        $team_project = ProjectTestBuilder::aProject()->withId(self::TEAM_ID)->build();
        $milestone->method('getTracker')->willReturn(
            TrackerTestBuilder::aTracker()->withProject($team_project)->build()
        );
        $milestone->method('getAnArtifactLinkField')->willReturn($this->getArtifactLinkField());
        $this->tracker_artifact_factory->method('getArtifactById')->willReturn($milestone);

        $this->artifacts_linked_dao->method('getUserStoriesOfMirroredProgramIncrementThatAreNotLinkedToASprint')
            ->willReturn([['id' => self::USER_STORY_ID]]);

        $fields_data = new FieldData(
            [],
            [],
            self::ARTIFACT_LINK_FIELD_ID
        );

        $milestone->expects(self::once())
            ->method('createNewChangeset')
            ->with(
                $fields_data->getFieldDataForChangesetCreationFormat($other_project_id),
                '',
                self::isInstanceOf(\PFUser::class)
            );

        $this->getPlanner()->plan($this->program_increment_changed);
    }

    private function getArtifactLinkField(): Tracker_FormElement_Field_ArtifactLink
    {
        return new Tracker_FormElement_Field_ArtifactLink(
            self::ARTIFACT_LINK_FIELD_ID,
            70,
            null,
            'field_artlink',
            'Field ArtLink',
            '',
            1,
            'P',
            true,
            '',
            1
        );
    }
}
