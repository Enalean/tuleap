<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once __DIR__ . '/../../../bootstrap.php';

class Transition_PostAction_CIBuildFactory_BaseTest extends TuleapTestCase
{

    protected $factory;
    protected $dao;

    public function setUp()
    {
        parent::setUp();

        $this->transition_id  = 123;
        $this->post_action_id = 789;

        $this->transition = new Transition(
            $this->transition_id,
            0,
            null,
            null
        );
        $this->dao        = Mockery::mock('Transition_PostAction_CIBuildDao');
        $this->factory    = new Transition_PostAction_CIBuildFactory($this->dao);
    }
}

class Transition_PostAction_CIBuildFactory_LoadPostActionsTest extends Transition_PostAction_CIBuildFactory_BaseTest
{

    public function itLoadsCIBuildPostActions()
    {
        $post_action_value = 'http://ww.myjenks.com/job';
        $post_action_rows  = array(
            'id'         => $this->post_action_id,
            'job_url'    => $post_action_value,
        );

        stub($this->dao)->searchByTransitionId($this->transition_id)->returnsDar($post_action_rows);

        $this->assertCount($this->factory->loadPostActions($this->transition), 1);

        $post_action_array = $this->factory->loadPostActions($this->transition);
        $first_pa = $post_action_array[0];

        $this->assertEqual($first_pa->getJobUrl(), $post_action_value);
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
    }
}

class Transition_PostAction_CIBuildFactory_DuplicateTest extends Transition_PostAction_CIBuildFactory_BaseTest
{

    public function itDelegatesTheDuplicationToTheDao()
    {
        $to_transition_id   = 2;
        $field_mapping      = array();

        expect($this->dao)->duplicate($this->transition_id, $to_transition_id)->once();
        $this->factory->duplicate($this->transition, $to_transition_id, $field_mapping);
    }
}

class Transition_CIBuildFactory_GetInstanceFromXmlTest extends Transition_PostAction_CIBuildFactory_BaseTest
{

    public function itReconstitutesCIBuildPostActionsFromXML()
    {
        $xml = new SimpleXMLElement('
            <postaction_ci_build job_url="http://www"/>
        ');
        $mapping     = array('F1' => 62334);
        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);

        $this->assertIsA($post_action, 'Transition_PostAction_CIBuild');
        $this->assertEqual($post_action->getJobUrl(), "http://www");
        $this->assertTrue($post_action->isDefined());
    }
}

class Transition_CIBuildFactory_isFieldUsedInPostActionsTest extends Transition_PostAction_CIBuildFactory_BaseTest
{

    public function itReturnsAlwaysFalseSinceThereIsNoFieldUsedInThisPostAction()
    {
        $this->assertFalse($this->factory->isFieldUsedInPostActions(mock('Tracker_FormElement_Field_Selectbox')));
    }
}
