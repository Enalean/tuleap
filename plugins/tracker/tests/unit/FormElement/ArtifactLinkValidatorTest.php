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

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ValidateArtifactLinkValueEvent;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ManualActionContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\SystemActionContext;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory
     */
    private $type_presenter_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var ArtifactLinkValidator
     */
    private $artifact_link_validator;

    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    /**
     * @var Tracker_FormElement_Field_ArtifactLink
     */
    private $field;

    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $linked_artifact;

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter
     */
    private $type_is_child;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter
     */
    private $type_fixed_in;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter
     */
    private $type_no_type;
    private $project;
    private $dao;
    /**
     * @var \EventManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $event_dispatcher;

    public function setUp(): void
    {
        $this->artifact_factory       = \Mockery::spy(Tracker_ArtifactFactory::class);
        $this->type_presenter_factory = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory::class);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn(101);

        $this->tracker = \Mockery::spy(\Tracker::class);

        $this->artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->linked_artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->linked_artifact->shouldReceive('getId')->andReturn(105);
        $this->linked_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->field = \Mockery::spy(Tracker_FormElement_Field_ArtifactLink::class);
        $this->dao   = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);

        $this->tracker->shouldReceive('getProject')->andReturn($this->project);

        $this->event_dispatcher = $this->createMock(\EventManager::class);

        $this->artifact_link_validator = new ArtifactLinkValidator(
            $this->artifact_factory,
            $this->type_presenter_factory,
            $this->dao,
            $this->event_dispatcher
        );

        $this->type_is_child = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter::class);
        $this->type_fixed_in = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter::class);
        $this->type_no_type  = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter::class);
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
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(false);

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
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn(null);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(false);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isDeleted')->andReturn(true);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(false);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(false);
        $this->project->shouldReceive('isActive')->andReturn(false);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(false);
        $this->project->shouldReceive('isActive')->andReturn(true);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);
        $this->type_presenter_factory->shouldReceive('getFromShortname')->andReturn(null);
        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn(null);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);
        $this->type_presenter_factory->shouldReceive('getFromShortname')->with('_is_child')->andReturn(
            $this->type_is_child
        );
        $this->type_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn(
            $this->type_fixed_in
        );
        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn(
            [
                new TypePresenter('_is_child', 'label', 'reverse_label', true),
                new TypePresenter('fixed_in', 'label', 'reverse_label', true),
            ]
        );
        $this->project->shouldReceive('isActive')->andReturn(true);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn(null);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            [],
        );
        $this->event_dispatcher->method('dispatch')->willReturn($returned_event);

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
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);
        $this->type_presenter_factory->shouldReceive('getFromShortname')->with('')->andReturn(
            $this->type_no_type
        );
        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn(null);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
            'types'    => ['_is_child', 'fixed_in'],
        ];

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);
        $this->type_presenter_factory->shouldReceive('getFromShortname')->with('_is_child')->andReturn(
            $this->type_is_child
        );
        $this->type_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn(
            $this->type_fixed_in
        );
        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->dao->shouldReceive('isTypeDisabledInProject')->with(101, 'fixed_in')->andReturn(true);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn(null);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
            'types' => ['123' => 'fixed_in_not_editable'],
        ];

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);
        $this->type_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in_not_editable')->andReturn($this->type_fixed_in);
        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $changeset       = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $artifact_link_info = Mockery::mock(\Tracker_ArtifactLinkInfo::class);
        $artifact_link_info->shouldReceive('getType')->andReturn('an_editable_link');
        $changeset_value->shouldReceive('getValue')->andReturn(['123' => $artifact_link_info]);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn($changeset);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
            'types' => ['123' => 'fixed_in_not_editable'],
        ];

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);
        $this->type_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in_not_editable')->andReturn($this->type_fixed_in);
        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $changeset       = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $artifact_link_info = Mockery::mock(\Tracker_ArtifactLinkInfo::class);
        $artifact_link_info->shouldReceive('getType')->andReturn('fixed_in_not_editable');
        $changeset_value->shouldReceive('getValue')->andReturn(['123' => $artifact_link_info]);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn($changeset);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
            'types' => ['123' => 'fixed_in_not_editable'],
        ];

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseType')->andReturn(true);
        $this->type_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in_not_editable')->andReturn($this->type_fixed_in);
        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $changeset       = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $artifact_link_info = Mockery::mock(\Tracker_ArtifactLinkInfo::class);
        $artifact_link_info->shouldReceive('getType')->andReturn('an_editable_link');
        $changeset_value->shouldReceive('getValue')->andReturn(['123' => $artifact_link_info]);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn($changeset);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

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
            'new_values' => '',
            'types' => ['123' => ''],
            'removed_values' => [
                '666' => ['666'],
            ],
        ];

        $changeset       = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $artifact_link_info = Mockery::mock(\Tracker_ArtifactLinkInfo::class);
        $artifact_link_info->shouldReceive('getType')->andReturn('an_editable_link');
        $changeset_value->shouldReceive('getValue')->andReturn(['123' => $artifact_link_info]);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn($changeset);
        $this->type_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);

        $returned_event = ValidateArtifactLinkValueEvent::buildFromSubmittedValues(
            $this->artifact,
            $value,
        );
        $this->event_dispatcher->expects(self::once())->method('dispatch')->willReturn($returned_event);

        $this->artifact_link_validator->isValid(
            $value,
            $this->artifact,
            $this->field,
            new ManualActionContext()
        );
    }
}
