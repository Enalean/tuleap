<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_FormElementFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentation;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\HTMLOrTextCommentRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const ARTIFACT_ID = 756;

    private ArtifactRepresentationBuilder $builder;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private ChangesetRepresentationBuilder&MockObject $changeset_representation_builder;

    public function setUp(): void
    {
        $this->form_element_factory             = $this->createMock(\Tracker_FormElementFactory::class);
        $this->changeset_representation_builder = $this->createMock(ChangesetRepresentationBuilder::class);
        $this->builder                          = new ArtifactRepresentationBuilder(
            $this->form_element_factory,
            $this->createMock(\Tracker_ArtifactFactory::class),
            $this->createMock(TypeDao::class),
            $this->changeset_representation_builder,
            ProvideUserAvatarUrlStub::build(),
        );
    }

    /**
     * @see ArtifactRepresentationTest
     */
    public function testGetArtifactRepresentationReturnsArtifactRepresentationWithoutFields(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $artifact     = $this->buildBasicArtifactMock();
        $submitted_by = UserTestBuilder::aUser()->withId(777)->build();
        $artifact->method('getSubmittedByUser')->willReturn($submitted_by);

        $representation = $this->builder->getArtifactRepresentation($current_user, $artifact, $this->buildStatusValueRepresentation());

        self::assertSame(self::ARTIFACT_ID, $representation->id);
    }

    public function testGetArtifactRepresentationWithFieldValuesWhenThereAreNoFields(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();
        $artifact     = $this->buildBasicArtifactMock();
        $this->form_element_factory->expects($this->once())->method('getUsedFieldsForREST')->willReturn([]);

        $this->builder->getArtifactRepresentationWithFieldValues($current_user, $artifact, self::buildMinimalTrackerRepresentation(), $this->buildStatusValueRepresentation());
    }

    public function testGetArtifactRepresentationWithFieldValuesDoesntIncludeFieldsUserCantRead(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $first_field_user_cant_read = $this->createMock(\Tracker_FormElement_Field::class);
        $first_field_user_cant_read
            ->method('userCanRead')
            ->with($current_user)
            ->willReturn(false);
        $first_field_user_cant_read->expects($this->never())->method('getRESTValue');

        $field_user_can_read = $this->createMock(\Tracker_FormElement_Field::class);
        $field_user_can_read
            ->method('userCanRead')
            ->with($current_user)
            ->willReturn(true);
        $field_user_can_read->expects($this->once())->method('getRESTValue');

        $second_field_user_cant_read = $this->createMock(\Tracker_FormElement_Field::class);
        $second_field_user_cant_read
            ->method('userCanRead')
            ->with($current_user)
            ->willReturn(false);
        $second_field_user_cant_read->expects($this->never())->method('getRESTValue');

        $this->form_element_factory->method('getUsedFieldsForREST')->willReturn(
            [
                $first_field_user_cant_read,
                $field_user_can_read,
                $second_field_user_cant_read,
            ]
        );
        $artifact = $this->buildBasicArtifactMock();

        $this->builder->getArtifactRepresentationWithFieldValues($current_user, $artifact, self::buildMinimalTrackerRepresentation(), $this->buildStatusValueRepresentation());
    }

    public function testGetArtifactRepresentationWithFieldValuesReturnsOnlyForFieldsWithValues(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $first_field  = StringFieldBuilder::aStringField(1001)->withReadPermission($current_user, false)->build();
        $second_field = $this->createMock(\Tracker_FormElement_Field::class);
        $second_field->method('userCanRead')->willReturn(true);
        $second_field->method('getRESTValue')->willReturn('whatever');
        $third_field = StringFieldBuilder::aStringField(1003)->withReadPermission($current_user, false)->build();
        $this->form_element_factory->method('getUsedFieldsForREST')->willReturn(
            [
                $first_field,
                $second_field,
                $third_field,
            ]
        );
        $artifact = $this->buildBasicArtifactMock();

        $representation = $this->builder->getArtifactRepresentationWithFieldValues($current_user, $artifact, self::buildMinimalTrackerRepresentation(), $this->buildStatusValueRepresentation());

        $this->assertEquals(['whatever'], $representation->values);
        $this->assertNull($representation->values_by_field);
    }

    public function testGetArtifactRepresentationWithFieldValuesByFieldValuesReturnsSimpleValues(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $first_field = $this->createMock(\Tracker_FormElement_Field_Integer::class);
        $first_field->method('userCanRead')->willReturn(true);
        $first_field->method('getName')->willReturn('field01');
        $first_field->method('getRESTValue')->willReturn('01');
        $second_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $second_field->method('userCanRead')->willReturn(true);
        $second_field->method('getName')->willReturn('field02');
        $second_field->method('getRESTValue')->willReturn('whatever');
        $third_field = FloatFieldBuilder::aFloatField(1001)->withReadPermission($current_user, false)->build();
        $this->form_element_factory->method('getUsedFieldsForREST')->willReturn(
            [$first_field, $second_field, $third_field]
        );

        $artifact = $this->buildBasicArtifactMock();

        $representation = $this->builder->getArtifactRepresentationWithFieldValuesByFieldValues(
            $current_user,
            $artifact,
            self::buildMinimalTrackerRepresentation(),
            $this->buildStatusValueRepresentation()
        );

        $this->assertNull($representation->values);
        $this->assertEquals(['field01' => '01', 'field02' => 'whatever'], $representation->values_by_field);
    }

    public function testGetArtifactRepresentationWithFieldValuesInBothFormat(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $first_field = $this->createMock(\Tracker_FormElement_Field_Integer::class);
        $first_field->method('userCanRead')->willReturn(true);
        $first_field->method('getName')->willReturn('field01');
        $first_field->method('getRESTValue')->willReturn('01');
        $second_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $second_field->method('userCanRead')->willReturn(true);
        $second_field->method('getName')->willReturn('field02');
        $second_field->method('getRESTValue')->willReturn('whatever');
        $third_field = FloatFieldBuilder::aFloatField(1001)->withReadPermission($current_user, false)->build();
        $this->form_element_factory->method('getUsedFieldsForREST')->willReturn(
            [$first_field, $second_field, $third_field]
        );

        $artifact = $this->buildBasicArtifactMock();

        $representation = $this->builder->getArtifactRepresentationWithFieldValuesInBothFormat(
            $current_user,
            $artifact,
            self::buildMinimalTrackerRepresentation(),
            $this->buildStatusValueRepresentation()
        );

        $this->assertEquals(['01', 'whatever'], $representation->values);
        $this->assertEquals(['field01' => '01', 'field02' => 'whatever'], $representation->values_by_field);
    }

    public function testGetArtifactChangesetsRepresentationReturnsEmptyArrayWhenNoChanges(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();
        $artifact     = $this->buildBasicArtifactMock();
        $artifact->method('getChangesets')->willReturn([]);

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            false
        );

        self::assertSame([], $representation->toArray());
    }

    public function testGetArtifactChangesetsRepresentationBuildsHistoryOutOfChangeset(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();
        $changeset1   = ChangesetTestBuilder::aChangeset(1001)->build();
        $artifact     = $this->buildBasicArtifactMock();
        $artifact->method('getChangesets')->willReturn([$changeset1]);

        $this->changeset_representation_builder->expects($this->once())->method('buildWithFields')
            ->with($changeset1, \Tracker_Artifact_Changeset::FIELDS_ALL, $current_user, null)
            ->willReturn($this->buildChangesetRepresentation());

        $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            false
        )->toArray();
    }

    public function testGetArtifactChangesetsRepresentationDoesntExportEmptyChanges(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();
        $changeset1   = ChangesetTestBuilder::aChangeset(1001)->build();
        $changeset2   = ChangesetTestBuilder::aChangeset(1002)->build();

        $artifact = $this->buildBasicArtifactMock();
        $artifact->method('getChangesets')->willReturn([$changeset1, $changeset2]);

        $changeset_representation1 = $this->buildChangesetRepresentation();
        $this->changeset_representation_builder->expects($this->exactly(2))->method('buildWithFields')
            ->willReturnCallback(static fn (
                \Tracker_Artifact_Changeset $changeset,
                string $filter_mode,
                \PFUser $current_user,
                ?\Tracker_Artifact_Changeset $previous_changeset,
            ) => match (true) {
                $changeset === $changeset1 && $previous_changeset === null => $changeset_representation1,
                $changeset === $changeset2 && $previous_changeset === $changeset1 => null,
            });

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            false
        );

        self::assertEquals([$changeset_representation1], $representation->toArray());
    }

    public function testGetArtifactChangesetsRepresentationPaginatesResults(): void
    {
        $changeset1 = $this->buildChangeset(1001);
        $changeset2 = $this->buildChangeset(1002);

        $artifact = $this->buildBasicArtifactMock();
        $artifact->method('getChangesets')->willReturn([$changeset1, $changeset2]);
        $current_user = UserTestBuilder::buildWithDefaults();

        $first_representation  = $this->buildChangesetRepresentation(1001);
        $second_representation = $this->buildChangesetRepresentation(1002);
        $this->changeset_representation_builder->method('buildWithFields')
            ->willReturnCallback(
                function (\Tracker_Artifact_Changeset $changeset, string $mode, \PFUser $user) use ($first_representation, $second_representation) {
                    return ($changeset->getId() === 1001) ? $first_representation : $second_representation;
                }
            );

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            1,
            10,
            false
        );

        self::assertEquals([$second_representation], $representation->toArray());
        self::assertSame(2, $representation->totalCount());
    }

    public function testGetArtifactChangesetsRepresentationReturnsTheChangesetsInReverseOrder(): void
    {
        $changeset1 = $this->buildChangeset(1001);
        $changeset2 = $this->buildChangeset(1002);

        $artifact = $this->buildBasicArtifactMock();
        $artifact->method('getChangesets')->willReturn([$changeset1, $changeset2]);
        $current_user = UserTestBuilder::buildWithDefaults();

        $first_representation  = $this->buildChangesetRepresentation(1001);
        $second_representation = $this->buildChangesetRepresentation(1002);
        $this->changeset_representation_builder->method('buildWithFields')
            ->willReturnCallback(
                function (\Tracker_Artifact_Changeset $changeset, string $mode, \PFUser $user) use ($first_representation, $second_representation) {
                    return ($changeset->getId() === 1001) ? $first_representation : $second_representation;
                }
            );

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            true
        );

        self::assertEquals([$second_representation, $first_representation], $representation->toArray());
    }

    private function buildTrackerMock(): \Tuleap\Tracker\Tracker
    {
        $project = ProjectTestBuilder::aProject()->withId(1478)->build();

        return TrackerTestBuilder::aTracker()->withProject($project)->withId(888)->build();
    }

    private function buildBasicArtifactMock(): Artifact&MockObject
    {
        $tracker = $this->buildTrackerMock();

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getId')->willReturn(self::ARTIFACT_ID);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getTitle')->willReturn('Title');
        $artifact->method('getLastChangeset')->willReturn(ChangesetTestBuilder::aChangeset(1001)->build());
        $artifact->method('getSubmittedByUser')->willReturn(UserTestBuilder::aUser()->build());
        $artifact->method('getAssignedTo')->willReturn([]);
        $artifact->method('getXRef')->willReturn('art #' . self::ARTIFACT_ID);
        $artifact->method('getSubmittedBy')->willReturn(111);
        $artifact->method('isOpen')->willReturn(true);
        $artifact->method('getSubmittedOn')->willReturn(6546546554);
        $artifact->method('getLastUpdateDate')->willReturn(6546546554);
        $artifact->method('getUri')->willReturn('/plugins/tracker/?aid=' . self::ARTIFACT_ID);

        return $artifact;
    }

    private static function buildMinimalTrackerRepresentation(): MinimalTrackerRepresentation
    {
        return MinimalTrackerRepresentation::build(
            TrackerTestBuilder::aTracker()
                ->withId(859)
                ->withName('tracker name')
                ->withProject(ProjectTestBuilder::aProject()->build())
                ->build()
        );
    }

    private function buildChangesetRepresentation(int $changeset_id = 1001): ChangesetRepresentation
    {
        $comment_representation = new HTMLOrTextCommentRepresentation('Irrelevant', 'Irrelevant', 'text', null);
        return new ChangesetRepresentation(
            $changeset_id,
            110,
            null,
            1234567890,
            null,
            $comment_representation,
            [],
            null,
            1234567890
        );
    }

    private function buildChangeset(int $changeset_id = 1001): \Tracker_Artifact_Changeset
    {
        $changeset = new \Tracker_Artifact_Changeset(
            $changeset_id,
            $this->createMock(Artifact::class),
            110,
            1234567890,
            null
        );
        $comment   = new \Tracker_Artifact_Changeset_Comment(
            201,
            $changeset,
            null,
            null,
            110,
            1234567890,
            'A text comment',
            'text',
            0,
            []
        );
        $changeset->setLatestComment($comment);
        return $changeset;
    }

    private function buildStatusValueRepresentation(): StatusValueRepresentation
    {
        return StatusValueRepresentation::buildFromValues('On going', 'flamingo-pink');
    }
}
