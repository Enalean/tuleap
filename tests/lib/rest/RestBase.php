<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All rights reserved
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

require_once dirname(__FILE__).'/../autoload.php';
require_once 'common/autoload.php';

use \Guzzle\Http\Client;
use \Test\Rest\RequestWrapper;

class RestBase extends PHPUnit_Framework_TestCase {

    protected $base_url  = 'http://localhost/api/v1';
    private   $setup_url = 'http://localhost/api/v1';

    /**
     * @var Client
     */
    protected $client;

    /**
    * @var Client
    */
    protected $setup_client;

    /**
     * @var Client
     */
    protected $xml_client;

    /**
     * @var RequestWrapper
     */
    protected $rest_request;

    protected $project_private_member_id;
    protected $project_private_id;
    protected $project_public_id;
    protected $project_public_member_id;
    protected $project_pbi_id;

    protected $epic_tracker_id;
    protected $releases_tracker_id;
    protected $sprints_tracker_id;
    protected $tasks_tracker_id;
    protected $user_stories_tracker_id;
    protected $deleted_tracker_id;
    protected $kanban_tracker_id;

    protected $project_ids = array();
    protected $tracker_ids = array();

    protected $release_artifact_ids = array();

    public function __construct() {
        parent::__construct();
        if (isset($_ENV['TULEAP_HOST'])) {
            $this->base_url  = $_ENV['TULEAP_HOST'].'/api/v1';
            $this->setup_url = $_ENV['TULEAP_HOST'].'/api/v1';
        }

        $this->rest_request = new RequestWrapper();
    }

    public function setUp() {
        parent::setUp();

        $this->client       = new Client($this->base_url, array('ssl.certificate_authority' => 'system'));
        $this->setup_client = new Client($this->setup_url, array('ssl.certificate_authority' => 'system'));
        $this->xml_client   = new Client($this->base_url, array('ssl.certificate_authority' => 'system'));

        $this->client->setDefaultOption('headers/Accept', 'application/json');
        $this->client->setDefaultOption('headers/Content-Type', 'application/json');

        $this->xml_client->setDefaultOption('headers/Accept', 'application/xml');
        $this->xml_client->setDefaultOption('headers/Content-Type', 'application/xml; charset=UTF8');

        $this->setup_client->setDefaultOption('headers/Accept', 'application/json');
        $this->setup_client->setDefaultOption('headers/Content-Type', 'application/json');

        if (! $this->project_ids) {
            $this->initProjectIds();
        }

        if (! $this->tracker_ids) {
            $this->initTrackerIds();
        }

        $this->project_private_member_id = $this->getProjectId(REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_SHORTNAME);
        $this->project_private_id        = $this->getProjectId(REST_TestDataBuilder::PROJECT_PRIVATE_SHORTNAME);
        $this->project_public_id         = $this->getProjectId(REST_TestDataBuilder::PROJECT_PUBLIC_SHORTNAME);
        $this->project_public_member_id  = $this->getProjectId(REST_TestDataBuilder::PROJECT_PUBLIC_MEMBER_SHORTNAME);
        $this->project_pbi_id            = $this->getProjectId(REST_TestDataBuilder::PROJECT_PBI_SHORTNAME);

        $this->getTrackerIdsForProjectPrivateMember();

        $this->getReleaseArtifactIds();
    }

    protected function getResponseWithoutAuth($request) {
        return $this->rest_request->getResponseWithoutAuth($request);
    }

    protected function getResponseByName($name, $request) {
        return $this->rest_request->getResponseByName($name, $request);
    }

    protected function getResponseByToken(Rest_Token $token, $request) {
        return $this->rest_request->getResponseByToken($token, $request);
    }

    protected function getTokenForUserName($user_name) {
        return $this->rest_request->getTokenForUserName($user_name);
    }

    protected function getResponseByBasicAuth($username, $password, $request) {
        return $this->rest_request->getResponseByBasicAuth($username, $password, $request);
    }

    private function initProjectIds()
    {
        $offset = 0;
        $limit  = 50;
        $query  = http_build_query(
            array('limit' => $limit, 'offset' => $offset)
        );

        do {
            $response = $this->getResponseByName(
                REST_TestDataBuilder::ADMIN_USER_NAME,
                $this->setup_client->get("projects/?$query")
            );

            $projects          = $response->json();
            $number_of_project = (int) (string) $response->getHeader('X-Pagination-Size');

            $this->addProjectIdFromRequestData($projects);

            $offset += $limit;
        } while ($offset < $number_of_project);
    }

    private function addProjectIdFromRequestData(array $projects)
    {
        foreach ($projects as $project) {
            $project_name = $project['shortname'];
            $project_id   = $project['id'];

            $this->project_ids[$project_name] = $project_id;
        }
    }

    private function getProjectId($project_short_name)
    {
        return $this->project_ids[$project_short_name];
    }

    private function initTrackerIds()
    {
        foreach ($this->project_ids as $project_id) {
            $this->extractTrackersForProject($project_id);
        }
    }

    private function extractTrackersForProject($project_id)
    {
        $offset = 0;
        $limit  = 50;
        $query  = http_build_query(
            array('limit' => $limit, 'offset' => $offset)
        );

        $tracker_ids = array();

        do {
            $response = $this->getResponseByName(
                REST_TestDataBuilder::ADMIN_USER_NAME,
                $this->setup_client->get("projects/$project_id/trackers?$query")
            );

            $trackers          = $response->json();
            $number_of_tracker = (int) (string) $response->getHeader('X-Pagination-Size');

            $this->addTrackerIdFromRequestData($trackers, $tracker_ids);

            $offset += $limit;
        } while ($offset < $number_of_tracker);

        $this->tracker_ids[$project_id] = $tracker_ids;
    }

    private function addTrackerIdFromRequestData(array $trackers, array &$tracker_ids)
    {
        foreach ($trackers as $tracker) {
            $tracker_id        = $tracker['id'];
            $tracker_shortname = $tracker['item_name'];

            $tracker_ids[$tracker_shortname] = $tracker_id;
        }
    }

    private function getTrackerIdsForProjectPrivateMember()
    {
        $this->epic_tracker_id         = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::EPICS_TRACKER_SHORTNAME];
        $this->releases_tracker_id     = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::RELEASES_TRACKER_SHORTNAME];
        $this->sprints_tracker_id      = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::SPRINTS_TRACKER_SHORTNAME];
        $this->tasks_tracker_id        = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::TASKS_TRACKER_SHORTNAME];
        $this->user_stories_tracker_id = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::USER_STORIES_TRACKER_SHORTNAME];

        if (isset($this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::DELETED_TRACKER_SHORTNAME])) {
            $this->deleted_tracker_id = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::DELETED_TRACKER_SHORTNAME];
        }

        $this->kanban_tracker_id = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::KANBAN_TRACKER_SHORTNAME];
    }

    private function getReleaseArtifactIds()
    {
        $this->getArtifactIds(
            $this->releases_tracker_id,
            $this->release_artifact_ids
        );
    }

    protected function getArtifactIds($tracker_id, array &$retrieved_tracker_ids)
    {
        $query = http_build_query(
            array('order' => 'asc')
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->setup_client->get("trackers/$tracker_id/artifacts?$query")
        );

        $artifacts = $response->json();
        $index     = 1;
        foreach ($artifacts as $artifact) {
            $retrieved_tracker_ids[$index] = $artifact['id'];
            $index++;
        }
    }
}
