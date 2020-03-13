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
require_once __DIR__ . '/../../bootstrap.php';

class Workflow_Transition_ConditionFactory_BaseTest extends TuleapTestCase
{

    /** @var Workflow_Transition_ConditionFactory */
    protected $condition_factory;

    /** @var Workflow_Transition_Condition_CommentNotEmpty_Factory */
    protected $commentnotempty_factory;

    /** @var Workflow_Transition_Condition_FieldNotEmpty_Factory */
    protected $fieldnotempty_factory;

    /** @var Workflow_Transition_Condition_Permissions_Factory */
    protected $permissions_factory;

    public function setUp()
    {
        parent::setUp();
        $this->permissions_factory     = mock('Workflow_Transition_Condition_Permissions_Factory');
        $this->fieldnotempty_factory   = mock('Workflow_Transition_Condition_FieldNotEmpty_Factory');
        $this->commentnotempty_factory = mock('Workflow_Transition_Condition_CommentNotEmpty_Factory');

        $this->condition_factory = new Workflow_Transition_ConditionFactory(
            $this->permissions_factory,
            $this->fieldnotempty_factory,
            $this->commentnotempty_factory
        );

        $this->project = mock('Project');
    }
}

class Workflow_Transition_ConditionFactory_isFieldUsedInConditionsTest extends Workflow_Transition_ConditionFactory_BaseTest
{

    public function itDelegatesToFieldNotEmptyFactory()
    {
        $field = mock('Tracker_FormElement_Field_Date');
        stub($this->fieldnotempty_factory)->isFieldUsedInConditions($field)->once()->returns(true);
        $this->assertTrue($this->condition_factory->isFieldUsedInConditions($field));
    }
}

class Workflow_Transition_ConditionFactory_getAllInstancesFromXML_Test extends Workflow_Transition_ConditionFactory_BaseTest
{

    private $xml_mapping = array();

    /** @var Transition */
    private $transition;

    public function setUp()
    {
        parent::setUp();
        PermissionsManager::setInstance(mock('PermissionsManager'));

        $this->transition = mock('Transition');

        $this->legacy_permissions_xml = new SimpleXMLElement('
            <transition>
                <permissions>
                    <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                    <permission ugroup="UGROUP_PROJECT_ADMIN"/>
                </permissions>
            </transition>
        ');
        stub($this->permissions_factory)
            ->getInstanceFromXML($this->legacy_permissions_xml->permissions, $this->xml_mapping, $this->transition, $this->project)
            ->returns(mock('Workflow_Transition_Condition_Permissions'));

        $this->from_5_7_permissions_xml = new SimpleXMLElement('
            <transition>
                <conditions>
                    <condition type="perms">
                        <permissions>
                            <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                            <permission ugroup="UGROUP_PROJECT_ADMIN"/>
                        </permissions>
                    </condition>
                </conditions>
            </transition>
        ');
        stub($this->permissions_factory)
            ->getInstanceFromXML($this->from_5_7_permissions_xml->conditions->condition, $this->xml_mapping, $this->transition, $this->project)
            ->returns(mock('Workflow_Transition_Condition_Permissions'));
    }

    public function tearDown()
    {
        PermissionsManager::clearInstance();
        parent::tearDown();
    }

    public function itReconstitutesLegacyPermissions()
    {
        $conditions = $this->condition_factory->getAllInstancesFromXML(
            $this->legacy_permissions_xml,
            $this->xml_mapping,
            $this->transition,
            $this->project
        );

        $this->assertIsA($conditions[0], 'Workflow_Transition_Condition_Permissions');
    }

    public function itReconstitutesPermissions()
    {
        $conditions = $this->condition_factory->getAllInstancesFromXML(
            $this->from_5_7_permissions_xml,
            $this->xml_mapping,
            $this->transition,
            $this->project
        );

        $this->assertIsA($conditions[0], 'Workflow_Transition_Condition_Permissions');
    }

    public function itReconstituesFieldNotEmpty()
    {
        $xml = new SimpleXMLElement('
            <transition>
                <conditions>
                    <condition type="notempty">
                        <field REF="F14"/>
                    </condition>
                </conditions>
            </transition>
        ');

        $condition = mock('Workflow_Transition_Condition_FieldNotEmpty');
        stub($this->fieldnotempty_factory)->getInstanceFromXML($xml->conditions->condition, '*', '*')->returns($condition);

        $conditions = $this->condition_factory->getAllInstancesFromXML(
            $xml,
            $this->xml_mapping,
            $this->transition,
            $this->project
        );

        $this->assertEqual($conditions[0], $condition);
    }

    public function itIgnoresNullConditions()
    {
        $xml = new SimpleXMLElement('
            <transition>
                <conditions>
                    <condition type="notempty" />
                </conditions>
            </transition>
        ');

        stub($this->fieldnotempty_factory)->getInstanceFromXML()->returns(null);

        $conditions = $this->condition_factory->getAllInstancesFromXML(
            $xml,
            $this->xml_mapping,
            $this->transition,
            $this->project
        );

        $this->assertEqual($conditions, new Workflow_Transition_ConditionsCollection());
    }

    public function itDelegatesTheDuplicateToSubFactories()
    {
        $new_transition_id = 2;
        $field_mapping     = array('some fields mapping');
        $ugroup_mapping    = array('some ugroups mapping');
        $duplicate_type    = PermissionsDao::DUPLICATE_NEW_PROJECT;

        expect($this->permissions_factory)->duplicate($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type)->once();
        expect($this->fieldnotempty_factory)->duplicate($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type)->once();
        $this->condition_factory->duplicate($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type);
    }
}
