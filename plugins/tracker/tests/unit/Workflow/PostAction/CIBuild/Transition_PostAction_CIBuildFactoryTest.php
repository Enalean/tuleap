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

final class Transition_PostAction_CIBuildFactoryTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Transition
     */
    private $transition;

    /**
     * @var int
     */
    private $post_action_id;

    /**
     * @var int
     */
    private $transition_id;

    /**
     * @var Transition_PostAction_CIBuildFactory
     */
    private $factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Transition_PostAction_CIBuildDao
     */
    private $dao;
    /**
     * @var Workflow
     */
    private $workflow;

    protected function setUp(): void
    {
        $this->transition_id  = 123;
        $this->post_action_id = 789;

        $workflow_id = '1112';
        $this->workflow = Mockery::mock(Workflow::class, ['getId' => $workflow_id]);

        $this->transition = new Transition(
            $this->transition_id,
            $workflow_id,
            null,
            null
        );
        $this->transition->setWorkflow($this->workflow);

        $this->dao        = Mockery::mock(Transition_PostAction_CIBuildDao::class);
        $this->factory    = new Transition_PostAction_CIBuildFactory($this->dao);
    }

    public function testItLoadsCIBuildPostActions(): void
    {
        $post_action_value = 'http://ww.myjenks.com/job';
        $post_action_rows  = [
            'id'            => $this->post_action_id,
            'job_url'       => $post_action_value,
            'transition_id' => (string) $this->transition_id,
        ];

        $this->dao->shouldReceive('searchByTransitionId')->with($this->transition_id)
            ->andReturns(\TestHelper::arrayToDar($post_action_rows));

        $this->assertCount(1, $this->factory->loadPostActions($this->transition));

        $post_action_array = $this->factory->loadPostActions($this->transition);
        $first_pa = $post_action_array[0];

        $this->assertEquals($post_action_value, $first_pa->getJobUrl());
        $this->assertEquals($this->post_action_id, $first_pa->getId());
        $this->assertEquals($this->transition, $first_pa->getTransition());
    }

    public function testItLoadsCIBuildPostActionsWithCache(): void
    {
        $post_action_value = 'http://ww.myjenks.com/job';

        $this->dao->shouldReceive('searchByWorkflow')->with($this->workflow)
            ->andReturns(\TestHelper::arrayToDar(
                [
                    'id'         => (string) $this->post_action_id,
                    'job_url'    => $post_action_value,
                    'transition_id' => (string) $this->transition_id,
                ],
                [
                    'id'         => "132",
                    'job_url'    => "https://example.com/jenkins/job",
                    'transition_id' => "999",
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
        $to_transition_id   = 2;
        $field_mapping      = [];

        $this->dao->shouldReceive('duplicate')->with($this->transition_id, $to_transition_id)->once();
        $this->factory->duplicate($this->transition, $to_transition_id, $field_mapping);
    }

    public function testItReconstitutesCIBuildPostActionsFromXML(): void
    {
        $xml = new SimpleXMLElement('
            <postaction_ci_build job_url="http://www"/>
        ');
        $mapping     = ['F1' => 62334];
        $post_action = $this->factory->getInstanceFromXML($xml, $mapping, $this->transition);

        $this->assertInstanceOf(Transition_PostAction_CIBuild::class, $post_action);
        $this->assertEquals("http://www", $post_action->getJobUrl());
        $this->assertTrue($post_action->isDefined());
    }

    public function testItReturnsAlwaysFalseSinceThereIsNoFieldUsedInThisPostAction(): void
    {
        $this->assertFalse($this->factory->isFieldUsedInPostActions(\Mockery::spy(\Tracker_FormElement_Field_Selectbox::class)));
    }
}
