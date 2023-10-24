<?php
/**
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

namespace Tuleap\Velocity\JiraImporter;

use Tuleap\JiraImport\JiraAgile\ScrumTrackerBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn;
use Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset;
use Tuleap\Tracker\XML\XMLTracker;

final class AddVelocityToScrumTemplateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExportVelocityFieldAndSemantic(): void
    {
        $tracker = (new XMLTracker('T1', 'bug'))
            ->withFormElement(
                (new XMLFieldset('F100', 'details'))
                ->withFormElements(
                    new XMLColumn('F200', ScrumTrackerBuilder::DETAILS_RIGHT_COLUMN_NAME)
                )
            );

        $tracker = (new AddVelocityToScrumTemplate())
            ->addVelocityToStructure($tracker, new FieldAndValueIDGenerator());

        $xml = $tracker->export(new \SimpleXMLElement('<trackers />'));

        $column_content = $xml->xpath('/trackers/tracker/formElements/formElement[@ID="F100"]/formElements/formElement[@ID="F200"]/formElements');
        self::assertNotNull($column_content);
        self::assertCount(1, $column_content);
        $first_column_content = $column_content[0];
        self::assertNotNull($first_column_content);
        self::assertCount(1, $first_column_content->formElement);
        $form_element = $first_column_content->formElement[0];
        self::assertNotNull($form_element);
        self::assertEquals('velocity', $form_element->name);
        self::assertEquals('float', $form_element['type']);
        $field_id = $form_element['ID'];

        $semantics = $xml->xpath('/trackers/tracker/semantics/semantic[@type="velocity"]');
        self::assertNotNull($semantics);
        self::assertCount(1, $semantics);
        $semantic = $semantics[0];
        self::assertEquals($field_id, $semantic->field['REF']);
    }

    public function testItCannotAddWhenColumnIsNotPresent(): void
    {
        $tracker = (new XMLTracker('T1', 'bug'));

        $tracker = (new AddVelocityToScrumTemplate())
            ->addVelocityToStructure($tracker, new FieldAndValueIDGenerator());

        $this->expectException(\LogicException::class);

        $tracker->export(new \SimpleXMLElement('<trackers />'));
    }
}
