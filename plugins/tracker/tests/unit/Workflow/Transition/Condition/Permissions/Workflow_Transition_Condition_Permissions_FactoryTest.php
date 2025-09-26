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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\Duplication\DuplicationUserGroupMapping;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Workflow_Transition_Condition_Permissions_FactoryTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    private array $xml_mapping = [];

    private Workflow_Transition_Condition_Permissions_Factory $permissions_factory;

    private Transition $transition;

    private PermissionsManager&MockObject $permissions_manager;
    private UGroupManager&MockObject $ugroup_manager;
    private Project $project;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->permissions_manager = $this->createMock(PermissionsManager::class);
        PermissionsManager::setInstance($this->permissions_manager);

        $this->ugroup_manager = $this->createMock(UGroupManager::class);
        $this->project        = ProjectTestBuilder::aProject()->build();

        $this->transition          = new Transition(
            123,
            101,
            null,
            ListStaticValueBuilder::aStaticValue('Done')->build(),
        );
        $this->permissions_factory = new Workflow_Transition_Condition_Permissions_Factory($this->ugroup_manager);
    }

    #[\Override]
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

        $mapping = DuplicationUserGroupMapping::fromNewProjectWithMapping([103 => 122]);

        $this->permissions_manager->expects($this->once())
            ->method('duplicatePermissions')
            ->with($this->transition->getId(), $new_transition_id, [Workflow_Transition_Condition_Permissions::PERMISSION_TRANSITION], $mapping);
        $this->permissions_factory->duplicate($this->transition, $new_transition_id, $mapping);
    }
}
