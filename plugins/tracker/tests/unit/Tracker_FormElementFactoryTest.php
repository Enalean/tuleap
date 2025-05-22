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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\CSRF\CSRFSessionKeyStorageStub;
use Tuleap\Test\Stubs\CSRF\CSRFSigningKeyStorageStub;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElementFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use Tuleap\GlobalLanguageMock;
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\TemporaryTestDirectory;

    private int $template_id = 29;

    private int $project_id = 3;

    private FieldDao&MockObject $dao;

    private Tracker $tracker;

    private PFUser $user;

    private Tracker_FormElementFactory&MockObject $factory;

    protected function setUp(): void
    {
        $GLOBALS['HTML'] = $this->createMock(Layout::class);
        $GLOBALS['HTML']->method('getImagePath');
        $GLOBALS['HTML']->method('selectRank');

        $this->dao = $this->createMock(FieldDao::class);

        $this->factory = $this->createPartialMock(Tracker_FormElementFactory::class, [
            'getDao',
            'createFormElement',
            'getFormElementById',
            'getShareableFieldById',
            'getUsedArtifactLinkFields',
        ]);

        $this->factory->method('getDao')->willReturn($this->dao);

        $this->user    = UserTestBuilder::buildWithDefaults();
        $this->tracker = TrackerTestBuilder::aTracker()->withId(66)->build();

        \ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['HTML']);
    }

    public function testSaveObject(): void
    {
        $a_formelement = $this->createMock(Tracker_FormElement_Container_Fieldset::class);

        $a_formelement->method('getFormElementDataForCreation');
        $a_formelement->expects($this->once())->method('afterSaveObject');
        $a_formelement->expects($this->once())->method('setId')->with(66);
        $a_formelement->method('getFlattenPropertiesValues')->willReturn([]);

        $this->factory->method('createFormElement')->willReturn(66);

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
                    1 => 'PLUGIN_TRACKER_FIELD_UPDATE',
                ],
                $GLOBALS['UGROUP_REGISTERED'] => [
                    0 => 'PLUGIN_TRACKER_FIELD_UPDATE',
                    1 => 'PLUGIN_TRACKER_FIELD_READ',
                ],
            ],
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
                    1 => 'PLUGIN_TRACKER_FIELD_SUBMIT',
                ],
                $GLOBALS['UGROUP_REGISTERED'] => [
                    0 => 'PLUGIN_TRACKER_FIELD_SUBMIT',
                    1 => 'PLUGIN_TRACKER_FIELD_READ',
                ],

            ],
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
        $date     = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $fieldset = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);

        $this->factory->method('getFormElementById')->willReturnCallback(
            static fn (int $id) => match ($id) {
                123 => $date,
                456 => $fieldset,
                default => null,
            }
        );

        $this->assertInstanceOf(Tracker_FormElement_Field::class, $this->factory->getFieldById(123));
        $this->assertNull($this->factory->getFieldById(456), 'A fieldset is not a Field');
        $this->assertNull($this->factory->getFieldById(789), 'Field does not exist');
    }

    public function testDisplayCreateFormShouldDisplayAForm(): void
    {
        $content = $this->whenIDisplayCreateFormElement();

        $this->assertStringContainsString('Create a new Separator', $content);
        $this->assertStringContainsString('</form>', $content);
    }

    private function whenIDisplayCreateFormElement(): string
    {
        $tracker_manager = $this->createMock(\TrackerManager::class);
        $request         = HTTPRequestBuilder::get()->build();
        $tracker         = $this->createMock(Tracker::class);
        $tracker->method('getId');
        $tracker->method('displayAdminFormElementsHeader');
        $tracker->method('displayFooter');

        $this->dao->method('searchUsedByTrackerId')->willReturn([]);

        $csrf_token = new CSRFSynchronizerToken('form_element', 'token', new CSRFSigningKeyStorageStub(), new CSRFSessionKeyStorageStub());

        ob_start();
        $this->factory->displayAdminCreateFormElement($tracker_manager, $request, 'separator', $tracker, $csrf_token);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function testItReturnsEmptyArrayWhenNoSharedFields(): void
    {
        $project_id = 1;
        $this->dao->method('searchProjectSharedFieldsOriginals')
            ->with($project_id)->willReturn(TestHelper::emptyDar());
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

        $this->dao->method('searchProjectSharedFieldsOriginals')
            ->with($project_id)->willReturn($dar);

        $project = ProjectTestBuilder::aProject()->withId($project_id)->build();

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
        $project = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $this->assertEquals($expectedResult, $this->factory->getProjectSharedFields($project));
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
            'original_field_id' => null,
        ];
    }

    public function testItDoesNothingWhenFieldMappingIsEmpty(): void
    {
        $template_project_field_ids = [];
        $new_project_shared_fields  = [];
        $field_mapping              = [];

        $this->dao->method('searchProjectSharedFieldsTargets')->with($this->project_id)->willReturn(
            $new_project_shared_fields
        );
        $this->dao->method('searchFieldIdsByGroupId')->with($this->template_id)->willReturn(
            $template_project_field_ids
        );

        $this->dao->expects($this->never())->method('updateOriginalFieldId');

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function testItDoesNothingWhenThereIsNoSharedFieldInTheFieldMapping(): void
    {
        $template_project_field_ids = [321];
        $new_project_shared_fields  = [];
        $field_mapping              = [['from' => 321, 'to' => 101]];

        $this->dao->method('searchProjectSharedFieldsTargets')->with($this->project_id)->willReturn(
            $new_project_shared_fields
        );
        $this->dao->method('searchFieldIdsByGroupId')->with($this->template_id)->willReturn(
            $template_project_field_ids
        );

        $this->dao->expects($this->never())->method('updateOriginalFieldId');

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
            ['from' => 666, 'to' => 777, 'values' => [3 => 4, 5 => 6]],
        ];

        $this->dao->method('searchProjectSharedFieldsTargets')->with($this->project_id)
            ->willReturn($new_project_shared_fields);
        $this->dao->method('searchFieldIdsByGroupId')->with($this->template_id)
            ->willReturn($template_project_field_ids);

        $this->dao->method('updateOriginalFieldId')->willReturnCallback(
            static fn (int $id, int $original_field_id) => match (true) {
                $id === 234 && $original_field_id === 777,
                    $id === 567 && $original_field_id === 888 => true,
            }
        );

        $field_234 = $this->createMock(\Tracker_FormElement_Field_Shareable::class);

        $field_567 = $this->createMock(\Tracker_FormElement_Field_Shareable::class);
        $this->factory->method('getShareableFieldById')->willReturnCallback(
            static fn (int $id) => match ($id) {
                234 => $field_234,
                567 => $field_567,
            }
        );

        $field_234->expects($this->atLeast(1))->method('fixOriginalValueIds')->with([3 => 4, 5 => 6]);
        $field_567->expects($this->atLeast(1))->method('fixOriginalValueIds')->with([1 => 2]);

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
            ['from' => 666, 'to' => 777, 'values' => [1 => 2, 3 => 4]],
        ];

        $this->dao->method('searchProjectSharedFieldsTargets')->with($this->project_id)
            ->willReturn($new_project_shared_fields);
        $this->dao->method('searchFieldIdsByGroupId')->with($this->template_id)
            ->willReturn($template_project_field_ids);

        $field_234 = $this->createMock(\Tracker_FormElement_Field_Shareable::class);
        $this->factory->method('getShareableFieldById')->with(234)->willReturn($field_234);

        $this->dao->expects($this->once())->method('updateOriginalFieldId')->with(234, 777);
        $field_234->expects($this->once())->method('fixOriginalValueIds')->with([1 => 2, 3 => 4]);

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function testItReturnsNullIfThereAreNoArtifactLinkFields(): void
    {
        $this->factory->method('getUsedArtifactLinkFields')->with($this->tracker)->willReturn([]);
        $this->assertNull($this->factory->getAnArtifactLinkField($this->user, $this->tracker));
    }

    public function testItReturnsNullIfUserCannotSeeArtifactLinkField(): void
    {
        $field = $this->createMock(ArtifactLinkField::class);
        $field->method('userCanRead')->with($this->user)->willReturn(false);

        $this->factory->method('getUsedArtifactLinkFields')->with($this->tracker)->willReturn([$field]);
        $this->assertNull($this->factory->getAnArtifactLinkField($this->user, $this->tracker));
    }

    public function testItReturnsFieldIfUserCanSeeArtifactLinkField(): void
    {
        $field = $this->createMock(ArtifactLinkField::class);
        $field->method('userCanRead')->with($this->user)->willReturn(true);

        $this->factory->method('getUsedArtifactLinkFields')->with($this->tracker)->willReturn([$field]);
        $this->assertEquals($field, $this->factory->getAnArtifactLinkField($this->user, $this->tracker));
    }
}
