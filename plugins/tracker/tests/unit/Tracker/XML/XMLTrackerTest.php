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

namespace Tuleap\Tracker\XML;

use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Artifact\Changeset\XML\XMLChangeset;
use Tuleap\Tracker\Artifact\XML\XMLArtifact;
use Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset;
use Tuleap\Tracker\FormElement\Field\Date\XML\XMLDateField;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringValue;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByID;
use Tuleap\Tracker\Semantic\Timeframe\XML\XMLTimeframeSemantic;
use Tuleap\Tracker\Semantic\Title\XML\XMLTitleSemantic;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField;
use Tuleap\Tracker\FormElement\Field\XML\ReadPermission;
use Tuleap\Tracker\FormElement\Field\XML\SubmitPermission;
use Tuleap\Tracker\FormElement\Field\XML\UpdatePermission;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Report\XML\XMLReport;
use Tuleap\Tracker\Report\XML\XMLReportCriterion;
use Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable;
use Tuleap\Tracker\Report\Renderer\Table\Column\XML\XMLTableColumn;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

/**
 * This test class intend to test not only the XMLTracker class itself but to provide an integrated test of
 * the whole tracker export
 *
 * @covers \Tuleap\Tracker\XML\XMLTracker
 * @covers \Tuleap\Tracker\FormElement\XML\XMLReferenceByID
 * @covers \Tuleap\Tracker\FormElement\XML\XMLReferenceByName
 * @covers \Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset
 * @covers \Tuleap\Tracker\FormElement\Field\XML\XMLFieldPermission
 * @covers \Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField
 * @covers \Tuleap\Tracker\Semantic\Title\XML\XMLTitleSemantic
 * @covers \Tuleap\Tracker\Semantic\Timeframe\XML\XMLTimeframeSemantic
 * @covers \Tuleap\Tracker\Report\XML\XMLReport
 * @covers \Tuleap\Tracker\Report\XML\XMLReportCriterion
 * @covers \Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable
 * @covers \Tuleap\Tracker\Report\Renderer\Table\Column\XML\XMLTableColumn
 * @covers \Tuleap\Tracker\Artifact\XML\XMLArtifact
 * @covers \Tuleap\Tracker\Artifact\Changeset\XML\XMLChangeset
 * @covers \Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringValue
 */
class XMLTrackerTest extends TestCase
{
    public function testExportsOneTracker(): void
    {
        $tracker = (new XMLTracker('some_xml_id', 'bug'))
            ->withName('Bugs')
            ->withDescription('Collect issues');
        $xml     = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $tracker->export($xml);

        assertCount(1, $xml->tracker);
        assertEquals('some_xml_id', (string) $xml->tracker[0]['id']);
        assertEquals('0', (string) $xml->tracker[0]['parent_id']);
        assertEquals('Bugs', (string) $xml->tracker[0]->name);
        assertEquals('bug', (string) $xml->tracker[0]->item_name);
        assertEquals('Collect issues', (string) $xml->tracker[0]->description);
        assertEquals('inca-silver', (string) $xml->tracker[0]->color);
        assertFalse(isset($xml->tracker[0]->submit_instructions));
        assertFalse(isset($xml->tracker[0]->browse_instructions));
    }

    public function testExportSubmitInstructions(): void
    {
        $tracker = (new XMLTracker('some_xml_id', 'bug'))
            ->withSubmitInstructions('Here are the rules');

        $xml = $tracker->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertEquals('Here are the rules', (string) $xml->submit_instructions);
    }

    public function testExportBrowseInstructions(): void
    {
        $tracker = (new XMLTracker('some_xml_id', 'bug'))
            ->withBrowseInstructions('Here are the bugs');

        $xml = $tracker->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertEquals('Here are the bugs', (string) $xml->browse_instructions);
    }

