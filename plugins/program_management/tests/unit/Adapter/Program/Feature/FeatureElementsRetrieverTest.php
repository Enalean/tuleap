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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Project;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchPlannableFeatures;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class FeatureElementsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SearchPlannableFeatures
     */
    private $features_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_FormElementFactory
     */
    private $form_element_factory;
    private UserIdentifier $user;
    private RetrieveFullArtifactStub $artifact_retriever;

    protected function setUp(): void
    {
        $this->features_dao         = $this->createMock(SearchPlannableFeatures::class);
        $this->artifact_factory     = $this->createMock(Tracker_ArtifactFactory::class);
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->user                 = UserIdentifierStub::buildGenericUser();
    }

    private function getRetriever(): FeatureElementsRetriever
    {
        return new FeatureElementsRetriever(
            BuildProgramStub::stubValidProgram(),
            $this->features_dao,
            new FeatureRepresentationBuilder(
                $this->artifact_retriever,
                $this->form_element_factory,
                RetrieveBackgroundColorStub::withDefaults(),
                VerifyIsVisibleFeatureStub::buildVisibleFeature(),
                VerifyLinkedUserStoryIsNotPlannedStub::buildNotLinkedStories(),
                RetrieveUserStub::withGenericUser()
            )
        );
    }

    public function testItBuildsACollectionOfOpenedElements(): void
    {
        $this->features_dao->method('searchPlannableFeatures')->willReturn(
            [
                ['tracker_name' => 'User stories', 'artifact_id' => 1, 'artifact_title' => 'Artifact 1', 'field_title_id' => 1],
                ['tracker_name' => 'Features', 'artifact_id' => 2, 'artifact_title' => 'Artifact 2', 'field_title_id' => 1],
            ]
        );

        $field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->method('getFieldById')->with(1)->willReturn($field);
        $field->method('userCanRead')->willReturn(true);

        $project      = ProjectTestBuilder::aProject()->withId(202)->withPublicName('My project')->build();
        $tracker_one  = $this->buildTracker(1, 'bug', $project);
        $artifact_one = $this->buildArtifact(1, $tracker_one);
        $tracker_two  = $this->buildTracker(2, 'user stories', $project);
        $artifact_two = $this->buildArtifact(2, $tracker_two);

        $this->artifact_retriever = RetrieveFullArtifactStub::withSuccessiveArtifacts($artifact_one, $artifact_two);

        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturnMap([
            [$this->user, 1, $artifact_one],
            [$this->user, 2, $artifact_two],
        ]);

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

        self::assertEquals($collection, $this->getRetriever()->retrieveFeaturesToBePlanned(202, $this->user));
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
