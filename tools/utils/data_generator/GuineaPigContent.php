<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'TuleapClient/TrackerFactory.php';
require_once 'TuleapClient/Request.php';

class GuineaPigContent {

    private $project_name;

    /** @var Client */
    private $request;

    public function __construct($project_name, $username, $password) {
        $this->project_name = $project_name;
        $this->request = new \TuleapClient\Request(
            new \Guzzle\Http\Client(
                'https://localhost/api',
                array(
                    'ssl.certificate_authority' => false
                )
            ),
            $username,
            $password
        );
    }

    public function setUp() {

        try {
            $tracker_factory = new TuleapClient\TrackerFactory(
                $this->request,
                $this->getProject($this->project_name)
            );

            $task_tracker = $tracker_factory->getTrackerRest('task');
            $task_1 = $task_tracker->createArtifact(
                array(
                    $task_tracker->getSubmitTextValue("Summary", "Add readme"),
                    $task_tracker->getSubmitListValue("Status", "To be done"),
                )
            );

            $story_tracker = $tracker_factory->getTrackerRest('story');
            $story_1 = $story_tracker->createArtifact(
                array(
                    $story_tracker->getSubmitTextValue("I want to", "have a meaningful documentation"),
                    $story_tracker->getSubmitListValue("Status", "To be done"),
                    $story_tracker->getSubmitArtifactLinkValue(array($task_1['id'])),
                )
            );

            $epic_tracker = $tracker_factory->getTrackerRest('epic');
            $epic_1 = $epic_tracker->createArtifact(
                array(
                    $epic_tracker->getSubmitTextValue("Summary", "Application documentation"),
                    $epic_tracker->getSubmitListValue("Status", "To be done"),
                    $epic_tracker->getSubmitArtifactLinkValue(array($story_1['id'])),
                )
            );

            $sprint_tracker = $tracker_factory->getTrackerRest('sprint');
            $sprint_1 = $sprint_tracker->createArtifact(
                array(
                    $sprint_tracker->getSubmitTextValue("Name", "Sprint 1"),
                    $sprint_tracker->getSubmitListValue("Status", "Current"),
                    $sprint_tracker->getSubmitArtifactLinkValue(array($story_1['id'])),
                )
            );

            $release_tracker = $tracker_factory->getTrackerRest('rel');
            $release_1 = $release_tracker->createArtifact(
                array(
                    $release_tracker->getSubmitTextValue("Name", "1.0"),
                    $release_tracker->getSubmitListValue('Status', 'Current'),
                    $release_tracker->getSubmitArtifactLinkValue(array($sprint_1['id'], $epic_1['id'])),
                )
            );


        }
        catch (Guzzle\Http\Exception\BadResponseException $exception) {
            echo $exception->getRequest();
            echo $exception->getResponse()->getBody(true);
            die(PHP_EOL);
        }
    }

    private function getProject($name) {
        foreach ($this->request->getJson($this->request->getClient()->get('projects')) as $project) {
            if ($project['label'] == $name) {
                return $project;
            }
        }
    }
}
