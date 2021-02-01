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
use Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset;
use Tuleap\Tracker\TrackerColor;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

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
}
