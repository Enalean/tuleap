<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ManualActionContext;

final class Tracker_Artifact_Changeset_InitialChangesetFieldsValidatorTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    /** @var Tracker_Artifact_Changeset_InitialChangesetFieldsValidator */
    private $initial_changeset_fields_validator;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\Tracker\FormElement\ArtifactLinkValidator
     */
    private $artifact_link_validator;

    /** @var Workflow */
    private $workflow;

    /** @var Artifact */
    private $artifact;

    protected function setUp(): void
    {
        $this->factory                 = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->artifact_link_validator = \Mockery::mock(\Tuleap\Tracker\FormElement\ArtifactLinkValidator::class);
        $workflow_checker              = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $this->initial_changeset_fields_validator = new Tracker_Artifact_Changeset_InitialChangesetFieldsValidator(
            $this->factory,
            $this->artifact_link_validator,
        );

        $this->factory->shouldReceive('getAllFormElementsForTracker')->andReturns([]);

        $this->workflow = \Mockery::spy(\Workflow::class);

        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn(\Mockery::spy(\Tracker::class));
        $this->artifact->shouldReceive('getWorkflow')->andReturns($this->workflow);
        $this->artifact->shouldReceive('getLastChangeset')->andReturns(new Tracker_Artifact_Changeset_Null());
    }

    /**
     * @return \Mockery\Mock|Tracker_FormElement_Field_Text
     */
    private function getFieldWithId(int $id, bool $can_submit, bool $can_update, bool $is_valid)
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Text::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getId')->andReturn($id);
        $field->shouldReceive('userCanUpdate')->andReturn($can_update);
        $field->shouldReceive('userCanSubmit')->andReturn($can_submit);
        $field->shouldReceive('isValid')->andReturns($is_valid);

        return $field;
    }

    public function testValidateFieldsBasicValid(): void
    {
        $field1 = $this->getFieldWithId(101, false, false, true);
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);

        $user        = \Mockery::spy(\PFUser::class);
        $fields_data = [];
        $this->assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertNotNull($fields_data);
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[102]));
        $this->assertFalse(isset($fields_data[103]));
    }

    public function testValidateSubmitFieldNotRequired(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);

        $this->workflow->shouldReceive('validate')->andReturns(true);

        $user        = \Mockery::spy(\PFUser::class);
        $fields_data = ['101' => 444];
        $this->assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertNotNull($fields_data[101]);
        $this->assertEquals(444, $fields_data[101]);
    }

    public function testValidateSubmitFieldNotRequiredNotSubmittedDefaultValue(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field1->shouldReceive('hasDefaultValue')->andReturns(true);
        $field1->shouldReceive('getDefaultValue')->andReturns('DefaultValue');

        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);
        $this->workflow->shouldReceive('validate')->andReturns(true);

        $user        = \Mockery::spy(\PFUser::class);
        $fields_data = [];
        $this->assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertFalse(isset($fields_data[101]));
    }

    public function testValidateSubmitFieldNotRequiredNotSubmittedNoDefaultValue(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);

        $this->workflow->shouldReceive('validate')->andReturns(true);

        $user        = \Mockery::spy(\PFUser::class);
        $fields_data = [];
        $this->assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertFalse(isset($fields_data[101]));
    }

    public function testValidateSubmitFieldRequired(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field1->shouldReceive('isRequired')->andReturns(true);
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);

        $this->workflow->shouldReceive('validate')->andReturns(true);

        $user        = \Mockery::spy(\PFUser::class);
        $fields_data = ['101' => 666];
        $this->assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertNotNull($fields_data[101]);
        $this->assertEquals(666, $fields_data[101]);
    }

    public function testValidateSubmitFieldRequiredNotSubmittedDefaultValue(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field1->shouldReceive('isRequired')->andReturns(true);
        $field1->shouldReceive('hasDefaultValue')->andReturns(true);
        $field1->shouldReceive('getDefaultValue')->andReturns('DefaultValue');
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);
        $this->workflow->shouldReceive('validate')->andReturns(true);

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()]);

        $user        = \Mockery::spy(\PFUser::class);
        $fields_data = [];
        $this->assertFalse(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertFalse(isset($fields_data[101]));
    }

    public function testValidateSubmitFieldRequiredNotSubmittedNoDefaultValue(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, false);
        $field1->shouldReceive('hasDefaultValue')->andReturns(false);
        $field1->shouldReceive('isRequired')->andReturns(true);
        $field2 = $this->getFieldWithId(102, false, false, true);
        $field3 = $this->getFieldWithId(103, false, false, true);

        $this->factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);

        $this->workflow->shouldReceive('validate')->andReturns(true);

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()]);

        $user        = \Mockery::spy(\PFUser::class);
        $fields_data = [];
        $this->assertFalse(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
    }

    public function testValidateFieldsMissingFieldsOnSubmission(): void
    {
        $field1 = $this->getFieldWithId(101, true, false, true);
        $field2 = $this->getFieldWithId(102, true, false, true);
        $field3 = $this->getFieldWithId(103, true, false, true);

        $this->factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);

        $this->workflow->shouldReceive('validate')->andReturns(true);

        $user = \Mockery::spy(\PFUser::class);
        // field 101 and 102 are missing
        // 101 has a default value
        // 102 has no default value
        $fields_data = ['103' => 444];
        $this->assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[102]));
        $this->assertNotNull($fields_data[103]);
        $this->assertEquals(444, $fields_data[103]);
    }

    public function testValidateFieldsBasicNotValid(): void
    {
        $field1 = $this->getFieldWithId(101, false, false, false);
        $field2 = $this->getFieldWithId(102, true, false, true);
        $field3 = $this->getFieldWithId(103, true, false, true);

        $field1->shouldReceive('validateFieldWithPermissionsAndRequiredStatus')->andReturn(false);

        $this->factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);

        $user = \Mockery::spy(\PFUser::class);
        // field 102 is missing
        $fields_data = [];
        $this->assertFalse(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext()
            )
        );
        $this->assertFalse(isset($fields_data[101]));
        $this->assertFalse(isset($fields_data[102]));
        $this->assertFalse(isset($fields_data[103]));
    }

    public function testItValidatesArtifactLinkField(): void
    {
        $this->artifact_link_validator->shouldReceive('isValid')->once()->andReturn(true);

        $artifact_link_field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link_field->shouldReceive('getId')->andReturn(101);
        $artifact_link_field->shouldReceive('userCanUpdate')->andReturnTrue();
        $artifact_link_field->shouldReceive('isRequired')->andReturnFalse();
        $artifact_link_field->shouldReceive('validateFieldWithPermissionsAndRequiredStatus')->andReturnTrue();
        $this->factory->shouldReceive('getUsedFields')->andReturns([$artifact_link_field]);

        $user        = UserTestBuilder::aUser()->build();
        $fields_data = [
            '101' => ['new_values' => '184']
        ];
        $context     = new ChangesetWithFieldsValidationContext(new ManualActionContext());
        $this->assertTrue(
            $this->initial_changeset_fields_validator->validate(
                $this->artifact,
                $user,
                $fields_data,
                $context
            )
        );
    }
}
