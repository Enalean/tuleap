<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Transition_PostAction_CIBuildFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Transition $transition;
    private int $post_action_id;
    private int $transition_id;
    private Transition_PostAction_CIBuildFactory $factory;
    private Transition_PostAction_CIBuildDao&MockObject $dao;
    private Workflow&MockObject $workflow;

    #[\Override]
    protected function setUp(): void
    {
        $this->transition_id  = 123;
        $this->post_action_id = 789;

        $workflow_id    = '1112';
        $this->workflow = $this->createMock(Workflow::class);
        $this->workflow->method('getId')->willReturn($workflow_id);

        $this->transition = new Transition(
            $this->transition_id,
            $workflow_id,
            null,
            ListStaticValueBuilder::aStaticValue('field')->build()
        );
        $this->transition->setWorkflow($this->workflow);

        $this->dao     = $this->createMock(Transition_PostAction_CIBuildDao::class);
        $this->factory = new Transition_PostAction_CIBuildFactory($this->dao);
    }

    public function testItLoadsCIBuildPostActions(): void
    {
        $post_action_value = 'http://ww.myjenks.com/job';
        $post_action_rows  = [
            'id'            => $this->post_action_id,
            'job_url'       => $post_action_value,
            'transition_id' => (string) $this->transition_id,
        ];

        $this->dao->method('searchByTransitionId')->with($this->transition_id)
            ->willReturn(\TestHelper::arrayToDar($post_action_rows));

        $this->assertCount(1, $this->factory->loadPostActions($this->transition));

        $post_action_array = $this->factory->loadPostActions($this->transition);
        $first_pa          = $post_action_array[0];

        $this->assertEquals($post_action_value, $first_pa->getJobUrl());
        $this->assertEquals($this->post_action_id, $first_pa->getId());
        $this->assertEquals($this->transition, $first_pa->getTransition());
    }

    public function testItLoadsCIBuildPostActionsWithCache(): void
    {
        $post_action_value = 'http://ww.myjenks.com/job';

        $this->dao->method('searchByWorkflow')->with($this->workflow)
            ->willReturn(\TestHelper::arrayToDar(
                [
                    'id'         => (string) $this->post_action_id,
                    'job_url'    => $post_action_value,
                    'transition_id' => (string) $this->transition_id,
                ],
                [
                    'id'         => '132',
                    'job_url'    => 'https://example.com/jenkins/job',
                    'transition_id' => '999',
                ],
            ));

        $this->factory->warmUpCacheForWorkflow($this->workflow);
        $post_action_array = $this->factory->loadPostActions($this->transition);
        $this->assertCount(1, $post_action_array);

        $first_pa = $post_action_array[0];

        $this->assertEquals($post_action_value, $first_pa->getJobUrl());
        $this->assertEquals($this->post_action_id, $first_pa->getId());
        $this->assertEquals($this->transition, $first_pa->getTransition());
    }

    public function testItDelegatesTheDuplicationToTheDao(): void
    {
        $to_transition_id = 2;
        $field_mapping    = [];

        $this->dao->expects($this->once())->method('duplicate')->with($this->transition_id, $to_transition_id);
        $this->factory->duplicate($this->transition, $to_transition_id, $field_mapping);
    }

    public function testItReconstitutesCIBuildPostActionsFromXML(): void
    {
        $xml         = new SimpleXMLElement('
            <postaction_ci_build job_url="http://www"/>
        ');
        $mapping     = ['F1' => 62334];
        $post_action = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);

        $this->assertInstanceOf(Transition_PostAction_CIBuild::class, $post_action);
        $this->assertEquals('http://www', $post_action->getJobUrl());
        $this->assertTrue($post_action->isDefined());
    }

    public function testItReturnsAlwaysFalseSinceThereIsNoFieldUsedInThisPostAction(): void
    {
        $this->assertFalse($this->factory->isFieldUsedInPostActions($this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class)));
    }
}
