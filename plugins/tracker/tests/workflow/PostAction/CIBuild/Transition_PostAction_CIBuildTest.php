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

require_once(dirname(__FILE__).'/../../../../include/Tracker/TrackerManager.class.php');
require_once(dirname(__FILE__).'/../../../../include/workflow/PostAction/CIBuild/Transition_PostAction_CIBuild.class.php');

class Transition_PostAction_CIBuildTest extends TuleapTestCase {

    public function itCallsDeleteMethodInDaoWhenDeleteIsRequested() {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://www.example.com';
        $client           = mock('Jenkins_Client');
        $condendi_request = aRequest()->with('remove_postaction', array($id => 1))->build();

        $ci_build_dao = mock('Transition_PostAction_CIBuildDao');

        $post_action_ci_build = partial_mock('Transition_PostAction_CIBuild', array('getDao'), array($transition, $id, $job_url, $client));
        stub($post_action_ci_build)->getDao()->returns($ci_build_dao);

        expect($ci_build_dao)->deletePostAction($id)->once();
        $post_action_ci_build->process($condendi_request);
    }

    public function itDoesNotUpdateThePostActionIfJobURLIsNotValid() {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://www.example.com';
        $new_job_url      = 'not_an_url';
        $client           = mock('Jenkins_Client');
        $condendi_request = aRequest()
            ->with('remove_postaction', array())
            ->with('workflow_postaction_launch_job', array($id => $new_job_url))
            ->build();

        $ci_build_dao = mock('Transition_PostAction_CIBuildDao');

        $post_action_ci_build = partial_mock('Transition_PostAction_CIBuild', array('getDao'), array($transition, $id, $job_url, $client));
        stub($post_action_ci_build)->getDao()->returns($ci_build_dao);

        expect($ci_build_dao)->updatePostAction()->never();
        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        $post_action_ci_build->process($condendi_request);
    }

    public function itDoesNotUpdateThePostActionIfJobURLIsNotChanged() {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://www.example.com';
        $client           = mock('Jenkins_Client');
        $condendi_request = aRequest()
            ->with('remove_postaction', array())
            ->with('workflow_postaction_launch_job', array($id => $job_url))
            ->build();

        $ci_build_dao = mock('Transition_PostAction_CIBuildDao');

        $post_action_ci_build = partial_mock('Transition_PostAction_CIBuild', array('getDao'), array($transition, $id, $job_url, $client));
        stub($post_action_ci_build)->getDao()->returns($ci_build_dao);

        expect($ci_build_dao)->updatePostAction()->never();
        expect($GLOBALS['Response'])->addFeedback('error', '*')->never();
        $post_action_ci_build->process($condendi_request);
    }

    public function itIsNotDefinedWhenJobUrlIsEmpty() {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = null;
        $client           = mock('Jenkins_Client');

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);
        $this->assertFalse($post_action_ci_build->isDefined());
    }

    public function itIsDefinedWhenJobUrlIsFilled() {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://example.com/job';
        $client           = mock('Jenkins_Client');

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);
        $this->assertTrue($post_action_ci_build->isDefined());
    }

    public function itExportsInXMLFormatTheJobUrl() {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://example.com';
        $client           = mock('Jenkins_Client');

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker xmlns="http://codendi.org/tracker" />');
        $array_xml_mapping = array();

        $post_action_ci_build->exportToXml($root, $array_xml_mapping);
        $this->assertEqual((string)$root->postaction_ci_build['job_url'], $job_url);
    }

    public function itDoesNotExportThePostActionIfJobUrlIsNotSet() {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = '';
        $client           = mock('Jenkins_Client');

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker xmlns="http://codendi.org/tracker" />');
        $array_xml_mapping = array();

        $post_action_ci_build->exportToXml($root, $array_xml_mapping);
        $this->assertFalse(isset($root->postaction_ci_build));
    }

    public function itLaunchTheCIBuildOnAfter() {

        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://example.com/job';
        $client           = mock('Jenkins_Client');

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);

        expect($client)->launchJobBuild($job_url)->once();
        $post_action_ci_build->after();
    }
}
?>
