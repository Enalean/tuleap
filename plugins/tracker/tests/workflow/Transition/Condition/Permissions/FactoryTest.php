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
require_once __DIR__ . '/../../../../bootstrap.php';
class Workflow_Transition_Condition_Permissions_FactoryTest extends TuleapTestCase
{

    private $xml_mapping = array();

    /** @var Workflow_Transition_Condition_Permissions_Factory */
    private $permissions_factory;

    /** @var Transition */
    private $transition;

    public function setUp()
    {
        parent::setUp();
        $this->permissions_manager = mock('PermissionsManager');
        PermissionsManager::setInstance($this->permissions_manager);

        $this->ugroup_manager = mock('UgroupManager');
        $this->project        = mock('Project');

        $this->transition          = stub('Transition')->getId()->returns(123);
        $this->permissions_factory = new Workflow_Transition_Condition_Permissions_Factory($this->ugroup_manager);
    }

    public function tearDown()
    {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function itReconstitutesLegacyPermissions()
    {
        $xml = new SimpleXMLElement('
            <permissions>
                <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                <permission ugroup="UGROUP_PROJECT_ADMIN"/>
            </permissions>');

        $condition = $this->permissions_factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition, $this->project);

        $this->assertIsA($condition, 'Workflow_Transition_Condition_Permissions');
    }

    public function _itReconstitutesPermissions()
    {
        $xml = new SimpleXMLElement('
            <condition type="perms">
                <permissions>
                    <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                    <permission ugroup="UGROUP_PROJECT_ADMIN"/>
                </permissions>
            </condition>
        ');

        $condition = $this->permissions_factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition, $this->project);

        $this->assertIsA($condition, 'Workflow_Transition_Condition_Permissions');
    }

    public function itDelegatesDuplicateToPermissionsManager()
    {
        $new_transition_id = 2;
        $field_mapping     = array('some fields mapping');
        $ugroup_mapping    = array('some ugroups mapping');
        $duplicate_type    = PermissionsDao::DUPLICATE_NEW_PROJECT;

        expect($this->permissions_manager)
            ->duplicatePermissions(
                $this->transition->getId(),
                $new_transition_id,
                array(Workflow_Transition_Condition_Permissions::PERMISSION_TRANSITION),
                $ugroup_mapping,
                $duplicate_type
            )->once();
        $this->permissions_factory->duplicate($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type);
    }
}
