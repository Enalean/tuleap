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

namespace Tuleap\Tracker\Report\XML;

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceByLabel;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByID;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLReportCriterionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItSimpleSearchWhenThereIsOnlyOneValueSelected(): void
    {
        $form_elements = new XMLFormElementFlattenedCollection([
            'status' => (new XMLSelectBoxField('F1', 'status'))
                ->withStaticValues(
                    new XMLBindStaticValue('V1', 'On going'),
                    new XMLBindStaticValue('V2', 'Done'),
                ),
        ]);

        $criterion = (new XMLReportCriterion(new XMLReferenceByID('F1')))
            ->withSelectedValues(new XMLBindValueReferenceByLabel('status', 'On going'));

        $xml = $criterion->export(new \SimpleXMLElement('<report />'), $form_elements);

        assertNull($xml['is_advanced']);
        assertCount(1, $xml->criteria_value->selected_value);
    }

    public function testItIsAdvancedWhenThereAreSeveralCriteria(): void
    {
        $form_elements = new XMLFormElementFlattenedCollection([
            'status' => (new XMLSelectBoxField('F1', 'status'))
                ->withStaticValues(
                    new XMLBindStaticValue('V1', 'On going'),
                    new XMLBindStaticValue('V2', 'Done'),
                ),
        ]);

        $criterion = (new XMLReportCriterion(new XMLReferenceByID('F1')))
            ->withSelectedValues(new XMLBindValueReferenceByLabel('status', 'On going'))
            ->withSelectedValues(new XMLBindValueReferenceByLabel('status', 'Done'));

        $xml = $criterion->export(new \SimpleXMLElement('<report />'), $form_elements);

        assertEquals('1', $xml['is_advanced']);
        assertCount(2, $xml->criteria_value->selected_value);
    }
}
