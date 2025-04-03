<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ValidateArtifactLinkValueEvent;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ManualActionContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\SystemActionContext;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkValidatorTest extends TestCase
{
    use GlobalResponseMock;

    private TypePresenterFactory&MockObject $type_presenter_factory;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private ArtifactLinkValidator $artifact_link_validator;
    private Artifact&MockObject $artifact;
    private ArtifactLinkField $field;
    private Artifact $linked_artifact;
    private Tracker&MockObject $tracker;
    private TypeIsChildPresenter $type_is_child;
    private TypePresenter $type_fixed_in;
    private TypePresenter $type_no_type;
    private Project $project;
    private ArtifactLinksUsageDao&MockObject $dao;
    private EventDispatcherInterface&MockObject $event_dispatcher;

    public function setUp(): void
    {
        $this->artifact_factory       = $this->createMock(Tracker_ArtifactFactory::class);
        $this->type_presenter_factory = $this->createMock(TypePresenterFactory::class);

        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getId')->willReturn(15);

        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getId')->willReturn(101);
        $this->artifact->method('getTracker')->willReturn($this->tracker);

        $this->linked_artifact = ArtifactTestBuilder::anArtifact(105)->inTracker($this->tracker)->build();

        $this->field = ArtifactLinkFieldBuilder::anArtifactLinkField(6541)->build();
        $this->dao   = $this->createMock(ArtifactLinksUsageDao::class);

        $this->tracker->method('getProject')->willReturn($this->project);

        $this->event_dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->artifact_link_validator = new ArtifactLinkValidator(
            $this->artifact_factory,
            $this->type_presenter_factory,
            $this->dao,
            $this->event_dispatcher
        );

        $this->type_is_child = new TypeIsChildPresenter();
        $this->type_fixed_in = new TypePresenter('fixed_in', '', '', true);
        $this->type_no_type  = new TypePresenter('', '', '', true);
    }

    public function testItReturnsTrueWhenNoNewValuesAreSent(): void
    {
        self::assertTrue(
            $this->artifact_link_validator->isValid(
                [],
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
        self::assertTrue(
            $this->artifact_link_validator->isValid(
                null,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsFalseWhenArtifactIdIsIncorrect(): void
    {
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(false);
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            [],
        );
        $this->event_dispatcher->method('dispatch')->willReturn($returned_event);

        self::assertFalse(
            $this->artifact_link_validator->isValid(
                ['new_values' => '666'],
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );

        self::assertFalse(
            $this->artifact_link_validator->isValid(
                ['new_values' => '123, 666'],
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
        self::assertFalse(
            $this->artifact_link_validator->isValid(
                ['new_values' => '123,666'],
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
        self::assertFalse(
            $this->artifact_link_validator->isValid(
                ['new_values' => ',,,,'],
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsFalseWhenArtifactIdDoesNotExist(): void
    {
        $value = ['new_values' => '1000'];
        $this->artifact_factory->method('getArtifactById')->willReturn(null);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(false);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);

        self::assertFalse(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsFalseWhenTrackerIsDeleted(): void
    {
        $value = ['new_values' => '1000'];
        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isDeleted')->willReturn(true);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(false);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);

        self::assertFalse(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsFalseWhenProjectIsNotActive(): void
    {
        $value = ['new_values' => '1000'];
        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(false);
        $this->project->data_array['status'] = Project::STATUS_SUSPENDED;

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);

        self::assertFalse(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsTrueWhenProjectCanNotUseType(): void
    {
        $value = ['new_values' => '1000'];
        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(false);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);

        self::assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsFalseWhenProjectCanUseTypeAndTypeDoesNotExist(): void
    {
        $value = ['new_values' => '1000', 'types' => ['_is_child', 'fixed_in']];
        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $this->type_presenter_factory->method('getFromShortname')->willReturn(null);
        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([]);
        $this->artifact->method('getLastChangesetWithFieldValue')->willReturn(null);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);

        self::assertFalse(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsTrueWhenProjectCanUseTypeAndTypeExist(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $this->type_presenter_factory->method('getFromShortname')->willReturnCallback(fn(string $type) => match ($type) {
            '_is_child' => $this->type_is_child,
            'fixed_in'  => $this->type_fixed_in,
        });
        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn(
            [
                new TypePresenter('_is_child', 'label', 'reverse_label', true),
                new TypePresenter('fixed_in', 'label', 'reverse_label', true),
            ]
        );
        $this->artifact->method('getLastChangesetWithFieldValue')->willReturn(null);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            [],
        );
        $this->event_dispatcher->method('dispatch')->willReturn($returned_event);
        $this->dao->method('isTypeDisabledInProject')->willReturn(false);

        $value = ['new_values' => '1000', 'types' => ['_is_child', 'fixed_in']];
        self::assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );

        $value = ['new_values' => '123          ,   321, 999', 'types' => ['_is_child', 'fixed_in']];
        self::assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );

        $value = ['new_values' => '', 'types' => ['_is_child', 'fixed_in']];
        self::assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        ); // existing values

        $value = ['new_values' => '123', 'types' => ['_is_child', 'fixed_in']];
        self::assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsTrueWhenProjectCanUseTypeAndTypeEmpty(): void
    {
        $value = ['new_values' => '1000', 'types' => ['']];
        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $this->type_presenter_factory->method('getFromShortname')->with('')->willReturn(
            $this->type_no_type
        );
        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([]);
        $this->artifact->method('getLastChangesetWithFieldValue')->willReturn(null);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);
        $this->dao->method('isTypeDisabledInProject')->willReturn(false);

        self::assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsFalseWhenProjectCanUseTypesAndAtLeastOneTypeIsDisabled(): void
    {
        $value = [
            'new_values' => '1000',
            'types'      => ['_is_child', 'fixed_in'],
        ];

        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isDeleted')->willReturn(false);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $this->type_presenter_factory->method('getFromShortname')->willReturnCallback(fn(string $type) => match ($type) {
            '_is_child' => $this->type_is_child,
            'fixed_in'  => $this->type_fixed_in,
        });
        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([]);
        $this->dao->method('isTypeDisabledInProject')->with(101, self::isString())->willReturnCallback(static fn(int $id, string $type) => $type === 'fixed_in');
        $this->artifact->method('getLastChangesetWithFieldValue')->willReturn(null);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);

        self::assertFalse(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testDoesNotAllowUsageOfANonEditableArtLinkTypeOnALinkNotAlreadyUsingIt(): void
    {
        $value = [
            'new_values' => '',
            'types'      => ['123' => 'fixed_in_not_editable'],
        ];

        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $this->type_presenter_factory->method('getFromShortname')->with('fixed_in_not_editable')->willReturn($this->type_fixed_in);
        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([]);
        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $changeset->setFieldValue($this->field, ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset, $this->field)->withLinks([
            new Tracker_ArtifactLinkInfo(123, '', 101, 15, 1, 'an_editable_link'),
        ])->build());
        $this->artifact->method('getLastChangesetWithFieldValue')->willReturn($changeset);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);
        $this->dao->method('isTypeDisabledInProject')->willReturn(false);

        self::assertFalse(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testAllowsUsageOfANonEditableArtLinkTypeWhenTheLinkIsAlreadyUsingIt(): void
    {
        $value = [
            'new_values' => '',
            'types'      => ['123' => 'fixed_in_not_editable'],
        ];

        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $this->type_presenter_factory->method('getFromShortname')->with('fixed_in_not_editable')->willReturn($this->type_fixed_in);
        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([]);
        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $changeset->setFieldValue($this->field, ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset, $this->field)->withLinks([
            123 => new Tracker_ArtifactLinkInfo(123, '', 101, 15, 1, 'fixed_in_not_editable'),
        ])->build());
        $this->artifact->method('getLastChangesetWithFieldValue')->willReturn($changeset);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);
        $this->dao->method('isTypeDisabledInProject')->willReturn(false);

        self::assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testAllowsUsageOfANonEditableArtLinkTypeWhenContextIsSystemAction(): void
    {
        $value = [
            'new_values' => '',
            'types'      => ['123' => 'fixed_in_not_editable'],
        ];

        $this->artifact_factory->method('getArtifactById')->willReturn($this->linked_artifact);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $this->type_presenter_factory->method('getFromShortname')->with('fixed_in_not_editable')->willReturn($this->type_fixed_in);
        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([]);
        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $changeset->setFieldValue($this->field, ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset, $this->field)->withLinks([
            new Tracker_ArtifactLinkInfo(123, '', 101, 15, 1, 'an_editable_link'),
        ])->build());
        $this->artifact->method('getLastChangesetWithFieldValue')->willReturn($changeset);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);
        $this->dao->method('isTypeDisabledInProject')->willReturn(false);

        self::assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new SystemActionContext()
            )
        );
    }

    public function testItAsksToExternalPluginsToValidateDeletionOfLinks(): void
    {
        $value = [
            'new_values'     => '',
            'types'          => ['123' => ''],
            'removed_values' => [
                '666' => ['666'],
            ],
        ];

        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $changeset->setFieldValue($this->field, ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset, $this->field)->withLinks([
            new Tracker_ArtifactLinkInfo(123, '', 101, 15, 1, 'an_editable_link'),
        ])->build());
        $this->artifact->method('getLastChangesetWithFieldValue')->willReturn($changeset);
        $this->type_presenter_factory->method('getAllTypesEditableInProject')->willReturn([]);
        $this->type_presenter_factory->method('getFromShortname')->willReturn(null);
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects($this->once())->method('dispatch')->willReturn($returned_event);

        $this->artifact_link_validator->isValid(
            $value,
            $this->artifact,
            $this->field,
            new ManualActionContext()
        );
    }
}
