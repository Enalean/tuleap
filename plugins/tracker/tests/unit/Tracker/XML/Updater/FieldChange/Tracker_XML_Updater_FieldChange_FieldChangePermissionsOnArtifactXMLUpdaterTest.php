<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
final class Tracker_XML_Updater_FieldChange_FieldChangePermissionsOnArtifactXMLUpdaterTest extends \PHPUnit\Framework\TestCase
{
    /** @var Tracker_XML_Updater_FieldChange_FieldChangePermissionsOnArtifactXMLUpdater */
    private $updater;

    /** @var SimpleXMLElement */
    private $field_change_xml;

    protected function setUp(): void
    {
        $this->updater          = new Tracker_XML_Updater_FieldChange_FieldChangePermissionsOnArtifactXMLUpdater();
        $this->field_change_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<field_change field_name="perms" use_perm="1">'
            . '  <ugroup ugroup_id="3"></ugroup>'
            . '  <ugroup ugroup_id="4"></ugroup>'
            . '</field_change>');
    }

    public function testItUpdatesTheUgroupNodesValueWithSubmittedValue(): void
    {
        $this->updater->update(
            $this->field_change_xml,
            [
                'use_artifact_permissions' => 1,
                'u_groups' => [
                    '1001',
                    '1002'
                ]
            ]
        );

        $this->assertEquals(1001, (int) $this->field_change_xml->ugroup[0]['ugroup_id']);
        $this->assertEquals(1002, (int) $this->field_change_xml->ugroup[1]['ugroup_id']);
    }

    public function testItUpdatesTheUsePerm(): void
    {
        $this->updater->update(
            $this->field_change_xml,
            [
                'use_artifact_permissions' => 0,
                'u_groups' => []
            ]
        );

        $this->assertEquals(0, (int) $this->field_change_xml['use_perm']);
        $this->assertCount(0, $this->field_change_xml->ugroup);
    }

    public function testItUpdatesTheUsePermEvenWhenUGroupsAreNotSubmitted(): void
    {
        $this->updater->update(
            $this->field_change_xml,
            [
                'use_artifact_permissions' => 0
            ]
        );

        $this->assertEquals(0, (int) $this->field_change_xml['use_perm']);
        $this->assertCount(0, $this->field_change_xml->ugroup);
    }
}
