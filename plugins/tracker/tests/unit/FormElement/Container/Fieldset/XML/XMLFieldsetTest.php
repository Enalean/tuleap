<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Container\Fieldset\XML;

use Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLFieldsetTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItHasAttributes(): void
    {
        $fieldset = new XMLFieldset('some_id', 'details');

        $xml = $fieldset->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertEquals('some_id', (string) $xml['ID']);
        assertEquals(\Tracker_FormElementFactory::CONTAINER_FIELDSET_TYPE, (string) $xml['type']);
        assertEquals('details', (string) $xml->name);
        assertEquals('details', (string) $xml->label);
    }

    public function testItHasOneChildFormElement(): void
    {
        $fieldset = (new XMLFieldset('some_id', 'details'))
            ->withFormElements(new XMLColumn('some_column', ''));

        $xml = $fieldset->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertCount(1, $xml->formElements->formElement);
        assertEquals('some_column', $xml->formElements->formElement[0]['ID']);
    }

    public function testItHasTwoChildrenFormElements(): void
    {
        $fieldset = (new XMLFieldset('some_id', 'details'))
            ->withFormElements(
                new XMLColumn('column1', ''),
                new XMLColumn('column2', ''),
            );

        $xml = $fieldset->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertCount(2, $xml->formElements->formElement);
        assertEquals('column1', $xml->formElements->formElement[0]['ID']);
        assertEquals('column2', $xml->formElements->formElement[1]['ID']);
    }

    public function testItExportTheFieldSetLabelUnModified(): void
    {
        $fieldset     = new \Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer(
            100,
            100,
            0,
            'fieldset_1',
            'fieldset_status_bugs_lbl_key',
            'fieldset_status_bugs_desc_key',
            true,
            null,
            false,
            false,
            1,
        );
        $xml_fieldset = (new XMLFieldset('F100', 'fieldset_1'))->fromFormElement($fieldset);

        $xml = $xml_fieldset->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertEquals('fieldset_status_bugs_lbl_key', (string) $xml->label);
    }
}
