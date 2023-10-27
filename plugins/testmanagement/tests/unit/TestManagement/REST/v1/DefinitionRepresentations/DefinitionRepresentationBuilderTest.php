<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see < http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\REST\v1\RequirementRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class DefinitionRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ConfigConformanceValidator
     */
    private $conformance_validator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RequirementRetriever
     */
    private $requirement_retriever;
    /**
     * @var \Codendi_HTMLPurifier|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $purifier;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ContentInterpretor
     */
    private $interpreter;
    /**
     * @var DefinitionRepresentationBuilder
     */
    private $definition_representation_builder;
    /**
     * @var ArtifactRepresentationBuilder&\PHPUnit\Framework\MockObject\MockObject
     */
    private $artifact_representation_builder;

    protected function setUp(): void
    {
        $this->tracker_form_element_factory    = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->conformance_validator           = Mockery::mock(ConfigConformanceValidator::class);
        $this->requirement_retriever           = Mockery::mock(RequirementRetriever::class);
        $this->purifier                        = Mockery::mock(\Codendi_HTMLPurifier::class);
        $this->interpreter                     = Mockery::mock(ContentInterpretor::class);
        $this->artifact_representation_builder = $this->createMock(ArtifactRepresentationBuilder::class);
        $priority_manager                      = $this->createStub(\Tracker_Artifact_PriorityManager::class);
        $priority_manager->method('getGlobalRank')->willReturn(1);

        $this->definition_representation_builder = new DefinitionRepresentationBuilder(
            $this->tracker_form_element_factory,
            $this->conformance_validator,
            $this->requirement_retriever,
            $this->purifier,
            $this->interpreter,
            $this->artifact_representation_builder,
            $priority_manager,
        );
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Status::clearInstances();

        parent::tearDown();
    }

    public function testItGetsTheTextDefinitionRepresentation(): void
    {
        $user       = UserTestBuilder::aUser()->build();
        $changeset  = Mockery::mock(Tracker_Artifact_Changeset::class);
        $field      = Mockery::mock(\Tracker_FormElement_Field::class);
        $text_field = Mockery::mock(Tracker_Artifact_ChangesetValue_Text::class);

        $tracker_id = 1450;

        $definition_artifact = $this->mockDefinitionArtifact($user, $tracker_id);

        $this->mockChangesetValue($definition_artifact, $changeset, $field, $text_field);

        $this->assertTrackerFormElementFactory($user, $field);

        $text_field->shouldReceive('getText')->andReturn('wololo');
        $text_field->shouldReceive('getValue')->andReturn(Mockery::any());

        $this->purifier->shouldReceive('purifyHTMLWithReferences')->andReturn('wololo');

        $text_field->shouldReceive('getFormat')->andReturn('text');

        $this->mockArtifactRepresentationBuilder($definition_artifact);

        $definition_representation = $this->definition_representation_builder->getDefinitionRepresentation(
            $user,
            $definition_artifact,
            null
        );

        $this->assertInstanceOf(DefinitionTextOrHTMLRepresentation::class, $definition_representation);
    }

    public function testItGetsDefaultEmptyTextRepresentationIfTheDescriptionFieldIsNull(): void
    {
        $user       = UserTestBuilder::aUser()->build();
        $changeset  = Mockery::mock(Tracker_Artifact_Changeset::class);
        $field      = Mockery::mock(\Tracker_FormElement_Field::class);
        $text_field = Mockery::mock(Tracker_Artifact_ChangesetValue_Text::class);

        $tracker_id          = 1450;
        $definition_artifact = $this->mockDefinitionArtifact($user, $tracker_id);

        $this->mockChangesetValue($definition_artifact, $changeset, $field, $text_field);

        $this->tracker_form_element_factory->shouldReceive('getUsedFieldByNameForUser')
            ->with(
                $tracker_id,
                Mockery::any(),
                $user
            )
            ->andReturnNull();
        $this->tracker_form_element_factory->shouldReceive('getSelectboxFieldByNameForUser')->andReturnNull();

        $this->mockArtifactRepresentationBuilder($definition_artifact);

        $definition_representation = $this->definition_representation_builder->getDefinitionRepresentation(
            $user,
            $definition_artifact,
            null
        );

        $this->assertInstanceOf(DefinitionTextOrHTMLRepresentation::class, $definition_representation);
    }

    public function testItGetsTheHTMLDefinitionRepresentation(): void
    {
        $user       = UserTestBuilder::aUser()->build();
        $changeset  = Mockery::mock(Tracker_Artifact_Changeset::class);
        $field      = Mockery::mock(\Tracker_FormElement_Field::class);
        $text_field = Mockery::mock(Tracker_Artifact_ChangesetValue_Text::class);

        $tracker_id = 1450;

        $definition_artifact = $this->mockDefinitionArtifact($user, $tracker_id);

        $this->mockChangesetValue($definition_artifact, $changeset, $field, $text_field);

        $this->assertTrackerFormElementFactory($user, $field);

        $text_field->shouldReceive('getText')->andReturn('wololo');
        $text_field->shouldReceive('getValue')->andReturn(Mockery::any());

        $this->purifier->shouldReceive('purifyHTMLWithReferences')->andReturn('wololo');

        $text_field->shouldReceive('getFormat')->andReturn('html');

        $this->mockArtifactRepresentationBuilder($definition_artifact);

        $definition_representation = $this->definition_representation_builder->getDefinitionRepresentation(
            $user,
            $definition_artifact,
            null
        );

        $this->assertInstanceOf(DefinitionTextOrHTMLRepresentation::class, $definition_representation);
    }

    public function testItGetsTheCommonmarkDefinitionRepresentation(): void
    {
        $user       = UserTestBuilder::aUser()->build();
        $changeset  = Mockery::mock(Tracker_Artifact_Changeset::class);
        $field      = Mockery::mock(\Tracker_FormElement_Field::class);
        $text_field = Mockery::mock(Tracker_Artifact_ChangesetValue_Text::class);

        $tracker_id = 1450;

        $definition_artifact = $this->mockDefinitionArtifact($user, $tracker_id);

        $this->mockChangesetValue($definition_artifact, $changeset, $field, $text_field);

        $this->conformance_validator->shouldReceive('isArtifactADefinition')->with(
            $definition_artifact
        )->andReturnTrue();

        $this->requirement_retriever->shouldReceive('getAllRequirementsForDefinition')
            ->with($definition_artifact, $user)
            ->andReturn([]);

        $this->assertTrackerFormElementFactory($user, $field);

        $text_field->shouldReceive('getText')->andReturn('wololo');
        $text_field->shouldReceive('getValue')->andReturn(Mockery::any());


        $this->purifier->shouldReceive('purifyHTMLWithReferences')->andReturn('wololo');
        $this->purifier->shouldReceive('purify')->andReturn('wololo');

        $this->interpreter->shouldReceive('getInterpretedContentWithReferences')->andReturn('wololo');

        $this->mockArtifactRepresentationBuilder($definition_artifact);

        $text_field->shouldReceive('getFormat')->andReturn('commonmark');
        $definition_representation = $this->definition_representation_builder->getDefinitionRepresentation(
            $user,
            $definition_artifact,
            null
        );

        $this->assertInstanceOf(DefinitionCommonmarkRepresentation::class, $definition_representation);
    }

    public function testItThrowsAnErrorIfTheDescriptionFormatIsInvalid(): void
    {
        $user                = Mockery::mock(PFUser::class);
        $field               = Mockery::mock(\Tracker_FormElement_Field::class);
        $changeset           = Mockery::mock(Tracker_Artifact_Changeset::class);
        $text_field          = Mockery::mock(Tracker_Artifact_ChangesetValue_Text::class);
        $definition_artifact = $this->mockDefinitionArtifact($user, 111);

        $this->mockChangesetValue($definition_artifact, $changeset, $field, $text_field);

        $this->tracker_form_element_factory->shouldReceive('getUsedFieldByNameForUser')
            ->with(
                Mockery::any(),
                Mockery::any(),
                $user
            )
            ->andReturn($field);

        $text_field->shouldReceive('getText')->andReturn('');
        $text_field->shouldReceive('getFormat')->andReturn('wololo');

        $this->mockArtifactRepresentationBuilder($definition_artifact);

        $this->expectException(DefinitionDescriptionFormatNotFoundException::class);

        $this->definition_representation_builder->getDefinitionRepresentation(
            $user,
            $definition_artifact,
            null
        );
    }

    private function assertTrackerFormElementFactory(PFUser $user, \Tracker_FormElement_Field $field): void
    {
        $this->tracker_form_element_factory->shouldReceive('getUsedFieldByNameForUser')
            ->with(
                Mockery::any(),
                Mockery::any(),
                $user
            )
            ->andReturn($field);
        $this->tracker_form_element_factory->shouldReceive('getSelectboxFieldByNameForUser')->andReturnNull();
    }

    private function mockDefinitionArtifact(
        PFUser $user,
        int $tracker_id,
    ): Artifact {
        $definition_artifact = Mockery::mock(Artifact::class);

        $definition_artifact->shouldReceive('getId')->andReturn(1);

        $tracker = TrackerTestBuilder::aTracker()->withId($tracker_id)->withProject(ProjectTestBuilder::aProject()->build())->build();
        $definition_artifact->shouldReceive('getTracker')->andReturn($tracker);
        $definition_artifact->shouldReceive('getTrackerId')->andReturn($tracker_id);
        $definition_artifact->shouldReceive('getStatus')->andReturn("open");

        $this->conformance_validator->shouldReceive('isArtifactADefinition')->with(
            $definition_artifact
        )->andReturnTrue();

        $this->requirement_retriever->shouldReceive('getAllRequirementsForDefinition')
            ->with($definition_artifact, $user)
            ->andReturn([]);

        return $definition_artifact;
    }

    /**
     * @param Mockery\MockInterface | Artifact      $definition_artifact
     */
    private function mockChangesetValue(
        Artifact $definition_artifact,
        Tracker_Artifact_Changeset $changeset,
        \Tracker_FormElement_Field $field,
        Tracker_Artifact_ChangesetValue_Text $text_field,
    ): void {
        $definition_artifact->shouldReceive('getLastChangeset')->andReturn($changeset);
        $definition_artifact->shouldReceive('getValue')->with($field, $changeset)->andReturn($text_field, null);
    }

    private function mockArtifactRepresentationBuilder(Artifact $definition_artifact): void
    {
        $semantic_status = $this->createMock(\Tracker_Semantic_Status::class);
        $semantic_status->method("getColor");
        \Tracker_Semantic_Status::setInstance(
            $semantic_status,
            $definition_artifact->getTracker(),
        );

        $this->artifact_representation_builder->method("getArtifactRepresentation")->willReturn(
            $this->createMock(ArtifactRepresentation::class)
        );
    }
}
