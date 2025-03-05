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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\REST\v1\RequirementRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DefinitionRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&\Tracker_FormElementFactory $tracker_form_element_factory;
    private MockObject&ConfigConformanceValidator $conformance_validator;
    private MockObject&RequirementRetriever $requirement_retriever;
    private MockObject&\Codendi_HTMLPurifier $purifier;
    private MockObject&ContentInterpretor $interpreter;
    private DefinitionRepresentationBuilder $definition_representation_builder;
    private MockObject&ArtifactRepresentationBuilder $artifact_representation_builder;

    protected function setUp(): void
    {
        $this->tracker_form_element_factory    = $this->createMock(\Tracker_FormElementFactory::class);
        $this->conformance_validator           = $this->createMock(ConfigConformanceValidator::class);
        $this->requirement_retriever           = $this->createMock(RequirementRetriever::class);
        $this->purifier                        = $this->createMock(\Codendi_HTMLPurifier::class);
        $this->interpreter                     = $this->createMock(ContentInterpretor::class);
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
            ProvideUserAvatarUrlStub::build(),
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
        $changeset  = $this->createMock(Tracker_Artifact_Changeset::class);
        $field      = $this->createMock(\Tracker_FormElement_Field::class);
        $text_field = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);

        $tracker_id = 1450;

        $definition_artifact = $this->mockDefinitionArtifact($user, $tracker_id);

        $this->mockChangesetValue($definition_artifact, $changeset, $text_field);

        $this->assertTrackerFormElementFactory($user, $field);

        $text_field->method('getText')->willReturn('wololo');
        $text_field->method('getValue')->willReturn(self::any());

        $this->purifier->method('purifyHTMLWithReferences')->willReturn('wololo');

        $text_field->method('getFormat')->willReturn('text');

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
        $user = UserTestBuilder::aUser()->build();

        $tracker_id          = 1450;
        $definition_artifact = $this->mockDefinitionArtifact($user, $tracker_id);

        $this->tracker_form_element_factory->method('getUsedFieldByNameForUser')
            ->willReturnCallback(
                static fn(int $called_tracker_id, string $field_name, PFUser $called_user) => match (true) {
                    $called_user === $user && $called_tracker_id === $tracker_id => null
                }
            );
        $this->tracker_form_element_factory->method('getSelectboxFieldByNameForUser')->willReturn(null);

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
        $changeset  = $this->createMock(Tracker_Artifact_Changeset::class);
        $field      = $this->createMock(\Tracker_FormElement_Field::class);
        $text_field = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);

        $tracker_id = 1450;

        $definition_artifact = $this->mockDefinitionArtifact($user, $tracker_id);

        $this->mockChangesetValue($definition_artifact, $changeset, $text_field);

        $this->assertTrackerFormElementFactory($user, $field);

        $text_field->method('getText')->willReturn('wololo');
        $text_field->method('getValue')->willReturn(self::any());

        $this->purifier->method('purifyHTMLWithReferences')->willReturn('wololo');

        $text_field->method('getFormat')->willReturn('html');

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
        $changeset  = $this->createMock(Tracker_Artifact_Changeset::class);
        $field      = $this->createMock(\Tracker_FormElement_Field::class);
        $text_field = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);

        $tracker_id = 1450;

        $definition_artifact = $this->mockDefinitionArtifact($user, $tracker_id);

        $this->mockChangesetValue($definition_artifact, $changeset, $text_field);

        $this->conformance_validator->method('isArtifactADefinition')->with(
            $definition_artifact
        )->willReturn(true);

        $this->requirement_retriever->method('getAllRequirementsForDefinition')
            ->with($definition_artifact, $user)
            ->willReturn([]);

        $this->assertTrackerFormElementFactory($user, $field);

        $text_field->method('getText')->willReturn('wololo');
        $text_field->method('getValue')->willReturn('');


        $this->purifier->method('purifyHTMLWithReferences')->willReturn('wololo');
        $this->purifier->method('purify')->willReturn('wololo');

        $this->interpreter->method('getInterpretedContentWithReferences')->willReturn('wololo');

        $this->mockArtifactRepresentationBuilder($definition_artifact);

        $text_field->method('getFormat')->willReturn('commonmark');
        $definition_representation = $this->definition_representation_builder->getDefinitionRepresentation(
            $user,
            $definition_artifact,
            null
        );

        $this->assertInstanceOf(DefinitionCommonmarkRepresentation::class, $definition_representation);
    }

    public function testItThrowsAnErrorIfTheDescriptionFormatIsInvalid(): void
    {
        $user                = $this->createMock(PFUser::class);
        $field               = $this->createMock(\Tracker_FormElement_Field::class);
        $changeset           = $this->createMock(Tracker_Artifact_Changeset::class);
        $text_field          = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);
        $definition_artifact = $this->mockDefinitionArtifact($user, 111);

        $this->mockChangesetValue($definition_artifact, $changeset, $text_field);

        $this->assertTrackerFormElementFactory($user, $field);

        $text_field->method('getText')->willReturn('');
        $text_field->method('getFormat')->willReturn('wololo');

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
        $this->tracker_form_element_factory->method('getUsedFieldByNameForUser')
            ->willReturnCallback(
                static fn(int $tracker_id, string $field_name, PFUser $called_user) => match ($called_user) {
                    $user => $field
                }
            );
        $this->tracker_form_element_factory->method('getSelectboxFieldByNameForUser')->willReturn(null);
    }

    private function mockDefinitionArtifact(
        PFUser $user,
        int $tracker_id,
    ): Artifact {
        $definition_artifact = $this->createMock(Artifact::class);

        $definition_artifact->method('getId')->willReturn(1);

        $tracker = TrackerTestBuilder::aTracker()->withId($tracker_id)->withProject(ProjectTestBuilder::aProject()->build())->build();
        $definition_artifact->method('getTracker')->willReturn($tracker);
        $definition_artifact->method('getTrackerId')->willReturn($tracker_id);
        $definition_artifact->method('getStatus')->willReturn('open');

        $definition_artifact->method('getLastChangeset')->willReturn(null);

        $this->conformance_validator->method('isArtifactADefinition')->with(
            $definition_artifact
        )->willReturn(true);

        $this->requirement_retriever->method('getAllRequirementsForDefinition')
            ->with($definition_artifact, $user)
            ->willReturn([]);

        return $definition_artifact;
    }

    private function mockChangesetValue(
        MockObject&Artifact $definition_artifact,
        Tracker_Artifact_Changeset $expected_changeset,
        Tracker_Artifact_ChangesetValue $changeset_value,
    ): void {
        $definition_artifact->method('getLastChangeset')->willReturn($expected_changeset);
        $matcher = $this->atLeastOnce();
        $definition_artifact->expects($matcher)->method('getValue')->willReturnCallback(
            function () use ($matcher, $changeset_value): ?Tracker_Artifact_ChangesetValue {
                if ($matcher->numberOfInvocations() === 1) {
                    return $changeset_value;
                }
                return null;
            }
        );
    }

    private function mockArtifactRepresentationBuilder(Artifact $definition_artifact): void
    {
        $semantic_status = $this->createMock(\Tracker_Semantic_Status::class);
        $semantic_status->method('getColor');
        \Tracker_Semantic_Status::setInstance(
            $semantic_status,
            $definition_artifact->getTracker(),
        );

        $this->artifact_representation_builder->method('getArtifactRepresentation')->willReturn(
            $this->createMock(ArtifactRepresentation::class)
        );
    }
}
