<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tracker_FormElementFactory;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BindValueIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoDuckTypedMatchingValueException;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\StatusFieldReferenceStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusValueMapperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_BIND_VALUE_ID  = 1287;
    private const SECOND_BIND_VALUE_ID = 3409;
    private const THIRD_BIND_VALUE_ID  = 9264;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
    }

    private function getMapper(): StatusValueMapper
    {
        return new StatusValueMapper($this->form_element_factory);
    }

    public static function dataProviderMatchingValue(): array
    {
        return [
            'It matches value by label'                                           => [
                '2',
                self::SECOND_BIND_VALUE_ID,
                new \Tracker_FormElement_Field_List_Bind_StaticValue(self::FIRST_BIND_VALUE_ID, '1', 'Irrelevant', 0, false),
                new \Tracker_FormElement_Field_List_Bind_StaticValue(self::SECOND_BIND_VALUE_ID, '2', 'Irrelevant', 1, false),
            ],
            'It matches value label with different cases'                         => [
                'a',
                self::FIRST_BIND_VALUE_ID,
                new \Tracker_FormElement_Field_List_Bind_StaticValue(self::FIRST_BIND_VALUE_ID, 'A', 'Irrelevant', 0, false),
                new \Tracker_FormElement_Field_List_Bind_StaticValue(self::SECOND_BIND_VALUE_ID, 'b', 'Irrelevant', 1, false),
            ],
            'It matches value even if it is hidden'                               => [
                '2',
                self::SECOND_BIND_VALUE_ID,
                new \Tracker_FormElement_Field_List_Bind_StaticValue(self::FIRST_BIND_VALUE_ID, '1', 'Irrelevant', 0, false),
                new \Tracker_FormElement_Field_List_Bind_StaticValue(self::SECOND_BIND_VALUE_ID, '2', 'Irrelevant', 1, true),
            ],
            'It matches first value if multiple values have the same label'       => [
                '1',
                self::FIRST_BIND_VALUE_ID,
                new \Tracker_FormElement_Field_List_Bind_StaticValue(self::FIRST_BIND_VALUE_ID, '1', 'Irrelevant', 0, false),
                new \Tracker_FormElement_Field_List_Bind_StaticValue(self::SECOND_BIND_VALUE_ID, '1', 'Irrelevant', 1, false),
            ],
            'It matches user bind values by display name'                         => [
                'Celia Apollo',
                self::SECOND_BIND_VALUE_ID,
                new \Tracker_FormElement_Field_List_Bind_UsersValue(self::FIRST_BIND_VALUE_ID, 'Irrelevant', 'Mildred Favorito'),
                new \Tracker_FormElement_Field_List_Bind_UsersValue(self::SECOND_BIND_VALUE_ID, 'Irrelevant', 'Celia Apollo'),
            ],
            'It matches username with different case'                             => [
                'CELIA APOLLO',
                self::FIRST_BIND_VALUE_ID,
                new \Tracker_FormElement_Field_List_Bind_UsersValue(self::FIRST_BIND_VALUE_ID, 'Irrelevant', 'Celia Apollo'),
                new \Tracker_FormElement_Field_List_Bind_UsersValue(self::SECOND_BIND_VALUE_ID, 'Irrelevant', 'Mildred Favorito'),
            ],
            'It matches first value if multiple users have the same display name' => [
                'Celia Apollo',
                self::FIRST_BIND_VALUE_ID,
                new \Tracker_FormElement_Field_List_Bind_UsersValue(self::FIRST_BIND_VALUE_ID, 'Irrelevant', 'Celia Apollo'),
                new \Tracker_FormElement_Field_List_Bind_UsersValue(self::SECOND_BIND_VALUE_ID, 'Irrelevant', 'Celia Apollo'),
            ],
            'It matches dynamic user group name'                                  => [
                'project_members',
                self::SECOND_BIND_VALUE_ID,
                self::buildUserGroupValue(self::FIRST_BIND_VALUE_ID, 905, 'palaeoclimatic', false),
                self::buildUserGroupValue(self::SECOND_BIND_VALUE_ID, \ProjectUGroup::PROJECT_MEMBERS, \ProjectUGroup::NORMALIZED_NAMES[\ProjectUGroup::PROJECT_MEMBERS], false),
            ],
            'It matches static user group name'                                   => [
                'palaeoclimatic',
                self::FIRST_BIND_VALUE_ID,
                self::buildUserGroupValue(self::FIRST_BIND_VALUE_ID, 905, 'palaeoclimatic', false),
                self::buildUserGroupValue(self::SECOND_BIND_VALUE_ID, 921, 'tolidine', false),
            ],
            'It matches static user group name with different case'               => [
                'PALAEOCLIMATIC',
                self::FIRST_BIND_VALUE_ID,
                self::buildUserGroupValue(self::FIRST_BIND_VALUE_ID, 905, 'palaeoclimatic', false),
            ],
            'It matches user group even it if is hidden'                          => [
                'palaeoclimatic',
                self::SECOND_BIND_VALUE_ID,
                self::buildUserGroupValue(self::FIRST_BIND_VALUE_ID, 921, 'tolidine', false),
                self::buildUserGroupValue(self::SECOND_BIND_VALUE_ID, 905, 'palaeoclimatic', true),
            ],
            'It matches first value if multiple user groups have the same name'   => [
                'palaeoclimatic',
                self::FIRST_BIND_VALUE_ID,
                self::buildUserGroupValue(self::FIRST_BIND_VALUE_ID, 905, 'palaeoclimatic', false),
                self::buildUserGroupValue(self::SECOND_BIND_VALUE_ID, 303, 'palaeoclimatic', false),
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderMatchingValue')]
    public function testItMapsValuesByDuckTyping(
        string $source_label,
        int $expected_bind_value_id,
        \Tracker_FormElement_Field_List_BindValue ...$values,
    ): void {
        $status_value = $this->buildStatusValueWithLabels($source_label);
        $status_field = $this->buildStatusFieldWithBindValues(...$values);

        $result         = $this->getMapper()->mapStatusValueByDuckTyping(
            $status_value,
            StatusFieldReferenceProxy::fromTrackerField($status_field)
        );
        $bind_value_ids = array_map(static fn(BindValueIdentifier $identifier): int => $identifier->getId(), $result);
        self::assertContains($expected_bind_value_id, $bind_value_ids);
    }

    public function testItMapsMultipleValuesByDuckTyping(): void
    {
        $status_value = $this->buildStatusValueWithLabels('Not found', 'Planned');
        $status_field = $this->buildStatusFieldWithLabels('Planned', 'Not found', 'Other value');

        $result         = $this->getMapper()->mapStatusValueByDuckTyping(
            $status_value,
            StatusFieldReferenceProxy::fromTrackerField($status_field)
        );
        $bind_value_ids = array_map(static fn(BindValueIdentifier $identifier): int => $identifier->getId(), $result);
        self::assertContains(self::FIRST_BIND_VALUE_ID, $bind_value_ids);
        self::assertContains(self::SECOND_BIND_VALUE_ID, $bind_value_ids);
        self::assertNotContains(self::THIRD_BIND_VALUE_ID, $bind_value_ids);
    }

    public function testItThrowsWhenOneValueCannotBeMapped(): void
    {
        $status_value = $this->buildStatusValueWithLabels('Not found', 'Planned');
        $status_field = $this->buildStatusFieldWithLabels('NOT MATCHING', 'not matching either', 'Nope');

        $this->expectException(NoDuckTypedMatchingValueException::class);
        $this->getMapper()->mapStatusValueByDuckTyping(
            $status_value,
            StatusFieldReferenceProxy::fromTrackerField($status_field)
        );
    }

    private function buildStatusValueWithLabels(string ...$values): StatusValue
    {
        return StatusValue::fromStatusReference(
            RetrieveStatusValuesStub::withValues(...$values),
            StatusFieldReferenceStub::withDefaults()
        );
    }

    private function buildStatusFieldWithLabels(
        string $first_label,
        string $second_label,
        string $third_label,
    ): \Tracker_FormElement_Field_List {
        $first_value  = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::FIRST_BIND_VALUE_ID,
            $first_label,
            'Irrelevant',
            0,
            false
        );
        $second_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::SECOND_BIND_VALUE_ID,
            $second_label,
            'Irrelevant',
            0,
            false
        );
        $third_value  = new \Tracker_FormElement_Field_List_Bind_StaticValue(
            self::THIRD_BIND_VALUE_ID,
            $third_label,
            'Irrelevant',
            0,
            false
        );

        $static_bind = $this->createStub(\Tracker_FormElement_Field_List_Bind_Static::class);
        $static_bind->method('getAllValues')->willReturn([$first_value, $second_value, $third_value]);
        $status_field = $this->createStub(\Tracker_FormElement_Field_List::class);
        $status_field->method('getBind')->willReturn($static_bind);
        $status_field->method('getId')->willReturn(1984);
        $status_field->method('getTrackerId')->willReturn(54);
        $status_field->method('getLabel')->willReturn('Status');

        $this->form_element_factory->method('getFieldById')->with(1984)->willReturn($status_field);

        return $status_field;
    }

    private function buildStatusFieldWithBindValues(
        \Tracker_FormElement_Field_List_BindValue ...$bind_values,
    ): \Tracker_FormElement_Field_List {
        $static_bind = $this->createStub(\Tracker_FormElement_Field_List_Bind::class);
        $static_bind->method('getAllValues')->willReturn($bind_values);
        $status_field = $this->createStub(\Tracker_FormElement_Field_List::class);
        $status_field->method('getBind')->willReturn($static_bind);
        $status_field->method('getId')->willReturn(101);
        $status_field->method('getLabel')->willReturn('Status');

        $this->form_element_factory->method('getFieldById')->with(101)->willReturn($status_field);

        return $status_field;
    }

    private static function buildUserGroupValue(
        int $bind_value_id,
        int $user_group_id,
        string $user_group_name,
        bool $is_hidden,
    ): \Tracker_FormElement_Field_List_Bind_UgroupsValue {
        return new \Tracker_FormElement_Field_List_Bind_UgroupsValue(
            $bind_value_id,
            new \ProjectUGroup(['ugroup_id' => $user_group_id, 'name' => $user_group_name]),
            $is_hidden
        );
    }
}
