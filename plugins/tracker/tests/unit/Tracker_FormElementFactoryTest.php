<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


final class Tracker_FormElementFactoryTest extends PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use Tuleap\GlobalLanguageMock;

    /**
     * @var int
     */
    private $template_id;

    /**
     * @var int
     */
    private $project_id;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_FieldDao
     */
    private $dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\Mock|Tracker_FormElementFactory
     */
    private $factory;

    /**
     * @var array
     */
    private $backup_globals;

    protected function setUp(): void
    {
        $this->backup_globals = array_merge([], $GLOBALS);
        $GLOBALS['HTML']      = Mockery::spy(Layout::class);

        $this->dao = Mockery::spy(Tracker_FormElement_FieldDao::class);

        $this->factory = Mockery::spy(Tracker_FormElementFactory::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();

        $this->factory->shouldReceive('getDao')->andReturns($this->dao);

        $this->user    = Mockery::spy(PFUser::class);
        $this->tracker = Mockery::spy(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(66);

        $this->project_id  = 3;
        $this->template_id = 29;
    }

    protected function tearDown(): void
    {
        $GLOBALS = $this->backup_globals;
    }


    public function testSaveObject(): void
    {
        $a_formelement = Mockery::spy(Tracker_FormElement_Container_Fieldset::class);

        $a_formelement->shouldReceive('afterSaveObject')->once();
        $a_formelement->shouldReceive('setId')->with(66)->once();
        $a_formelement->shouldReceive('getFlattenPropertiesValues')->andReturns([]);

        $this->factory->shouldReceive('createFormElement')->andReturns(66);

        $this->assertEquals(
            66,
            $this->factory->saveObject($this->tracker, $a_formelement, 0, $this->user, false)
        );
    }

    //WARNING : READ/UPDATE is actual when last is READ, UPDATE liste (weird case, but good to know)
    public function testGetPermissionFromFormElementData(): void
    {
        $formElementData = [
            'permissions' => [
                $GLOBALS['UGROUP_ANONYMOUS']  => [
                    0 => 'PLUGIN_TRACKER_FIELD_READ',
                    1 => 'PLUGIN_TRACKER_FIELD_UPDATE'
                ],
                $GLOBALS['UGROUP_REGISTERED'] => [
                    0 => 'PLUGIN_TRACKER_FIELD_UPDATE',
                    1 => 'PLUGIN_TRACKER_FIELD_READ'
                ],
            ]
        ];

        $elmtId = 134;

        $ugroups_permissions = $this->factory->getPermissionsFromFormElementData($elmtId, $formElementData);
        $this->assertTrue(isset($ugroups_permissions[$elmtId]));
        $this->assertTrue(isset($ugroups_permissions[$elmtId][1]));//ugroup_anonymous
        $this->assertTrue(isset($ugroups_permissions[$elmtId][2]));//ugroup_registered
        $this->assertTrue(isset($ugroups_permissions[$elmtId][1]['others']));
        $this->assertEquals(1, $ugroups_permissions[$elmtId][1]['others']);
        $this->assertEquals(0, $ugroups_permissions[$elmtId][2]['others']);
    }

    public function testGetPermissionFromFormElementDataSubmit(): void
    {
        $formElementData = [
            'permissions' => [
                $GLOBALS['UGROUP_ANONYMOUS']  => [
                    0 => 'PLUGIN_TRACKER_FIELD_UPDATE',
                    1 => 'PLUGIN_TRACKER_FIELD_SUBMIT'
                ],
                $GLOBALS['UGROUP_REGISTERED'] => [
                    0 => 'PLUGIN_TRACKER_FIELD_SUBMIT',
                    1 => 'PLUGIN_TRACKER_FIELD_READ'
                ],

            ]
        ];

        $elmtId = 134;

        $ugroups_permissions = $this->factory->getPermissionsFromFormElementData($elmtId, $formElementData);
        $this->assertTrue(isset($ugroups_permissions[$elmtId]));
        $this->assertTrue(isset($ugroups_permissions[$elmtId][1]));//ugroup_anonymous
        $this->assertTrue(isset($ugroups_permissions[$elmtId][2]));//ugroup_registered
        $this->assertTrue(isset($ugroups_permissions[$elmtId][1]['others']));
        $this->assertEquals(1, $ugroups_permissions[$elmtId][1]['others']);
        $this->assertEquals(0, $ugroups_permissions[$elmtId][2]['others']);
        $this->assertTrue(isset($ugroups_permissions[$elmtId][2]['submit']));
        $this->assertEquals('on', $ugroups_permissions[$elmtId][2]['submit']);
    }

    public function testGetFieldById(): void
    {
        $date     = Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $fieldset = Mockery::spy(\Tracker_FormElement_Container_Fieldset::class);

        $this->factory->shouldReceive('getFormElementById')->with(123)->andReturn($date);
        $this->factory->shouldReceive('getFormElementById')->with(456)->andReturn($fieldset);
        $this->factory->shouldReceive('getFormElementById')->with(789)->andReturn(null);

        $this->assertInstanceOf(Tracker_FormElement_Field::class, $this->factory->getFieldById(123));
        $this->assertNull($this->factory->getFieldById(456), 'A fieldset is not a Field');
        $this->assertNull($this->factory->getFieldById(789), 'Field does not exist');
    }

    public function testDeductNameFromLabel(): void
    {
        $label = 'titi est dans la brouSSe avec ro,min"ééééet';
        $label = $this->factory->deductNameFromLabel($label);
        $this->assertEquals('titi_est_dans_la_brousse_avec_rominet', $label);
    }

    public function testDisplayCreateFormShouldDisplayAForm(): void
    {
        $content = $this->whenIDisplayCreateFormElement();

        $this->assertStringContainsString('Create a new Separator', $content);
        $this->assertStringContainsString('</form>', $content);
    }

    private function whenIDisplayCreateFormElement(): string
    {
        $GLOBALS['Language']->shouldReceive('getText')
            ->with('plugin_tracker_formelement_admin', 'separator_label')->andReturns('Separator');

        $tracker_manager = Mockery::spy(\TrackerManager::class);
        $user            = Mockery::spy(\PFUser::class);
        $request         = Mockery::spy(\HTTPRequest::class);
        $tracker         = Mockery::spy(\Tracker::class);

        $this->dao->shouldReceive('searchUsedByTrackerId')->andReturn([]);
        $this->factory->shouldReceive('getDao')->andReturn($this->dao);

        ob_start();
        $this->factory->displayAdminCreateFormElement($tracker_manager, $request, $user, 'separator', $tracker);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function testItReturnsEmptyArrayWhenNoSharedFields(): void
    {
        $project_id = 1;
        $this->dao->shouldReceive('searchProjectSharedFieldsOriginals')
            ->withArgs([$project_id])->andReturns(TestHelper::emptyDar());
        $this->thenICompareProjectSharedFieldsWithExpectedResult($project_id, []);
    }

    public function testItReturnsAllSharedFieldsThatTheTrackerExports(): void
    {
        $project_id = 1;

        $sharedRow1 = $this->createRow(999, 'text');
        $sharedRow2 = $this->createRow(666, 'date');

        $dar = TestHelper::arrayToDar(
            $sharedRow1,
            $sharedRow2
        );

        $this->dao->shouldReceive('searchProjectSharedFieldsOriginals')
            ->withArgs([$project_id])->andReturns($dar);

        $project = Mockery::spy(\Project::class);
        $project->shouldReceive('getID')->andReturns($project_id);

        $result = $this->factory->getProjectSharedFields($project);

        $this->assertCount(2, $result);

        $found_fields = [];
        foreach ($result as $field) {
            if ($field instanceof Tracker_FormElement_Field_Date) {
                $found_fields['date'] = true;
                $this->assertEquals(666, $field->getId());
            }

            if ($field instanceof Tracker_FormElement_Field_Text) {
                $found_fields['text'] = true;
                $this->assertEquals(999, $field->getId());
            }
        }

        $this->assertEquals(['date' => true, 'text' => true], $found_fields);
    }


    private function thenICompareProjectSharedFieldsWithExpectedResult($project_id, $expectedResult): void
    {
        $project = Mockery::spy(\Project::class);
        $project->shouldReceive('getID')->andReturns($project_id);

        $this->assertEquals($expectedResult, $this->factory->getProjectSharedFields($project));
    }

    public function testItReturnsTheFieldsIfUserCanReadTheOriginalAndAllTargets(): void
    {
        $user    = Mockery::spy(\PFUser::class);
        $project = Mockery::spy(\Project::class);

        $readableField = Mockery::mock(\Tracker_FormElement::class);
        $readableField->shouldReceive('userCanRead')->withArgs([$user])->andReturnTrue();
        $targetOfReadableField1 = Mockery::mock(\Tracker_FormElement::class);
        $targetOfReadableField1->shouldReceive('userCanRead')->withArgs([$user])->andReturnTrue();
        $targetOfReadableField2 = Mockery::mock(\Tracker_FormElement::class);
        $targetOfReadableField2->shouldReceive('userCanRead')->withArgs([$user])->andReturnTrue();
        $unReadableField = Mockery::mock(\Tracker_FormElement::class);
        $unReadableField->shouldReceive('userCanRead')->withArgs([$user])->andReturnFalse();

        $this->factory->shouldReceive('getProjectSharedFields')->withArgs([$project])->andReturns(
            [$readableField, $unReadableField]
        );
        $this->factory->shouldReceive('getSharedTargets')->withArgs([$unReadableField])->andReturns([]);
        $this->factory->shouldReceive('getSharedTargets')->withArgs([$readableField])->andReturns(
            [$targetOfReadableField1, $targetOfReadableField2]
        );

        $this->assertEquals([$readableField], $this->factory->getSharedFieldsReadableBy($user, $project));
    }

    public function testItDoesntReturnAnythingIfUserCannotReadTheOriginalAndAllTheTargets(): void
    {
        $user    = Mockery::spy(\PFUser::class);
        $project = Mockery::spy(\Project::class);

        $aReadableField = Mockery::mock(\Tracker_FormElement::class);
        $aReadableField->shouldReceive('userCanRead')->withArgs([$user])->andReturnTrue();
        $targetOfReadableField1 = Mockery::mock(\Tracker_FormElement::class);
        $targetOfReadableField1->shouldReceive('userCanRead')->withArgs([$user])->andReturnFalse();
        $targetOfReadableField2 = Mockery::mock(\Tracker_FormElement::class);
        $targetOfReadableField2->shouldReceive('userCanRead')->withArgs([$user])->andReturnTrue();

        $this->factory->shouldReceive('getProjectSharedFields')->withArgs([$project])->andReturns([$aReadableField]);
        $this->factory->shouldReceive('getSharedTargets')->withArgs([$aReadableField])->andReturns(
            [$targetOfReadableField1, $targetOfReadableField2]
        );

        $this->assertEquals([], $this->factory->getSharedFieldsReadableBy($user, $project));
    }

    public function testItReturnsACollectionOfUniqueOriginals(): void
    {
        $user    = Mockery::spy(\PFUser::class);
        $project = Mockery::spy(\Project::class);

        $aReadableField = Mockery::mock(\Tracker_FormElement::class);
        $aReadableField->shouldReceive('userCanRead')->withArgs([$user])->andReturnTrue();
        $targetOfReadableField1 = Mockery::mock(\Tracker_FormElement::class);
        $targetOfReadableField1->shouldReceive('userCanRead')->withArgs([$user])->andReturnTrue();
        $targetOfReadableField2 = Mockery::mock(\Tracker_FormElement::class);
        $targetOfReadableField2->shouldReceive('userCanRead')->withArgs([$user])->andReturnTrue();


        $this->factory->shouldReceive('getProjectSharedFields')->withArgs([$project])->andReturns([$aReadableField]);
        $this->factory->shouldReceive('getSharedTargets')->withArgs([$aReadableField])->andReturns(
            [$targetOfReadableField1, $targetOfReadableField2]
        );

        $this->assertEquals([$aReadableField], $this->factory->getSharedFieldsReadableBy($user, $project));
    }

    private function createRow(int $id, string $type): array
    {
        return [
            'id'                => $id,
            'formElement_type'  => $type,
            'tracker_id'        => null,
            'parent_id'         => null,
            'name'              => null,
            'label'             => null,
            'description'       => null,
            'use_it'            => null,
            'scope'             => null,
            'required'          => null,
            'notifications'     => null,
            'rank'              => null,
            'original_field_id' => null
        ];
    }

    public function testGetFieldFromTrackerAndSharedField(): void
    {
        $original_field_dar = TestHelper::arrayToDar($this->createRow(999, 'text'));

        $this->dao->shouldReceive('searchFieldFromTrackerIdAndSharedFieldId')
            ->withArgs([66, 123])->andReturns($original_field_dar)->once();

        $originalField = Mockery::spy(Tracker_FormElement_Field_Text::class);
        $originalField->shouldReceive('getId')->andReturn(999);

        $exportedField = Mockery::spy(Tracker_FormElement_Field_Text::class);
        $exportedField->shouldReceive('getId')->andReturn(123);

        $field = $this->factory->getFieldFromTrackerAndSharedField($this->tracker, $exportedField);
        $this->assertEquals(
            $originalField->getId(),
            $field->getId()
        );
    }

    public function testItDoesNothingWhenFieldMappingIsEmpty(): void
    {
        $template_project_field_ids = [];
        $new_project_shared_fields  = [];
        $field_mapping              = [];

        $this->dao->shouldReceive('searchProjectSharedFieldsTargets')->with($this->project_id)->andReturns(
            $new_project_shared_fields
        );
        $this->dao->shouldReceive('searchFieldIdsByGroupId')->with($this->template_id)->andReturns(
            $template_project_field_ids
        );

        $this->dao->shouldReceive('updateOriginalFieldId')->never();

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function testItDoesNothingWhenThereIsNoSharedFieldInTheFieldMapping(): void
    {
        $template_project_field_ids = [321];
        $new_project_shared_fields  = [];
        $field_mapping              = [['from' => 321, 'to' => 101]];

        $this->dao->shouldReceive('searchProjectSharedFieldsTargets')->with($this->project_id)->andReturns(
            $new_project_shared_fields
        );
        $this->dao->shouldReceive('searchFieldIdsByGroupId')->with($this->template_id)->andReturns(
            $template_project_field_ids
        );

        $this->dao->shouldReceive('updateOriginalFieldId')->never();

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function testItUpdatesTheOrginalFieldIdForEverySharedField(): void
    {
        $template_project_field_ids = [999, 103, 555, 666];

        $new_project_shared_field_1 = ['id' => 234, 'original_field_id' => 666];
        $new_project_shared_field_2 = ['id' => 567, 'original_field_id' => 555];
        $new_project_shared_fields  = [$new_project_shared_field_1, $new_project_shared_field_2];

        $field_mapping = [
            ['from' => 999, 'to' => 234],
            ['from' => 103, 'to' => 567],
            ['from' => 555, 'to' => 888, 'values' => [1 => 2]],
            ['from' => 666, 'to' => 777, 'values' => [3 => 4, 5 => 6]]
        ];

        $this->dao->shouldReceive('searchProjectSharedFieldsTargets')->with($this->project_id)
            ->andReturns($new_project_shared_fields);
        $this->dao->shouldReceive('searchFieldIdsByGroupId')->with($this->template_id)
            ->andReturns($template_project_field_ids);

        $this->dao->shouldReceive('updateOriginalFieldId')->with(234, 777)->ordered();
        $this->dao->shouldReceive('updateOriginalFieldId')->with(567, 888)->ordered();

        $field_234 = \Mockery::spy(\Tracker_FormElement_Field_Shareable::class);
        $this->factory->shouldReceive('getShareableFieldById')->with(234)->andReturns($field_234);

        $field_567 = \Mockery::spy(\Tracker_FormElement_Field_Shareable::class);
        $this->factory->shouldReceive('getShareableFieldById')->with(567)->andReturns($field_567);

        $field_234->shouldReceive('fixOriginalValueIds')->with([3 => 4, 5 => 6])->ordered();
        $field_567->shouldReceive('fixOriginalValueIds')->with([1 => 2])->ordered();

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function testItDoesntUpdateWhenTheOriginalFieldIdRefersToAfieldOutsideTheTemplateProject(): void
    {
        $template_project_field_ids = [999, 103, 666];

        $new_project_internal_shared_field = ['id' => 234, 'original_field_id' => 666];
        $new_project_external_shared_field = ['id' => 567, 'original_field_id' => 555];
        $new_project_shared_fields         = [$new_project_internal_shared_field, $new_project_external_shared_field];

        $field_mapping = [
            ['from' => 999, 'to' => 234],
            ['from' => 103, 'to' => 567],
            ['from' => 666, 'to' => 777, 'values' => [1 => 2, 3 => 4]]
        ];

        $this->dao->shouldReceive('searchProjectSharedFieldsTargets')->with($this->project_id)
            ->andReturns($new_project_shared_fields);
        $this->dao->shouldReceive('searchFieldIdsByGroupId')->with($this->template_id)
            ->andReturns($template_project_field_ids);

        $field_234 = \Mockery::spy(\Tracker_FormElement_Field_Shareable::class);
        $this->factory->shouldReceive('getShareableFieldById')->with(234)->andReturns($field_234);

        $this->dao->shouldReceive('updateOriginalFieldId')->with(234, 777)->once();
        $field_234->shouldReceive('fixOriginalValueIds')->with([1 => 2, 3 => 4])->once();

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function testItReturnsNullIfThereAreNoArtifactLinkFields(): void
    {
        $this->factory->shouldReceive('getUsedArtifactLinkFields')->with($this->tracker)->andReturns(array());
        $this->assertNull($this->factory->getAnArtifactLinkField($this->user, $this->tracker));
    }

    public function testItReturnsNullIfUserCannotSeeArtifactLinkField(): void
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('userCanRead')->with($this->user)->andReturnFalse();

        $this->factory->shouldReceive('getUsedArtifactLinkFields')->with($this->tracker)->andReturn([$field]);
        $this->assertNull($this->factory->getAnArtifactLinkField($this->user, $this->tracker));
    }

    public function testItReturnsFieldIfUserCanSeeArtifactLinkField(): void
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('userCanRead')->with($this->user)->andReturnTrue();

        $this->factory->shouldReceive('getUsedArtifactLinkFields')->with($this->tracker)->andReturn([$field]);
        $this->assertEquals($field, $this->factory->getAnArtifactLinkField($this->user, $this->tracker));
    }
}
