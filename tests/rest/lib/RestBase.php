<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Guzzle\Http\Client;
use PHPUnit\Framework\TestCase;
use Test\Rest\Cache;
use Test\Rest\RequestWrapper;

class RestBase extends TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    protected $base_url = 'https://localhost/api/v1';
    private $setup_url  = 'https://localhost/api/v1';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Client
     */
    protected $setup_client;

    /**
     * @var RequestWrapper
     */
    protected $rest_request;

    protected $project_private_member_id;
    protected $project_private_id;
    protected $project_public_id;
    protected $project_public_member_id;
    protected $project_pbi_id;
    protected $project_deleted_id;
    protected $project_suspended_id;
    protected $project_public_with_membership_id;
    protected $project_future_releases_id;
    protected $project_services_id;

    protected $epic_tracker_id;
    protected $releases_tracker_id;
    protected $sprints_tracker_id;
    protected $tasks_tracker_id;
    protected $user_stories_tracker_id;
    protected $deleted_tracker_id;
    protected $kanban_tracker_id;

    protected $project_ids = array();
    protected $tracker_ids = array();
    protected $user_groups_ids = array();
    protected $user_ids = [];

    protected $tracker_representations = [];

    protected $release_artifact_ids = array();
    protected $epic_artifact_ids = array();
    protected $story_artifact_ids = array();
    protected $sprint_artifact_ids = array();

    /**
     * @var Cache
     */
    protected $cache;

    private $initialized = false;
    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        if (isset($_ENV['TULEAP_HOST'])) {
            $this->base_url  = $_ENV['TULEAP_HOST'] . '/api/v1';
            $this->setup_url = $_ENV['TULEAP_HOST'] . '/api/v1';
        }

        $this->cache = Cache::instance();

        $this->client = new Client(
            $this->base_url,
            [
                'request.options' => [
                    'exceptions' => false,
                ]
            ]
        );
        $this->client->setSslVerification(false, false, false);
        $this->setup_client = new Client($this->setup_url);
        $this->setup_client->setCurlMulti($this->client->getCurlMulti());
        $this->setup_client->setSslVerification(false, false, false);

        $this->client->setDefaultOption('headers/Accept', 'application/json');
        $this->client->setDefaultOption('headers/Content-Type', 'application/json');

        $this->setup_client->setDefaultOption('headers/Accept', 'application/json');
        $this->setup_client->setDefaultOption('headers/Content-Type', 'application/json');

        $this->rest_request = new RequestWrapper($this->client, $this->cache);

        $this->initialized = true;
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->initialize();

        $this->project_ids = $this->cache->getProjectIds();
        if (!$this->project_ids) {
            $this->initProjectIds();
        }

        $this->tracker_ids = $this->cache->getTrackerIds();
        if (!$this->tracker_ids) {
            $this->initTrackerIds();
        }

        $this->tracker_representations = $this->cache->getTrackerRepresentations();

        $this->user_ids = $this->cache->getUserIds();
        if (!$this->user_ids) {
            $this->initUserIds();
        }

        $this->user_groups_ids = $this->cache->getUserGroupIds();
        if (!$this->user_groups_ids) {
            $this->initUserGroupsId();
        }

        $this->project_private_member_id         = $this->getProjectId(REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_SHORTNAME);
        $this->project_private_id                = $this->getProjectId(REST_TestDataBuilder::PROJECT_PRIVATE_SHORTNAME);
        $this->project_public_id                 = $this->getProjectId(REST_TestDataBuilder::PROJECT_PUBLIC_SHORTNAME);
        $this->project_public_member_id          = $this->getProjectId(REST_TestDataBuilder::PROJECT_PUBLIC_MEMBER_SHORTNAME);
        $this->project_pbi_id                    = $this->getProjectId(REST_TestDataBuilder::PROJECT_PBI_SHORTNAME);
        $this->project_deleted_id                = $this->getProjectId(REST_TestDataBuilder::PROJECT_DELETED_SHORTNAME);
        $this->project_suspended_id              = $this->getProjectId(REST_TestDataBuilder::PROJECT_SUSPENDED_SHORTNAME);
        $this->project_public_with_membership_id = $this->getProjectId(REST_TestDataBuilder::PROJECT_PUBLIC_WITH_MEMBERSHIP_SHORTNAME);
        $this->project_future_releases_id        = $this->getProjectId(REST_TestDataBuilder::PROJECT_FUTURE_RELEASES);
        $this->project_services_id               = $this->getProjectId(REST_TestDataBuilder::PROJECT_SERVICES);

        $this->getTrackerIdsForProjectPrivateMember();
    }

    protected function getResponse($request, $user_name = REST_TestDataBuilder::TEST_USER_1_NAME)
    {
        return $this->getResponseByName(
            $user_name,
            $request
        );
    }

    protected function getResponseForReadOnlyUserAdmin($request)
    {
        return $this->getResponseByName(
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
            $request
        );
    }

    protected function getResponseWithoutAuth($request)
    {
        return $this->rest_request->getResponseWithoutAuth($request);
    }

    protected function getResponseByName($name, $request)
    {
        return $this->rest_request->getResponseByName($name, $request);
    }

    protected function getResponseByBasicAuth($username, $password, $request)
    {
        return $this->rest_request->getResponseByBasicAuth($username, $password, $request);
    }

    private function initProjectIds()
    {
        $offset = 0;
        $limit  = 50;
        $query_for_active_projects  = http_build_query([
            'limit' => $limit, 'offset' => $offset
        ]);

        $this->getProjectsIdsWithQuery($query_for_active_projects, $limit);

        $deleted_status_label       = 'deleted';
        $query_for_deleted_projects = http_build_query([
            'limit'  => $limit,
            'offset' => $offset,
            'query'  => json_encode(["with_status" => $deleted_status_label])
        ]);

        $this->getProjectsIdsWithQuery($query_for_deleted_projects, $limit);

        $suspended_status_label       = 'suspended';
        $query_for_suspended_projects = http_build_query([
            'limit'  => $limit,
            'offset' => $offset,
            'query'  => json_encode(["with_status" => $suspended_status_label])
        ]);

        $this->getProjectsIdsWithQuery($query_for_suspended_projects, $limit);
    }

    private function addProjectIdFromRequestData(array $projects)
    {
        foreach ($projects as $project) {
            $project_name = $project['shortname'];
            $project_id   = $project['id'];

            $this->project_ids[$project_name] = $project_id;
        }
        $this->cache->setProjectIds($this->project_ids);
    }

    protected function getProjectId($project_short_name)
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

        $tracker_ids            = array();
        $tracker_representation = [];

        do {
            $response = $this->getResponseByName(
                REST_TestDataBuilder::ADMIN_USER_NAME,
                $this->setup_client->get("projects/$project_id/trackers?$query")
            );

            $trackers          = $response->json();
            $number_of_tracker = (int) (string) $response->getHeader('X-Pagination-Size');

            $this->addTrackerIdFromRequestData($trackers, $tracker_ids);
            $this->addTrackerRepresentationFromRequestData($trackers, $tracker_representation);

            $offset += $limit;
        } while ($offset < $number_of_tracker);

        $this->cache->addTrackerRepresentations($tracker_representation);

        $this->tracker_ids[$project_id] = $tracker_ids;
        $this->cache->setTrackerIds($this->tracker_ids);
    }

    private function addTrackerIdFromRequestData(array $trackers, array &$tracker_ids)
    {
        foreach ($trackers as $tracker) {
            $tracker_id        = $tracker['id'];
            $tracker_shortname = $tracker['item_name'];

            $tracker_ids[$tracker_shortname] = $tracker_id;
        }
    }

    private function addTrackerRepresentationFromRequestData(array $trackers, array &$tracker_representation)
    {
        foreach ($trackers as $tracker) {
            $tracker_id = $tracker['id'];

            $tracker_representation[$tracker_id] = $tracker;
        }
    }

    private function getTrackerIdsForProjectPrivateMember()
    {
        $this->epic_tracker_id         = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::EPICS_TRACKER_SHORTNAME];
        $this->releases_tracker_id     = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::RELEASES_TRACKER_SHORTNAME];
        $this->sprints_tracker_id      = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::SPRINTS_TRACKER_SHORTNAME];
        $this->tasks_tracker_id        = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::TASKS_TRACKER_SHORTNAME];
        $this->user_stories_tracker_id = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::USER_STORIES_TRACKER_SHORTNAME];
        // Since the tracker is deleted it can not be retrieved through the REST route, it is however expected to be created right after
        // the user story tracker so hopefully it takes the next available ID. This is a weak assumption, a clean way to achieve the test
        // would be to introduce a DELETE trackers/:id route. See tests/rest/_fixtures/01-private-member/project.xml.
        $this->deleted_tracker_id      = $this->user_stories_tracker_id + 1;

        $this->kanban_tracker_id = $this->tracker_ids[$this->project_private_member_id][REST_TestDataBuilder::KANBAN_TRACKER_SHORTNAME];
    }

    protected function getReleaseArtifactIds()
    {
        $this->getArtifactIds(
            $this->releases_tracker_id,
            $this->release_artifact_ids
        );
    }

    protected function getEpicArtifactIds()
    {
        $this->getArtifactIds(
            $this->epic_tracker_id,
            $this->epic_artifact_ids
        );
    }

    protected function getStoryArtifactIds()
    {
        $this->getArtifactIds(
            $this->user_stories_tracker_id,
            $this->story_artifact_ids
        );
    }

    protected function getSprintArtifactIds()
    {
        $this->getArtifactIds(
            $this->sprints_tracker_id,
            $this->sprint_artifact_ids
        );
    }

    protected function getArtifactIds($tracker_id, array &$retrieved_artifact_ids)
    {
        if (count($retrieved_artifact_ids) > 0) {
            return $retrieved_artifact_ids;
        }

        $artifacts = $this->getArtifacts($tracker_id);

        $index = 1;
        foreach ($artifacts as $artifact) {
            $retrieved_artifact_ids[$index] = $artifact['id'];
            $index++;
        }
    }

    protected function getArtifacts($tracker_id): array
    {
        $artifacts = $this->cache->getArtifacts($tracker_id);
        if (!$artifacts) {
            $query = http_build_query(
                array('order' => 'asc')
            );

            $artifacts = $this->getResponseByName(
                REST_TestDataBuilder::ADMIN_USER_NAME,
                $this->setup_client->get("trackers/$tracker_id/artifacts?$query")
            )->json();

            $this->cache->setArtifacts($tracker_id, $artifacts);
        }
        return $artifacts;
    }

    public function getUserGroupsByProjectId($project_id)
    {
        if (isset($this->user_groups_ids[$project_id])) {
            return $this->user_groups_ids[$project_id];
        }

        return array();
    }

    private function initUserGroupsId()
    {
        foreach ($this->project_ids as $project_id) {
            $this->extractUserGroupsForProject($project_id);
        }
    }

    private function extractUserGroupsForProject($project_id)
    {
        try {
            $response = $this->getResponseByName(
                REST_TestDataBuilder::ADMIN_USER_NAME,
                $this->setup_client->get("projects/$project_id/user_groups")
            );

            $ugroups = $response->json();
            foreach ($ugroups as $ugroup) {
                $this->user_groups_ids[$project_id][$ugroup['short_name']] = $ugroup['id'];
            }
            $this->cache->setUserGroupIds($this->user_groups_ids);
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
        }
    }

    protected function getArtifactIdsIndexedByTitle(string $project_name, string $tracker_name): array
    {
        $tracker_id = $this->cache->getTrackerInProject($project_name, $tracker_name);

        $ids = [];
        foreach ($this->getArtifacts($tracker_id) as $artifact) {
            $ids[$artifact['title']] = $artifact['id'];
        }
        return $ids;
    }

    private function getProjectsIdsWithQuery($query, $limit)
    {
        $offset = 0;

        do {
            $response = $this->getResponseByName(
                REST_TestDataBuilder::ADMIN_USER_NAME,
                $this->setup_client->get("projects/?$query")
            );

            $projects = $response->json();

            $number_of_project = (int) (string) $response->getHeader('X-Pagination-Size');

            $this->addProjectIdFromRequestData($projects);

            $offset += $limit;
        } while ($offset < $number_of_project);
    }

    protected function initUserId($user_name)
    {
        $query = urlencode(
            json_encode([
                "username" => $user_name
            ])
        );

        $response = $this->getResponseByName(
            TestDataBuilder::ADMIN_USER_NAME,
            $this->setup_client->get("users/?query=$query")
        );
        $user     = $response->json();

        $this->addUserIdFromRequestData($user[0]);
    }

    private function initUserIds()
    {
        $this->initUserId(TestDataBuilder::ADMIN_USER_NAME);
        $this->initUserId(TestDataBuilder::TEST_USER_1_NAME);
        $this->initUserId(TestDataBuilder::TEST_USER_2_NAME);
        $this->initUserId(TestDataBuilder::TEST_USER_3_NAME);
        $this->initUserId(REST_TestDataBuilder::TEST_USER_4_NAME);
        $this->initUserId(TestDataBuilder::TEST_USER_5_NAME);
        $this->initUserId(TestDataBuilder::TEST_USER_RESTRICTED_1_NAME);
        $this->initUserId(TestDataBuilder::TEST_USER_RESTRICTED_2_NAME);
    }

    private function addUserIdFromRequestData($user)
    {
        $this->user_ids[$user["username"]] = $user["id"];
        $this->cache->setUserId($user);
    }
}
