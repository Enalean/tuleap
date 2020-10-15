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
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ManualActionContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\SystemActionContext;

final class ArtifactLinkValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory
     */
    private $nature_presenter_factory;

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
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildPresenter
     */
    private $nature_is_child;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter
     */
    private $nature_fixed_in;

    /**
     * @var \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter
     */
    private $nature_no_nature;
    private $project;
    private $dao;

    public function setUp(): void
    {
        $this->artifact_factory         = \Mockery::spy(Tracker_ArtifactFactory::class);
        $this->nature_presenter_factory = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory::class);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn(101);

        $this->tracker = \Mockery::spy(\Tracker::class);

        $this->artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->linked_artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->linked_artifact->shouldReceive('getId')->andReturn(105);
        $this->linked_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->field   = \Mockery::spy(Tracker_FormElement_Field_ArtifactLink::class);
        $this->dao     = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);

        $this->tracker->shouldReceive('getProject')->andReturn($this->project);

        $this->artifact_link_validator = new ArtifactLinkValidator(
            $this->artifact_factory,
            $this->nature_presenter_factory,
            $this->dao
        );

        $this->nature_is_child  = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildPresenter::class);
        $this->nature_fixed_in  = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter::class);
        $this->nature_no_nature = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter::class);
    }

    public function testItReturnsTrueWhenNoNewValuesAreSent(): void
    {
        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                [],
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
        $this->assertTrue(
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
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

        $this->assertFalse(
            $this->artifact_link_validator->isValid(
                ['new_values' => '666'],
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );

        $this->assertFalse(
            $this->artifact_link_validator->isValid(
                ['new_values' => '123, 666'],
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
        $this->assertFalse(
            $this->artifact_link_validator->isValid(
                ['new_values' => '123,666'],
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
        $this->assertFalse(
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
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

        $this->assertFalse(
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
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);

        $this->assertFalse(
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
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);
        $this->project->shouldReceive('isActive')->andReturn(false);

        $this->assertFalse(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsTrueWhenProjectCanNotUseNature(): void
    {
        $value = ['new_values' => '1000'];
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(false);
        $this->project->shouldReceive('isActive')->andReturn(true);

        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsFalseWhenProjectCanUseNatureAndNatureDoesNotExist(): void
    {
        $value = ['new_values' => '1000', 'natures' => ['_is_child', 'fixed_in']];
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->andReturn(null);
        $this->nature_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn(null);

        $this->assertFalse(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsTrueWhenProjectCanUseNatureAndNatureExist(): void
    {
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('_is_child')->andReturn(
            $this->nature_is_child
        );
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn(
            $this->nature_fixed_in
        );
        $this->nature_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn(
            [
                new NaturePresenter('_is_child', 'label', 'reverse_label', true),
                new NaturePresenter('fixed_in', 'label', 'reverse_label', true),
            ]
        );
        $this->project->shouldReceive('isActive')->andReturn(true);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn(null);

        $value = ['new_values' => '1000', 'natures' => ['_is_child', 'fixed_in']];
        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );

        $value = ['new_values' => '123          ,   321, 999', 'natures' => ['_is_child', 'fixed_in']];
        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );

        $value = ['new_values' => '', 'natures' => ['_is_child', 'fixed_in']];
        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        ); // existing values

        $value = ['new_values' => '123', 'natures' => ['_is_child', 'fixed_in']];
        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new ManualActionContext()
            )
        );
    }

    public function testItReturnsTrueWhenProjectCanUseNatureAndNatureEmpty(): void
    {
        $value = ['new_values' => '1000', 'natures' => ['']];
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('')->andReturn(
            $this->nature_no_nature
        );
        $this->nature_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn(null);

        $this->assertTrue(
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
            'natures'    => ['_is_child', 'fixed_in']
        ];

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('_is_child')->andReturn(
            $this->nature_is_child
        );
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in')->andReturn(
            $this->nature_fixed_in
        );
        $this->nature_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->dao->shouldReceive('isTypeDisabledInProject')->with(101, 'fixed_in')->andReturn(true);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn(null);

        $this->assertFalse(
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
            'natures' => ['123' => 'fixed_in_not_editable']
        ];

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in_not_editable')->andReturn($this->nature_fixed_in);
        $this->nature_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $changeset       = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $artifact_link_info = Mockery::mock(\Tracker_ArtifactLinkInfo::class);
        $artifact_link_info->shouldReceive('getNature')->andReturn('an_editable_link');
        $changeset_value->shouldReceive('getValue')->andReturn(['123' => $artifact_link_info]);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn($changeset);

        $this->assertFalse(
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
            'natures' => ['123' => 'fixed_in_not_editable']
        ];

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in_not_editable')->andReturn($this->nature_fixed_in);
        $this->nature_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $changeset       = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $artifact_link_info = Mockery::mock(\Tracker_ArtifactLinkInfo::class);
        $artifact_link_info->shouldReceive('getNature')->andReturn('fixed_in_not_editable');
        $changeset_value->shouldReceive('getValue')->andReturn(['123' => $artifact_link_info]);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn($changeset);

        $this->assertTrue(
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
            'natures' => ['123' => 'fixed_in_not_editable']
        ];

        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($this->linked_artifact);
        $this->tracker->shouldReceive('isProjectAllowedToUseNature')->andReturn(true);
        $this->nature_presenter_factory->shouldReceive('getFromShortname')->with('fixed_in_not_editable')->andReturn($this->nature_fixed_in);
        $this->nature_presenter_factory->shouldReceive('getAllTypesEditableInProject')->andReturn([]);
        $this->project->shouldReceive('isActive')->andReturn(true);
        $changeset       = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')->andReturn($changeset_value);
        $artifact_link_info = Mockery::mock(\Tracker_ArtifactLinkInfo::class);
        $artifact_link_info->shouldReceive('getNature')->andReturn('an_editable_link');
        $changeset_value->shouldReceive('getValue')->andReturn(['123' => $artifact_link_info]);
        $this->artifact->shouldReceive('getLastChangesetWithFieldValue')->andReturn($changeset);

        $this->assertTrue(
            $this->artifact_link_validator->isValid(
                $value,
                $this->artifact,
                $this->field,
                new SystemActionContext()
            )
        );
    }
}
