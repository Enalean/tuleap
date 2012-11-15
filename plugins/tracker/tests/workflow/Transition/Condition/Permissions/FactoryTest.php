<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Workflow_Transition_Condition_Permissions_FactoryTest extends TuleapTestCase {

    private $xml_mapping = array();

    /** @var Workflow_Transition_Condition_Permissions_Factory */
    private $permissions_factory;

    /** @var Transition */
    private $transition;

    public function setUp() {
        parent::setUp();
        PermissionsManager::setInstance(mock('PermissionsManager'));

        $this->transition          = mock('Transition');
        $this->permissions_factory = new Workflow_Transition_Condition_Permissions_Factory();
    }

    public function tearDown() {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function itReconstitutesLegacyPermissions() {
        $xml = new SimpleXMLElement('
            <permissions>
                <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                <permission ugroup="UGROUP_PROJECT_ADMIN"/>
            </permissions>
        ');

        $condition = $this->permissions_factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition);

        $this->assertIsA($condition, 'Workflow_Transition_Condition_Permissions');
    }

    public function itReconstitutesPermissions() {
        $xml = new SimpleXMLElement('
            <condition type="perms">
                <permissions>
                    <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                    <permission ugroup="UGROUP_PROJECT_ADMIN"/>
                </permissions>
            </condition>
        ');

        $condition = $this->permissions_factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition);

        $this->assertIsA($condition, 'Workflow_Transition_Condition_Permissions');
    }
}
?>
