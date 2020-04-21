<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Updater_FieldChange_FieldChangeIntegerXMLUpdaterTest extends \PHPUnit\Framework\TestCase
{
    /** @var Tracker_XML_Updater_FieldChange_FieldChangeIntegerXMLUpdater */
    private $updater;

    /** @var SimpleXMLElement */
    private $field_change_xml;

    protected function setUp(): void
    {
        $this->updater          = new Tracker_XML_Updater_FieldChange_FieldChangeIntegerXMLUpdater();
        $this->field_change_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<field_change field_name="story_points">'
            . '  <value>123</value>'
            . '</field_change>');
    }

    public function testItUpdatesTheNodeValueWithSubmittedValue(): void
    {
        $this->updater->update(
            $this->field_change_xml,
            '21'
        );

        $this->assertEquals(21, (int) $this->field_change_xml->value);
    }
}
