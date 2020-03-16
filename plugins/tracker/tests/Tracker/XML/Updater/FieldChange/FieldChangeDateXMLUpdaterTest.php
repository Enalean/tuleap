<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
require_once __DIR__ . '/../../../../bootstrap.php';

class Tracker_XML_Updater_FieldChange_FieldChangeDateXMLUpdaterTest extends TuleapTestCase
{

    /** @var Tracker_XML_Updater_FieldChange_FieldChangeDateXMLUpdater */
    private $updater;

    /** @var SimpleXMLElement */
    private $field_change_xml;

    public function setUp()
    {
        parent::setUp();
        $this->updater          = new Tracker_XML_Updater_FieldChange_FieldChangeDateXMLUpdater();
        $this->field_change_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<field_change field_name="start_date">'
            . '  <value format="ISO8601">2012-01-01T00:00:00+01:00</value>'
            . '</field_change>');
    }

    public function itUpdatesTheNodeValueWithSubmittedValue()
    {
        $this->updater->update(
            $this->field_change_xml,
            '2014-04-28'
        );

        $this->assertEqual($this->field_change_xml->value, '2014-04-28T00:00:00+02:00');
    }
}
