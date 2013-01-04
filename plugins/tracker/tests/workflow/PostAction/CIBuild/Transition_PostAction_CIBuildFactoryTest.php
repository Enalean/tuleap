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

require_once dirname(__FILE__).'/../../../builders/aPostActionCIBuildFactory.php';
require_once dirname(__FILE__).'/../../../builders/aCIBuildPostAction.php';
require_once dirname(__FILE__).'/../../../../include/workflow/PostAction/CIBuild/Transition_PostAction_CIBuildFactory.class.php';

class Transition_PostAction_CIBuildFactoryTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->transition_id  = 123;
        $this->post_action_id = 789;

        $this->transition = aTransition()->withId($this->transition_id)->build();
    }

    public function itLoadsCIBuildPostActions() {
        $post_action_value = 12;
        $post_action_rows  = array(array('id'         => $this->post_action_id,
                                         'job_url'    => $post_action_value));

        $ci_build_dao = stub('Transition_PostAction_CIBuildDao')->searchByTransitionId($this->transition_id)->returns($post_action_rows);
        $ci_client    = new Jenkins_Client(new Http_Client());
        $factory = aPostActionCIBuildFactory()
            ->withCIBuildDao($ci_build_dao)
            ->withCIClient($ci_client)
            ->build();
        $expected = array(aCIBuildPostAction()
            ->withId($this->post_action_id)
            ->withTransition($this->transition)
            ->withValue($post_action_value)
            ->withCIClient($ci_client)
            ->build());

        $this->assertEqual($factory->loadPostActions($this->transition), $expected);
    }
}

class Transition_CIBuildFactory_GetInstanceFromXmlTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $ci_client    = new Jenkins_Client(new Http_Client());
        $this->factory    = aPostActionCIBuildFactory()
                ->withCIClient($ci_client)
                ->build();
        $this->mapping    = array('F1' => 62334);
        $this->transition = aTransition()->build();
    }

    public function itReconstitutesCIBuildPostActionsFromXML() {
        $xml = new SimpleXMLElement('
            <postaction_ci_build job_url="http://www"/>
        ');

        $post_action = $this->factory->getInstanceFromXML($xml, $this->mapping, $this->transition);

        $this->assertIsA($post_action, 'Transition_PostAction_CIBuild');
        $this->assertEqual($post_action->getJobUrl(),"http://www" );
        $this->assertTrue($post_action->isDefined());
    }
}
?>