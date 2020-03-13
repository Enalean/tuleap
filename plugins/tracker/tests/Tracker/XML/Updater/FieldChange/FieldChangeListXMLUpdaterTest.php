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

class Tracker_XML_Updater_FieldChange_FieldChangeListXMLUpdaterTest extends TuleapTestCase
{

    /** @var Tracker_XML_Updater_FieldChange_FieldChangeListXMLUpdater */
    private $updater;

    /** @var SimpleXMLElement */
    private $field_change_xml;

    public function setUp()
    {
        parent::setUp();
        $this->updater          = new Tracker_XML_Updater_FieldChange_FieldChangeListXMLUpdater();
        $this->field_change_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<field_change field_name="status" type="list" bind="static">'
            . '  <value format="id">101</value>'
            . '  <value format="id">102</value>'
            . '</field_change>');
    }

    public function itUpdatesTheValueNodesValueWithTwoNewSubmittedValue()
    {
        $this->updater->update(
            $this->field_change_xml,
            array(
                '2001',
                '2002'
            )
        );

        $this->assertEqual((int) $this->field_change_xml->value[0], 2001);
        $this->assertEqual((string) $this->field_change_xml->value[0]['format'], 'id');
        $this->assertEqual((int) $this->field_change_xml->value[1], 2002);
        $this->assertEqual((string) $this->field_change_xml->value[1]['format'], 'id');
    }

    public function itUpdatesTheValueNodesValueWithOneNewSubmittedValue()
    {
        $this->updater->update(
            $this->field_change_xml,
            array(
                '2001',
            )
        );

        $this->assertEqual((int) $this->field_change_xml->value[0], 2001);
        $this->assertEqual((string) $this->field_change_xml->value[0]['format'], 'id');
    }
}
