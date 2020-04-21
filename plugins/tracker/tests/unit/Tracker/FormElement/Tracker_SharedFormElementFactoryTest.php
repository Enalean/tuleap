<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
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

use Mockery\MockInterface;

require_once dirname(__FILE__) . '/../../bootstrap.php';

class Tracker_SharedFormElementFactoryTest extends \PHPUnit\Framework\TestCase // @codingStandardsIgnoreLine
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Project|MockInterface
     */
    private $project;
    /**
     * @var Tracker|MockInterface
     */
    private $tracker;

    protected function setUp(): void
    {
        $this->project = Mockery::mock(Project::class);
        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getProject')->andReturn($this->project);
        $this->tracker->shouldReceive('getId')->andReturn(1);

        $language            = \Mockery::mock(\BaseLanguage::class);
        $GLOBALS['Language'] = $language;
        $GLOBALS['Language']->shouldReceive('getText');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testCreateFormElementExtractsDataFromOriginalFieldThenForwardsToFactory()
    {
        $field_id = 321;

        $original_field = $this->givenASelectBoxField(321, null);
        list($shared_factory, $form_element_factory, $user, $bound_factory) = $this->givenASharedFormElementFactory();
        $this->setFieldAndTrackerPermissions($original_field, true, true);

        $original_field->shouldReceive('getId')->andReturn($field_id);
        $original_field->shouldReceive('getOriginalField')->andReturn(null);

        $form_element_factory->shouldReceive('getFormElementById')->withArgs([$field_id])->andReturn($original_field);

        $this->project->shouldReceive('isActive')->andReturn(true);

        $bound_factory->shouldReceive('duplicateByReference')->andReturn(null);

        $formElement_data = [
            'field_id' => $original_field->getId(),
        ];

        $form_element_factory->shouldReceive(
            'createFormElement'
        )->withArgs(
            [
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
            ]
        )->once();

        $shared_factory->createFormElement($this->tracker, $formElement_data, $user, false, false);
    }

    /**
     * @return MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private function givenASelectBoxField($id, $original_field)
    {
        $bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);

        $field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $field->shouldReceive('getLabel')->andReturn('Label');
        $field->shouldReceive('getDescription')->andReturn('Description');
        $field->shouldReceive('isUsed')->andReturn(true);
        $field->shouldReceive('getScope')->andReturn('P');
        $field->shouldReceive('isRequired')->andReturn(true);
        $field->shouldReceive('hasNotifications')->andReturn(true);
        $field->shouldReceive('getTracker')->andReturn($this->tracker);
        $field->shouldReceive('getBind')->andReturn($bind);
        $field->shouldReceive('getId')->andReturn($id);
        if ($original_field) {
            $field->shouldReceive('getOriginalFieldId')->andReturn($original_field->getId());
            $field->shouldReceive('getOriginalField')->andReturn($original_field);
        } else {
            $field->shouldReceive('getOriginalFieldId')->andReturn(null);
            $field->shouldReceive('getOriginalField')->andReturn(null);
        }

        return $field;
    }

    private function givenASharedFormElementFactory()
    {
        $user                 = Mockery::mock(PFUser::class);
        $form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $form_element_factory->shouldReceive('getType')->andReturn('sb');

        $bound_factory  = Mockery::mock(Tracker_FormElement_Field_List_BindFactory::class);
        $shared_factory = new Tracker_SharedFormElementFactory(
            $form_element_factory,
            $bound_factory
        );
        return [$shared_factory, $form_element_factory, $user, $bound_factory];
    }

    private function setFieldAndTrackerPermissions(
        Tracker_FormElement_Field $field,
        $user_can_read_field,
        $user_can_view_tracker
    ) {
        $field->shouldReceive('userCanRead')->andReturn($user_can_read_field);
        $this->tracker->shouldReceive('userCanView')->andReturn($user_can_view_tracker);
    }

    public function testUnreadableFieldCannotBeCopied()
    {
        $field_id       = 321;
        $original_field = $this->givenASelectBoxField($field_id, null);
        list($shared_factory, $form_element_factory, $user, $bound_factory) = $this->givenASharedFormElementFactory();
        $this->setFieldAndTrackerPermissions($original_field, false, true);

        $this->expectException(Exception::class);
        $shared_factory->createFormElement($this->tracker, ['field_id' => $original_field->getId()], $user, false, false);
    }

    public function testFieldInInaccessibleTrackerCannotBeCopied()
    {
        $field_id       = 321;
        $original_field = $this->givenASelectBoxField($field_id, null);
        list($shared_factory, $form_element_factory, $user, $bound_factory) = $this->givenASharedFormElementFactory();
        $this->setFieldAndTrackerPermissions($original_field, true, false);

        $this->expectException(Exception::class);
        $shared_factory->createFormElement($this->tracker, ['field_id' => $original_field->getId()], $user, false, false);
    }

    public function testDuplicatesAnyValuesThatAreBoundToTheOriginalField()
    {
        $field_id     = 321;
        $new_field_id = 999;

        $original_field = $this->givenASelectBoxField($field_id, null);
        list($shared_factory, $form_element_factory, $user, $bound_factory) = $this->givenASharedFormElementFactory();
        $this->setFieldAndTrackerPermissions($original_field, true, true);

        $form_element_factory->shouldReceive('getFormElementById')->withArgs([$field_id])->andReturn($original_field);

        $this->project->shouldReceive('isActive')->andReturn(true);

        $form_element_factory->shouldReceive('createFormElement')->andReturn($new_field_id);
        $bound_factory->shouldReceive(
            'duplicateByReference'
        )->withArgs([$original_field->getId(), $new_field_id])->once();

        $shared_factory->createFormElement($this->tracker, ['field_id' => $original_field->getId()], $user, false, false);
    }

    public function testSharedFieldsShouldRespectChaslesTheorem()
    {
        list($field, $original_field, $shared_factory, $bound_factory, $user, $form_element_factory) = $this->givenTwoFieldsThatAreShared();
        $form_element_data = $this->whenIShareTheCopy($field);
        $this->thenTheOriginalShouldBeUsed(
            $form_element_factory,
            $original_field,
            $shared_factory,
            $form_element_data,
            $user,
            $bound_factory
        );
    }

    private function givenTwoFieldsThatAreShared()
    {
        $original_field_id = 123;
        $original_field    = $this->givenASelectBoxField($original_field_id, null);
        $this->setFieldAndTrackerPermissions($original_field, true, true);

        $field_id = 456;
        $field    = $this->givenASelectBoxField($field_id, $original_field);
        $this->setFieldAndTrackerPermissions($field, true, true);

        list($shared_factory, $form_element_factory, $user, $bound_factory) = $this->givenASharedFormElementFactory();
        $form_element_factory->shouldReceive('getType')->withArgs([$original_field])->andReturn('string');
        $form_element_factory->shouldReceive('getFormElementById')->withArgs([$field_id])->andReturn($field);

        $this->project->shouldReceive('isActive')->andReturn(true);

        return [$field, $original_field, $shared_factory, $bound_factory, $user, $form_element_factory];
    }

    private function whenIShareTheCopy(Tracker_FormElement_Field $field)
    {
        $form_element_data = [
            'field_id' => $field->getId(),
        ];
        return $form_element_data;
    }

    private function thenTheOriginalShouldBeUsed(
        Tracker_FormElementFactory $form_element_factory,
        Tracker_FormElement_Field $original_field,
        Tracker_SharedFormElementFactory $shared_form_element_factory,
        array $form_element_data,
        PFUser $user,
        Tracker_FormElement_Field_List_BindFactory $bind_factory
    ) {
        $form_element_factory->shouldReceive('createFormElement')->withArgs(
            [
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
            ]
        )->once();

        $bind_factory->shouldReceive('duplicateByReference');

        $shared_form_element_factory->createFormElement($this->tracker, $form_element_data, $user, false, false);
    }

    public function testCreateSharedFieldNotPossibleIfFieldNotSelectbox()
    {
        $field = $this->givenAStringField();
        list($decorator, $factory, $tracker, $user) = $this->givenASharedFormElementFactory();
        $this->setFieldAndTrackerPermissions($field, true, true);

        $this->expectException(Exception::class);
        $decorator->createFormElement($tracker, ['field_id' => $field->getId()], $user, false, false);
    }

    private function givenAStringField()
    {
        $field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $field->shouldReceive('getTracker')->andReturn($this->tracker);

        return $field;
    }

    public function testCreateSharedFieldNotPossibleIfFieldNotStaticSelectbox()
    {
        $field = $this->givenASelectBoxBoundToUsers();
        list($decorator, $factory, $tracker, $user) = $this->givenASharedFormElementFactory();
        $this->setFieldAndTrackerPermissions($field, true, true);

        $this->expectException(Exception::class);
        $decorator->createFormElement($tracker, ['field_id' => $field->getId()], $user, false, false);
    }

    private function givenASelectBoxBoundToUsers()
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $field->shouldReceive('getTracker')->andReturn($this->tracker);
        $field->shouldReceive('getBind')->andReturn(Mockery::mock(Tracker_FormElement_Field_List_Bind_Users::class));
        return $field;
    }

    public function testCreateSharedFieldNotPossibleWhenProjectIsNotActive()
    {
        $field_id = 321;

        $original_field = $this->givenASelectBoxField(321, null);
        list($shared_factory, $form_element_factory, $user, $bound_factory) = $this->givenASharedFormElementFactory();
        $this->setFieldAndTrackerPermissions($original_field, true, true);

        $original_field->shouldReceive('getId')->andReturn($field_id);
        $original_field->shouldReceive('getOriginalField')->andReturn(null);

        $form_element_factory->shouldReceive('getFormElementById')->withArgs([$field_id])->andReturn($original_field);

        $this->project->shouldReceive('isActive')->andReturn(false);

        $formElement_data = [
            'field_id' => $original_field->getId(),
        ];

        $this->expectException(Exception::class);
        $shared_factory->createFormElement($this->tracker, $formElement_data, $user, false, false);
    }
}