    public function testExportFromTrackerObject(): void
    {
        $tracker = XMLTracker::fromTracker(new \Tracker(
            23,
            115,
            'Bugs',
            'Collect issues',
            'bug',
            false,
            'Here are the rules',
            'See all my stuff',
            null,
            null,
            false,
            false,
            false,
            TrackerColor::fromName('flamingo-pink'),
            false,
        ));

        $xml = $tracker->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));
        assertEquals('T23', (string) $xml['id']);
        assertEquals('Bugs', (string) $xml->name);
        assertEquals('bug', (string) $xml->item_name);
        assertEquals('Collect issues', (string) $xml->description);
        assertEquals('Here are the rules', (string) $xml->submit_instructions);
        assertEquals('See all my stuff', (string) $xml->browse_instructions);
    }

    public function testExportsOneTrackerWithIDGenerator(): void
    {
        $id_generator = new class implements IDGenerator
        {
            public function getNextId(): int
            {
                return 58;
            }
        };

        $tracker = new XMLTracker($id_generator, 'bug');

        $xml = $tracker->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertEquals('T58', (string) $xml['id']);
    }

    public function testItHasCannedResponsesNode(): void
    {
        $tracker = new XMLTracker('some_xml_id', 'bug');
        $xml     = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $node = $tracker->export($xml);

        assertTrue(isset($node->cannedResponses));
    }

    public function testItHasFormElementsNode(): void
    {
        $tracker = new XMLTracker('some_xml_id', 'bug');
        $xml     = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $node = $tracker->export($xml);

        assertTrue(isset($node->formElements));
    }

    public function testItHasOneFormElement(): void
    {
        $tracker = (new XMLTracker('some_xml_id', 'bug'))
            ->withFormElement(
                new XMLFieldset('some_fieldset', 'details')
            );

        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');
        $node = $tracker->export($xml);

        assertCount(1, $node->formElements->formElement);
        assertEquals('some_fieldset', (string) $node->formElements->formElement[0]['ID']);
    }

    public function testItHasOneFormElementWithIDGenerator(): void
    {
        $id_generator = new class implements IDGenerator
        {
            public function getNextId(): int
            {
                return 58;
            }
        };

        $tracker = (new XMLTracker('some_xml_id', 'bug'))
            ->withFormElement(
                new XMLFieldset($id_generator, 'details')
            );

        $node = $tracker->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertCount(1, $node->formElements->formElement);
        assertEquals('F58', (string) $node->formElements->formElement[0]['ID']);
    }

    public function testWithFormElementsDoNotChangeOriginalObject(): void
    {
        $tracker_1 = new XMLTracker('some_xml_id', 'bug');
        $tracker_2 = $tracker_1->withFormElement(
            new XMLFieldset('some_fieldset', 'details')
        );

        $node_from_tracker_1_export = $tracker_1->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));
        $node_from_tracker_2_export = $tracker_2->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertCount(0, $node_from_tracker_1_export->formElements->formElement);
        assertCount(1, $node_from_tracker_2_export->formElements->formElement);
    }

    public function testItHasTwoFormElements(): void
    {
        $tracker = (new XMLTracker('some_xml_id', 'bug'))
            ->withFormElement(
                new XMLFieldset('fieldset1', 'details'),
                new XMLFieldset('fieldset2', 'summary'),
            );

        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');
        $node = $tracker->export($xml);

        assertCount(2, $node->formElements->formElement);
        assertEquals('fieldset1', (string) $node->formElements->formElement[0]['ID']);
        assertEquals('fieldset2', (string) $node->formElements->formElement[1]['ID']);
    }

    public function testItHasTwoFormElementsWithTwoCallsToWithFormElement(): void
    {
        $tracker = (new XMLTracker('some_xml_id', 'bug'))
            ->withFormElement(new XMLFieldset('fieldset1', 'details'))
            ->withFormElement(new XMLFieldset('fieldset2', 'summary'));

        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');
        $node = $tracker->export($xml);

        assertCount(2, $node->formElements->formElement);
        assertEquals('fieldset1', (string) $node->formElements->formElement[0]['ID']);
        assertEquals('fieldset2', (string) $node->formElements->formElement[1]['ID']);
    }

    public function testAFieldAlwaysHasALabel(): void
    {
        $xml = (new XMLTracker('some_xml_id', 'bug'))
            ->withFormElement(
                (new XMLStringField('some_id', 'name'))
            )
            ->export(new \SimpleXMLElement('<tracker />'));

        assertNotEmpty((string) $xml->formElements->formElement[0]->label);
    }

    public function testItHasAFieldAtTheRootOfTreeWithPermissions(): void
    {
        $tracker = (new XMLTracker('some_xml_id', 'bug'))
            ->withFormElement(
                (new XMLStringField('some_id', 'name'))
                    ->withPermissions(
                        new ReadPermission('UGROUP_ANONYMOUS'),
                        new SubmitPermission('UGROUP_REGISTERED'),
                        new UpdatePermission('UGROUP_PROJECT_MEMBERS'),
                    )
            );

        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');
        $node = $tracker->export($xml);

        assertCount(3, $node->permissions->permission);
        assertEquals('some_id', $node->permissions->permission[0]['REF']);
        assertEquals('field', $node->permissions->permission[0]['scope']);
        assertEquals('UGROUP_ANONYMOUS', $node->permissions->permission[0]['ugroup']);
        assertEquals('PLUGIN_TRACKER_FIELD_READ', $node->permissions->permission[0]['type']);

        assertEquals('some_id', $node->permissions->permission[1]['REF']);
        assertEquals('field', $node->permissions->permission[1]['scope']);
        assertEquals('UGROUP_REGISTERED', $node->permissions->permission[1]['ugroup']);
        assertEquals('PLUGIN_TRACKER_FIELD_SUBMIT', $node->permissions->permission[1]['type']);

        assertEquals('some_id', $node->permissions->permission[2]['REF']);
        assertEquals('field', $node->permissions->permission[2]['scope']);
        assertEquals('UGROUP_PROJECT_MEMBERS', $node->permissions->permission[2]['ugroup']);
        assertEquals('PLUGIN_TRACKER_FIELD_UPDATE', $node->permissions->permission[2]['type']);
    }

    public function testItHasAFieldInsideAFieldSetWithPermissions(): void
    {
        $tracker = (new XMLTracker('some_xml_id', 'bug'))
            ->withFormElement(
                (new XMLFieldset('fieldset_id', 'details'))
                    ->withFormElements(
                        (new XMLStringField('some_id', 'name'))
                        ->withPermissions(
                            new ReadPermission('UGROUP_ANONYMOUS'),
                            new SubmitPermission('UGROUP_REGISTERED'),
                            new UpdatePermission('UGROUP_PROJECT_MEMBERS'),
                        )
                    )
            );

        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');
        $node = $tracker->export($xml);

        assertCount(3, $node->permissions->permission);
        assertEquals('some_id', $node->permissions->permission[0]['REF']);
        assertEquals('field', $node->permissions->permission[0]['scope']);
        assertEquals('UGROUP_ANONYMOUS', $node->permissions->permission[0]['ugroup']);
        assertEquals('PLUGIN_TRACKER_FIELD_READ', $node->permissions->permission[0]['type']);

        assertEquals('some_id', $node->permissions->permission[1]['REF']);
        assertEquals('field', $node->permissions->permission[1]['scope']);
        assertEquals('UGROUP_REGISTERED', $node->permissions->permission[1]['ugroup']);
        assertEquals('PLUGIN_TRACKER_FIELD_SUBMIT', $node->permissions->permission[1]['type']);

        assertEquals('some_id', $node->permissions->permission[2]['REF']);
        assertEquals('field', $node->permissions->permission[2]['scope']);
        assertEquals('UGROUP_PROJECT_MEMBERS', $node->permissions->permission[2]['ugroup']);
        assertEquals('PLUGIN_TRACKER_FIELD_UPDATE', $node->permissions->permission[2]['type']);
    }

    public function testItHasAReportThatReferenceAFieldIndexedByName(): void
    {
        $tracker = (new XMLTracker('id', 'bug'))
            ->withFormElement(new XMLStringField('some_id', 'name'))
            ->withReports(
                (new XMLReport('Default'))
                    ->withCriteria(
                        new XMLReportCriterion(
                            new XMLReferenceByName('name')
                        )
                    )
            );

        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');
        $node = $tracker->export($xml);

        assertEquals('some_id', (string) $node->reports->report[0]->criterias->criteria[0]->field['REF']);
    }

    public function testItHasAReportThatReferenceAFieldIndexedByNameInsideContainer(): void
    {
        $tracker = (new XMLTracker('id', 'bug'))
            ->withFormElement(
                (new XMLFieldset('fieldset', 'details'))
                ->withFormElements(
                    new XMLStringField('some_id', 'name')
                )
            )
            ->withReports(
                (new XMLReport('Default'))
                    ->withCriteria(
                        new XMLReportCriterion(
                            new XMLReferenceByName('name')
                        )
                    )
            );

        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');
        $node = $tracker->export($xml);

        assertEquals('some_id', (string) $node->reports->report[0]->criterias->criteria[0]->field['REF']);
    }


    public function testItHasAReportWithTableRendererThatReferenceAFieldByName(): void
    {
        $tracker = (new XMLTracker('id', 'bug'))
            ->withFormElement(
                (new XMLFieldset('fieldset', 'details'))
                    ->withFormElements(
                        new XMLStringField('some_id', 'name')
                    )
            )
            ->withReports(
                (new XMLReport('Default'))
                    ->withRenderers(
                        (new XMLTable('table'))
                        ->withColumns(
                            new XMLTableColumn(
                                new XMLReferenceByName('name')
                            )
                        )
                    )
            );

        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');
        $node = $tracker->export($xml);

        assertEquals('table', (string) $node->reports->report[0]->renderers->renderer[0]['type']);
        assertEquals('some_id', (string) $node->reports->report[0]->renderers->renderer[0]->columns->field[0]['REF']);
    }

    public function testItHasAReportWithTableRendererThatReferenceAFieldByID(): void
    {
        $tracker = (new XMLTracker('id', 'bug'))
            ->withFormElement(
                (new XMLFieldset('fieldset', 'details'))
                    ->withFormElements(
                        new XMLStringField('some_id', 'name')
                    )
            )
            ->withReports(
                (new XMLReport('Default'))
                    ->withRenderers(
                        (new XMLTable('table'))
                            ->withColumns(
                                new XMLTableColumn(
                                    new XMLReferenceByID('some_id')
                                )
                            )
                    )
            );

        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');
        $node = $tracker->export($xml);

        assertEquals('table', (string) $node->reports->report[0]->renderers->renderer[0]['type']);
        assertEquals('some_id', (string) $node->reports->report[0]->renderers->renderer[0]->columns->field[0]['REF']);
    }

    public function testItHasAStringFieldWithTitleSemantic(): void
    {
        $tracker = (new XMLTracker('id', 'bug'))
            ->withFormElement(
                new XMLStringField('some_id', 'name')
            )
            ->withSemantics(
                new XMLTitleSemantic(
                    new XMLReferenceByName('name')
                )
            );

        $node = $tracker->export(new \SimpleXMLElement('<tracker />'));

        assertCount(1, $node->semantics->semantic);
        assertEquals('title', $node->semantics->semantic[0]['type']);
        assertEquals('title', $node->semantics->semantic[0]->shortname);
        assertNotEmpty($node->semantics->semantic[0]->label);
        assertNotEmpty($node->semantics->semantic[0]->description);
        assertEquals($node->formElements->formElement[0]['ID'], $node->semantics->semantic[0]->field['REF']);
    }

    public function testItHasTwoDateFieldsWithTimeframeSemantic(): void
    {
        $tracker = (new XMLTracker('id', 'bug'))
            ->withFormElement(
                new XMLDateField('F1', 'start_date'),
                new XMLDateField('F2', 'end_date'),
            )
            ->withSemantics(
                new XMLTimeframeSemantic(
                    new XMLReferenceByName('start_date'),
                    new XMLReferenceByName('end_date'),
                )
            );


        $node = $tracker->export(new \SimpleXMLElement('<trackers />'));

        $start_date_field = $node->xpath('/trackers/tracker/formElements/formElement[name="start_date"]');
        $end_date_field   = $node->xpath('/trackers/tracker/formElements/formElement[name="end_date"]');

        assertCount(1, $node->semantics->semantic);
        assertEquals('timeframe', $node->semantics->semantic[0]['type']);
        assertEquals($start_date_field[0]['ID'], $node->semantics->semantic[0]->start_date_field['REF']);
        assertEquals($end_date_field[0]['ID'], $node->semantics->semantic[0]->end_date_field['REF']);
    }

    public function testItHasOneArtifactWithAStringFieldValue(): void
    {
        $submitted_on = new \DateTimeImmutable();
        $tracker      = (new XMLTracker('tracker_id', 'bug'))
            ->withFormElement(
                new XMLStringField('some_id', 'name')
            )
            ->withArtifact(
                (new XMLArtifact(123))
                ->withChangeset(
                    (new XMLChangeset(
                        XMLUser::buildUsername('jane'),
                        $submitted_on
                    ))
                    ->withFieldChange(
                        new XMLStringValue(
                            'name',
                            'Sprint 1'
                        )
                    )
                )
            );

        $node = $tracker->export(new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));

        assertCount(1, $node->artifacts->artifact);
        assertEquals('123', $node->artifacts->artifact[0]['id']);
        assertCount(1, $node->artifacts->artifact[0]->changeset);
        assertEquals('username', $node->artifacts->artifact[0]->changeset[0]->submitted_by['format']);
        assertEquals('jane', $node->artifacts->artifact[0]->changeset[0]->submitted_by);
        assertEquals('ISO8601', $node->artifacts->artifact[0]->changeset[0]->submitted_on['format']);
        assertEquals($submitted_on->format('c'), $node->artifacts->artifact[0]->changeset[0]->submitted_on);
        assertNotNull($node->artifacts->artifact[0]->changeset[0]->comments);
        assertCount(0, $node->artifacts->artifact[0]->changeset[0]->comments->comment);
        assertCount(1, $node->artifacts->artifact[0]->changeset[0]->field_change);
        assertEquals('name', $node->artifacts->artifact[0]->changeset[0]->field_change[0]['field_name']);
        assertEquals('string', $node->artifacts->artifact[0]->changeset[0]->field_change[0]['type']);
        assertEquals('Sprint 1', $node->artifacts->artifact[0]->changeset[0]->field_change[0]->value);
    }
}
