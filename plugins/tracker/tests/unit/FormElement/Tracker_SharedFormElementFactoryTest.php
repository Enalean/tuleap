<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Exception;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List_BindFactory;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use Tracker_SharedFormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SharedFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_SharedFormElementFactoryTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Project&MockObject $project;
    private Tracker&MockObject $tracker;

    protected function setUp(): void
    {
        $this->project = $this->createMock(Project::class);
        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getProject')->willReturn($this->project);
        $this->tracker->method('getId')->willReturn(1);
    }

    public function testCreateFormElementExtractsDataFromOriginalFieldThenForwardsToFactory(): void
    {
        $field_id             = 321;
        $original_field       = $this->givenASelectBoxField($field_id, null);
        $user                 = UserTestBuilder::buildWithDefaults();
        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getType')->willReturn('sb');
        $bind_factory   = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $shared_factory = new Tracker_SharedFormElementFactory($form_element_factory, $bind_factory);
        $this->setFieldAndTrackerPermissions($original_field, $user, true, true);

        $form_element_factory->method('getFormElementById')->with($field_id)->willReturn($original_field);

        $this->project->method('isActive')->willReturn(true);

        $bind_factory->method('duplicateByReference')->willReturn(null);

        $formElement_data = [
            'field_id' => $original_field->getId(),
        ];

        $form_element_factory->expects($this->once())->method('createFormElement')->with(
            $this->tracker,
            'sb',
            [
                'type'              => 'sb',
                'label'             => $original_field->getLabel(),
                'description'       => $original_field->getDescription(),
                'use_it'            => $original_field->isUsed(),
                'scope'             => $original_field->getScope(),
                'required'          => $original_field->isRequired(),
                'notifications'     => $original_field->hasNotifications(),
                'original_field_id' => $original_field->getId(),
            ],
            false,
            false,
        );

        $shared_factory->createFormElement($this->tracker, $formElement_data, $user, false, false);
    }

    private function givenASelectBoxField(int $id, ?Tracker_FormElement_Field $original_field): Tracker_FormElement_Field_Selectbox
    {
        if ($original_field !== null) {
            $field = SharedFieldBuilder::aSharedField($id, $original_field)
                ->withLabel('Label')
                ->thatIsRequired()
                ->inTracker($this->tracker)
                ->build();
        } else {
            $field = ListFieldBuilder::aListField($id)
                ->withLabel('Label')
                ->thatIsRequired()
                ->inTracker($this->tracker)
                ->build();
        }
        ListStaticBindBuilder::aStaticBind($field)->build()->getField();
        $field->notifications = true;
        return $field;
    }

    private function setFieldAndTrackerPermissions(
        Tracker_FormElement_Field $field,
        PFUser $user,
        bool $user_can_read_field,
        bool $user_can_view_tracker,
    ): void {
        $field->setUserCanRead($user, $user_can_read_field);
        $this->tracker->method('userCanView')->willReturn($user_can_view_tracker);
    }

    public function testUnreadableFieldCannotBeCopied(): void
    {
        $original_field       = $this->givenASelectBoxField(321, null);
        $user                 = UserTestBuilder::buildWithDefaults();
        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getType')->willReturn('sb');
        $form_element_factory->method('getFormElementById')->willReturn($original_field);
        $this->project->method('isActive')->willReturn(true);

        $bind_factory   = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $shared_factory = new Tracker_SharedFormElementFactory($form_element_factory, $bind_factory);
        $this->setFieldAndTrackerPermissions($original_field, $user, false, true);

        $this->expectException(Exception::class);
        $shared_factory->createFormElement($this->tracker, ['field_id' => $original_field->getId()], $user, false, false);
    }

    public function testFieldInInaccessibleTrackerCannotBeCopied(): void
    {
        $original_field       = $this->givenASelectBoxField(321, null);
        $user                 = UserTestBuilder::buildWithDefaults();
        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getType')->willReturn('sb');
        $form_element_factory->method('getFormElementById')->willReturn($original_field);
        $this->project->method('isActive')->willReturn(true);

        $bind_factory   = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $shared_factory = new Tracker_SharedFormElementFactory($form_element_factory, $bind_factory);
        $this->setFieldAndTrackerPermissions($original_field, $user, true, false);

        $this->expectException(Exception::class);
        $shared_factory->createFormElement($this->tracker, ['field_id' => $original_field->getId()], $user, false, false);
    }

    public function testDuplicatesAnyValuesThatAreBoundToTheOriginalField(): void
    {
        $field_id     = 321;
        $new_field_id = 999;

        $original_field       = $this->givenASelectBoxField($field_id, null);
        $user                 = UserTestBuilder::buildWithDefaults();
        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getType')->willReturn('sb');

        $bind_factory   = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $shared_factory = new Tracker_SharedFormElementFactory($form_element_factory, $bind_factory);
        $this->setFieldAndTrackerPermissions($original_field, $user, true, true);

        $form_element_factory->method('getFormElementById')->with($field_id)->willReturn($original_field);

        $this->project->method('isActive')->willReturn(true);

        $form_element_factory->method('createFormElement')->willReturn($new_field_id);
        $bind_factory->expects($this->once())->method('duplicateByReference')->with($original_field->getId(), $new_field_id);

        $shared_factory->createFormElement($this->tracker, ['field_id' => $original_field->getId()], $user, false, false);
    }

    public function testSharedFieldsShouldRespectChaslesTheorem(): void
    {
        $user           = UserTestBuilder::buildWithDefaults();
        $original_field = $this->givenASelectBoxField(123, null);
        $this->setFieldAndTrackerPermissions($original_field, $user, true, true);

        $field_id = 456;
        $field    = $this->givenASelectBoxField($field_id, $original_field);
        $this->setFieldAndTrackerPermissions($field, $user, true, true);

        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getType')->willReturn('sb');

        $bind_factory   = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $shared_factory = new Tracker_SharedFormElementFactory($form_element_factory, $bind_factory);
        $form_element_factory->method('getType')->with($original_field)->willReturn('string');
        $form_element_factory->method('getFormElementById')->with($field_id)->willReturn($field);

        $this->project->method('isActive')->willReturn(true);
        $form_element_data = ['field_id' => $field->getId()];
        $this->thenTheOriginalShouldBeUsed(
            $form_element_factory,
            $original_field,
            $shared_factory,
            $form_element_data,
            $user,
            $bind_factory,
        );
    }

    private function thenTheOriginalShouldBeUsed(
        Tracker_FormElementFactory&MockObject $form_element_factory,
        Tracker_FormElement_Field $original_field,
        Tracker_SharedFormElementFactory $shared_form_element_factory,
        array $form_element_data,
        PFUser $user,
        Tracker_FormElement_Field_List_BindFactory&MockObject $bind_factory,
    ): void {
        $form_element_factory->expects($this->once())->method('createFormElement')->with(
            $this->tracker,
            'sb',
            [
                'type'              => 'sb',
                'label'             => $original_field->getLabel(),
                'description'       => $original_field->getDescription(),
                'use_it'            => $original_field->isUsed(),
                'scope'             => $original_field->getScope(),
                'required'          => $original_field->isRequired(),
                'notifications'     => $original_field->hasNotifications(),
                'original_field_id' => $original_field->getId(),
            ],
            false,
            false,
        );

        $bind_factory->method('duplicateByReference');

        $shared_form_element_factory->createFormElement($this->tracker, $form_element_data, $user, false, false);
    }

    public function testCreateSharedFieldNotPossibleIfFieldNotSelectbox(): void
    {
        $field                = StringFieldBuilder::aStringField(456)->inTracker($this->tracker)->build();
        $user                 = UserTestBuilder::buildWithDefaults();
        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getType')->willReturn('sb');
        $form_element_factory->method('getFormElementById')->willReturn($field);
        $this->project->method('isActive')->willReturn(true);

        $bind_factory   = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $shared_factory = new Tracker_SharedFormElementFactory($form_element_factory, $bind_factory);
        $this->setFieldAndTrackerPermissions($field, $user, true, true);

        $this->expectException(Exception::class);
        $shared_factory->createFormElement($this->tracker, ['field_id' => $field->getId()], $user, false, false);
    }

    public function testCreateSharedFieldNotPossibleIfFieldNotStaticSelectbox(): void
    {
        $field                = $this->givenASelectBoxBoundToUsers();
        $user                 = UserTestBuilder::buildWithDefaults();
        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getType')->willReturn('sb');
        $form_element_factory->method('getFormElementById')->willReturn($field);
        $this->project->method('isActive')->willReturn(true);

        $bind_factory   = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $shared_factory = new Tracker_SharedFormElementFactory($form_element_factory, $bind_factory);
        $this->setFieldAndTrackerPermissions($field, $user, true, true);

        $this->expectException(Exception::class);
        $shared_factory->createFormElement($this->tracker, ['field_id' => $field->getId()], $user, false, false);
    }

    private function givenASelectBoxBoundToUsers(): Tracker_FormElement_Field_Selectbox
    {
        $field = ListUserBindBuilder::aUserBind(ListFieldBuilder::aListField(654)->inTracker($this->tracker)->build())->build()->getField();
        self::assertInstanceOf(Tracker_FormElement_Field_Selectbox::class, $field);
        return $field;
    }

    public function testCreateSharedFieldNotPossibleWhenProjectIsNotActive(): void
    {
        $field_id = 321;

        $original_field       = $this->givenASelectBoxField($field_id, null);
        $user                 = UserTestBuilder::buildWithDefaults();
        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getType')->willReturn('sb');

        $bind_factory   = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $shared_factory = new Tracker_SharedFormElementFactory($form_element_factory, $bind_factory);
        $this->setFieldAndTrackerPermissions($original_field, $user, true, true);

        $form_element_factory->method('getFormElementById')->with($field_id)->willReturn($original_field);

        $this->project->method('isActive')->willReturn(false);

        $formElement_data = [
            'field_id' => $original_field->getId(),
        ];

        $this->expectException(Exception::class);
        $shared_factory->createFormElement($this->tracker, $formElement_data, $user, false, false);
    }
}
