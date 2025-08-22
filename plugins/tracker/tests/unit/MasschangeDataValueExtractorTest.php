<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use PHPUnit\Framework\Attributes\DataProvider;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tracker_FormElementFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Masschange\MasschangeDataValueExtractor;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MasschangeDataValueExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @param class-string $field_class
     */
    #[DataProvider('dataProviderFields')]
    public function testReturnsFieldWithNewValue(string $field_class, mixed $value, bool $is_expected_to_set_value): void
    {
        $field = $this->createStub($field_class);

        $form_element_factory = $this->createStub(Tracker_FormElementFactory::class);
        $form_element_factory->method('getFieldById')->willReturn($field);

        $masschange_data = [12 => $value];

        $expected_result = $is_expected_to_set_value ? $masschange_data : [];

        $GLOBALS['Language']->method('getText')->willReturn('Unchanged');

        $masschange_data_values_extractor = new MasschangeDataValueExtractor($form_element_factory);

        $this->assertEquals(
            $expected_result,
            $masschange_data_values_extractor->getNewValues($masschange_data)
        );
    }

    public function testReturnsFieldWithNewValueRemovingUnchangedValueForMultiSelectboxField(): void
    {
        $field = $this->createStub(ListField::class);

        $form_element_factory = $this->createStub(Tracker_FormElementFactory::class);
        $form_element_factory->method('getFieldById')->willReturn($field);

        $masschange_data = [12 => ['-1', 'Value02']];

        $GLOBALS['Language']->method('getText')->willReturn('Unchanged');

        $masschange_data_values_extractor = new MasschangeDataValueExtractor($form_element_factory);

        $this->assertEquals(
            [12 => [1 => 'Value02']],
            $masschange_data_values_extractor->getNewValues($masschange_data)
        );
    }

    public static function dataProviderFields(): array
    {
        return [
            'Field with an update' => [
                TextField::class,
                'Value01',
                true,
            ],
            'Field without an update' => [
                TextField::class,
                'Unchanged',
                false,
            ],
            'List field with an update' => [
                ListField::class,
                ['Value02'],
                true,
            ],
            'List field without an update' => [
                ListField::class,
                ['-1'],
                false,
            ],
            'Permissions on artifact field with an update' => [
                Tracker_FormElement_Field_PermissionsOnArtifact::class,
                ['do_mass_update' => '1', 'use_artifact_permissions' => '1'],
                true,
            ],
            'Permissions on artifact field without an update' => [
                Tracker_FormElement_Field_PermissionsOnArtifact::class,
                ['use_artifact_permissions' => '1'],
                false,
            ],
        ];
    }
}
