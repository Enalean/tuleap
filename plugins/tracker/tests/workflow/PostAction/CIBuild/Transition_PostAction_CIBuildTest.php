<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../../bootstrap.php';

class Transition_PostAction_CIBuildTest extends TuleapTestCase
{
    public function itIsNotDefinedWhenJobUrlIsEmpty()
    {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = null;
        $client           = mock('Jenkins_Client');

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);
        $this->assertFalse($post_action_ci_build->isDefined());
    }

    public function itIsDefinedWhenJobUrlIsFilled()
    {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://example.com/job';
        $client           = mock('Jenkins_Client');

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);
        $this->assertTrue($post_action_ci_build->isDefined());
    }

    public function itExportsInXMLFormatTheJobUrl()
    {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://example.com';
        $client           = mock('Jenkins_Client');

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = array();

        $post_action_ci_build->exportToXml($root, $array_xml_mapping);
        $this->assertEqual((string) $root->postaction_ci_build['job_url'], $job_url);
    }

    public function itDoesNotExportThePostActionIfJobUrlIsNotSet()
    {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = '';
        $client           = mock('Jenkins_Client');

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = array();

        $post_action_ci_build->exportToXml($root, $array_xml_mapping);
        $this->assertFalse(isset($root->postaction_ci_build));
    }
}


class Transition_PostAction_CIBuildAfterTest extends TuleapTestCase
{

    protected $parameters;
    protected $tracker;
    protected $artifact;
    protected $project;
    protected $transition;
    protected $changeset;
    protected $field;
    protected $client;
    protected $post_action_ci_build;
    protected $job_url;

    public function setUp()
    {
        parent::setUp();

        $build_user             = 'mickey mooouse';
        $project_id             = 9852614;
        $artifact_id            = 333558899;
        $tracker_id             = 5245;
        $value_triggering_build = 'the fat field';

        $this->expected_parameters = array(
            Transition_PostAction_CIBuild::BUILD_PARAMETER_USER                => $build_user,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_PROJECT_ID          => $project_id,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_ARTIFACT_ID         => $artifact_id,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_TRACKER_ID          => $tracker_id,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_TRIGGER_FIELD_VALUE => $value_triggering_build,
        );

        $this->transition   = mock('Transition');
        $id                 = 123;
        $this->job_url      = 'http://example.com/job';
        $this->client       = mock('Jenkins_Client');

        $this->post_action_ci_build = new Transition_PostAction_CIBuild($this->transition, $id, $this->job_url, $this->client);

        $this->tracker = mock('Tracker');
        $this->project = mock('Project');
        $this->artifact = mock('Tracker_Artifact');
        $this->changeset = mock('Tracker_Artifact_Changeset');
        $this->field = mock('Tracker_FormElement_Field_Selectbox');

        stub($this->changeset)->getSubmittedBy()->returns($build_user);

        stub($this->changeset)->getArtifact()->returns($this->artifact);
        stub($this->artifact)->getTracker()->returns($this->tracker);
        stub($this->tracker)->getProject()->returns($this->project);
        stub($this->project)->getId()->returns($project_id);

        stub($this->artifact)->getId()->returns($artifact_id);

        stub($this->tracker)->getId()->returns($tracker_id);

        stub($this->transition)->getFieldValueTo()->returns($this->field);
        stub($this->field)->getLabel()->returns($value_triggering_build);
    }

    public function itLaunchTheCIBuildOnAfter()
    {
        expect($this->client)->launchJobBuild($this->job_url, '*')->once();
        $this->post_action_ci_build->after($this->changeset);
    }

    public function itDisplayInfoFeedbackIfLaunchSucceed()
    {
        expect($GLOBALS['Response'])->addFeedback('info', '*')->once();
        $this->post_action_ci_build->after($this->changeset);
    }

    public function itDisplayErrorFeedbackIfLaunchFailed()
    {
        $error_message = 'Oops';
        stub($this->client)->launchJobBuild($this->job_url, '*')->throws(new Jenkins_ClientUnableToLaunchBuildException($error_message));

        expect($GLOBALS['Response'])->addFeedback('error', $error_message)->once();
        $this->post_action_ci_build->after($this->changeset);
    }

    public function itIncludesTheNeededParameters()
    {
        expect($this->client)->launchJobBuild($this->job_url, $this->expected_parameters)->once();
        $this->post_action_ci_build->after($this->changeset);
    }

    public function itDoesNothingIfThePostActionIsNotDefined()
    {
        $id      = 123;
        $job_url = '';
        $post_action_ci_build = new Transition_PostAction_CIBuild($this->transition, $id, $job_url, $this->client);
        expect($GLOBALS['Response'])->addFeedback()->never();
        expect($this->client)->launchJobBuild()->never();
        $post_action_ci_build->after($this->changeset);
    }
}
