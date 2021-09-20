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

use Project;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureRepresentationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureChecker;
use Tuleap\ProgramManagement\Adapter\Program\Feature\VerifyIsVisibleFeatureAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\ContentStore;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\ProgramSearcher;
use Tuleap\ProgramManagement\Domain\Program\SearchProgram;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class ProgramIncrementContentRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FeatureContentRetriever $retriever;
    private \PFUser $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ContentStore
     */
    private $content_store;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BackgroundColorRetriever
     */
    private $retrieve_background;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ArtifactsLinkedToParentDao
     */
    private $parent_dao;

    protected function setUp(): void
    {
        $this->content_store        = $this->createMock(ContentStore::class);
        $retrieve_program_increment = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $this->artifact_factory     = $this->createMock(Tracker_ArtifactFactory::class);
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->retrieve_background  = $this->createMock(BackgroundColorRetriever::class);
        $this->parent_dao           = $this->createMock(ArtifactsLinkedToParentDao::class);
        $this->user                 = UserTestBuilder::aUser()->build();
        $retrieve_user              = RetrieveUserStub::withUser($this->user);

        $this->retriever = new FeatureContentRetriever(
            $retrieve_program_increment,
            $this->content_store,
            new FeatureRepresentationBuilder(
                $this->artifact_factory,
                $this->form_element_factory,
                $this->retrieve_background,
                new VerifyIsVisibleFeatureAdapter($this->artifact_factory, $retrieve_user),
                new UserStoryLinkedToFeatureChecker(
                    $this->parent_dao,
                    $this->createMock(BuildPlanning::class),
                    $this->artifact_factory
                ),
                $retrieve_user
            ),
            new ProgramSearcher($this->getStubSearchProgram(), BuildProgramStub::stubValidProgram()),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts()
        );
    }

    public function testItBuildsACollectionOfOpenedElements(): void
    {
        $this->content_store->method('searchContent')->willReturn(
            [
                [
                    'tracker_name'   => 'User stories',
                    'artifact_id'    => 1,
                    'artifact_title' => 'Artifact 1',
                    'field_title_id' => 1
                ],
                [
                    'tracker_name'   => 'Features',
                    'artifact_id'    => 2,
                    'artifact_title' => 'Artifact 2',
                    'field_title_id' => 1
                ],
            ]
        );

        $field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->method('getFieldById')->with(1)->willReturn($field);
        $field->method('userCanRead')->willReturn(true);

        $project      = ProjectTestBuilder::aProject()->withId(101)->withPublicName('My project')->build();
        $tracker_one  = $this->buildTracker(1, 'bug', $project);
        $artifact_one = $this->buildArtifact(1, $tracker_one);
        $tracker_two  = $this->buildTracker(2, 'user stories', $project);
        $artifact_two = $this->buildArtifact(2, $tracker_two);

        $this->artifact_factory->method('getArtifactById')->willReturnMap([
            [1, $artifact_one],
            [2, $artifact_two],
        ]);

        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturnMap([
            [$this->user, 1, $artifact_one],
            [$this->user, 2, $artifact_two],
        ]);

        $this->retrieve_background->method('retrieveBackgroundColor')
            ->willReturn(new BackgroundColor("lake-placid-blue"));

        $this->parent_dao->method('getPlannedUserStory')->willReturn([]);
        $this->parent_dao->expects(self::exactly(2))->method('getChildrenOfFeatureInTeamProjects')->willReturn([]);

        $collection = [
            new FeatureRepresentation(
                1,
                'Artifact 1',
                'bug #1',
                '/plugins/tracker/?aid=1',
                MinimalTrackerRepresentation::build($tracker_one),
                new BackgroundColor("lake-placid-blue"),
                false,
                false
            ),
            new FeatureRepresentation(
                2,
                'Artifact 2',
                'user stories #2',
                '/plugins/tracker/?aid=2',
                MinimalTrackerRepresentation::build($tracker_two),
                new BackgroundColor("lake-placid-blue"),
                false,
                false
            ),
        ];

        self::assertEquals($collection, $this->retriever->retrieveProgramIncrementContent(202, UserIdentifierStub::buildGenericUser()));
    }

    private function getStubSearchProgram(): SearchProgram
    {
        return new class implements SearchProgram {
            public function searchProgramOfProgramIncrement(int $program_increment_id): ?int
            {
                return 101;
            }
        };
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

    private function buildArtifact(int $artifact_id, \Tracker $tracker): Artifact
    {
        $artifact = new Artifact($artifact_id, $tracker->getId(), 110, 1234567890, false);
        $artifact->setTracker($tracker);
        return $artifact;
    }
}
