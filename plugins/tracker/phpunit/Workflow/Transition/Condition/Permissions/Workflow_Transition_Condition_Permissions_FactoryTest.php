<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
final class Workflow_Transition_Condition_Permissions_FactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $xml_mapping = array();

    /** @var Workflow_Transition_Condition_Permissions_Factory */
    private $permissions_factory;

    /** @var Transition */
    private $transition;

    private $permissions_manager;
    private $ugroup_manager;
    private $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissions_manager = \Mockery::spy(\PermissionsManager::class);
        PermissionsManager::setInstance($this->permissions_manager);

        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);
        $this->project        = \Mockery::spy(\Project::class);

        $this->transition          = \Mockery::spy(\Transition::class)->shouldReceive('getId')->andReturns(123)->getMock();
        $this->permissions_factory = new Workflow_Transition_Condition_Permissions_Factory($this->ugroup_manager);
    }

    protected function tearDown(): void
    {
        PermissionsManager::clearInstance();
    }

    public function testItReconstitutesLegacyPermissions(): void
    {
        $xml = new SimpleXMLElement('
            <permissions>
                <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                <permission ugroup="UGROUP_PROJECT_ADMIN"/>
            </permissions>');

        $condition = $this->permissions_factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition, $this->project);

        $this->assertInstanceOf(\Workflow_Transition_Condition_Permissions::class, $condition);
    }

    public function testItReconstitutesPermissions(): void
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

        $this->assertInstanceOf(\Workflow_Transition_Condition_Permissions::class, $condition);
    }

    public function testItDelegatesDuplicateToPermissionsManager(): void
    {
        $new_transition_id = 2;
        $field_mapping     = array('some fields mapping');
        $ugroup_mapping    = array('some ugroups mapping');
        $duplicate_type    = PermissionsDao::DUPLICATE_NEW_PROJECT;

        $this->permissions_manager->shouldReceive('duplicatePermissions')->with($this->transition->getId(), $new_transition_id, array(Workflow_Transition_Condition_Permissions::PERMISSION_TRANSITION), $ugroup_mapping, $duplicate_type)->once();
        $this->permissions_factory->duplicate($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type);
    }
}
