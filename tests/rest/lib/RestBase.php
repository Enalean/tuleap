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

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Test\Rest\Cache;
use Test\Rest\RequestWrapper;

class RestBase extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    protected RequestFactoryInterface $request_factory;
    protected StreamFactoryInterface $stream_factory;

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
    protected $project_public_template_id;
    protected $project_private_template_id;

    protected $epic_tracker_id;
    protected $releases_tracker_id;
    protected $sprints_tracker_id;
    protected $tasks_tracker_id;
    protected $user_stories_tracker_id;
    protected $deleted_tracker_id;
    protected $kanban_tracker_id;

    protected $project_ids     = [];
    protected $tracker_ids     = [];
    protected $user_groups_ids = [];
    protected $user_ids        = [];

    protected $tracker_representations = [];

    protected $release_artifact_ids = [];
    protected $epic_artifact_ids    = [];
    protected $story_artifact_ids   = [];
    protected $sprint_artifact_ids  = [];

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

        $base_url = 'https://localhost/api/';
        if (isset($_ENV['TULEAP_HOST'])) {
            $base_url = $_ENV['TULEAP_HOST'] . '/api/';
        }

        $this->cache = Cache::instance();

        $client                = new \GuzzleHttp\Client(['base_uri' => $base_url, 'verify' => false]);
        $this->request_factory = new \GuzzleHttp\Psr7\HttpFactory();
        $this->stream_factory  = new \GuzzleHttp\Psr7\HttpFactory();

        $this->rest_request = new RequestWrapper($client, $this->cache);

        $this->initialized = true;
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->initialize();

        $this->project_ids = $this->cache->getProjectIds();
        if (! $this->project_ids) {
            $this->initProjectIds();
        }

        $this->tracker_ids = $this->cache->getTrackerIds();
        if (! $this->tracker_ids) {
            $this->initTrackerIds();
        }

        $this->tracker_representations = $this->cache->getTrackerRepresentations();

        $this->user_ids = $this->cache->getUserIds();
        if (! $this->user_ids) {
            $this->initUserIds();
        }

        $this->user_groups_ids = $this->cache->getUserGroupIds();
        if (! $this->user_groups_ids) {
            $this->initUserGroupsId();
        }

        $this->project_private_member_id         = $this->getProjectId(
            REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_SHORTNAME
        );
        $this->project_private_id                = $this->getProjectId(REST_TestDataBuilder::PROJECT_PRIVATE_SHORTNAME);
        $this->project_public_id                 = $this->getProjectId(REST_TestDataBuilder::PROJECT_PUBLIC_SHORTNAME);
        $this->project_public_member_id          = $this->getProjectId(
            REST_TestDataBuilder::PROJECT_PUBLIC_MEMBER_SHORTNAME
        );
        $this->project_pbi_id                    = $this->getProjectId(REST_TestDataBuilder::PROJECT_PBI_SHORTNAME);
        $this->project_deleted_id                = $this->getProjectId(REST_TestDataBuilder::PROJECT_DELETED_SHORTNAME);
        $this->project_suspended_id              = $this->getProjectId(
            REST_TestDataBuilder::PROJECT_SUSPENDED_SHORTNAME
        );
        $this->project_public_with_membership_id = $this->getProjectId(
            REST_TestDataBuilder::PROJECT_PUBLIC_WITH_MEMBERSHIP_SHORTNAME
        );
        $this->project_future_releases_id        = $this->getProjectId(REST_TestDataBuilder::PROJECT_FUTURE_RELEASES);
        $this->project_services_id               = $this->getProjectId(REST_TestDataBuilder::PROJECT_SERVICES);
        $this->project_public_template_id        = $this->getProjectId(REST_TestDataBuilder::PROJECT_PUBLIC_TEMPLATE);
        $this->project_private_template_id       = $this->getProjectId(REST_TestDataBuilder::PROJECT_PRIVATE_TEMPLATE);

        $this->getTrackerIdsForProjectPrivateMember();
    }

    protected function getResponse($request, $user_name = REST_TestDataBuilder::TEST_USER_1_NAME): ResponseInterface
    {
        return $this->getResponseByName(
            $user_name,
            $request
        );
    }

    protected function getResponseForReadOnlyUserAdmin(RequestInterface $request)
    {
        return $this->getResponseByName(
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
            $request
        );
    }

    protected function getResponseWithoutAuth(RequestInterface $request)
    {
        return $this->rest_request->getResponseWithoutAuth($request);
    }

    protected function getResponseByName($name, RequestInterface $request): ResponseInterface
    {
        return $this->rest_request->getResponseByName($name, $request);
    }

    protected function getResponseByBasicAuth($username, $password, RequestInterface $request)
    {
        return $this->rest_request->getResponseByBasicAuth($username, $password, $request);
    }

    private function initProjectIds()
    {
        $offset                    = 0;
        $limit                     = 50;
        $query_for_active_projects = [
            'limit' => $limit, 'offset' => $offset,
        ];

        $this->getProjectsIdsWithQuery($query_for_active_projects, $limit);

        $deleted_status_label       = 'deleted';
        $query_for_deleted_projects = [
            'limit'  => $limit,
            'offset' => $offset,
            'query'  => json_encode(["with_status" => $deleted_status_label]),
        ];

        $this->getProjectsIdsWithQuery($query_for_deleted_projects, $limit);

        $suspended_status_label       = 'suspended';
        $query_for_suspended_projects = [
            'limit'  => $limit,
            'offset' => $offset,
            'query'  => json_encode(["with_status" => $suspended_status_label]),
        ];

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
            ['limit' => $limit, 'offset' => $offset]
        );

        $tracker_ids            = [];
        $tracker_representation = [];

        do {
            $response = $this->getResponseByName(
                REST_TestDataBuilder::ADMIN_USER_NAME,
                $this->request_factory->createRequest('GET', "projects/$project_id/trackers?$query")
            );

            $trackers = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $number_of_tracker = (int) $response->getHeaderLine('X-Pagination-Size');

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
        $this->deleted_tracker_id = $this->user_stories_tracker_id + 1;

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
        if (! $artifacts) {
            $query = http_build_query(
                ['order' => 'asc']
            );

            $artifacts = json_decode(
                $this->getResponseByName(
                    REST_TestDataBuilder::ADMIN_USER_NAME,
                    $this->request_factory->createRequest('GET', "trackers/$tracker_id/artifacts?$query")
                )->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $this->cache->setArtifacts($tracker_id, $artifacts);
        }
        return $artifacts;
    }

    public function getUserGroupsByProjectId($project_id)
    {
        if (isset($this->user_groups_ids[$project_id])) {
            return $this->user_groups_ids[$project_id];
        }

        return [];
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
                $this->request_factory->createRequest('GET', "projects/$project_id/user_groups")
            );

            $ugroups = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            foreach ($ugroups as $ugroup) {
                $this->user_groups_ids[$project_id][$ugroup['short_name']] = $ugroup['id'];
            }
            $this->cache->setUserGroupIds($this->user_groups_ids);
        } catch (ClientExceptionInterface $e) {
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

    private function getProjectsIdsWithQuery(array $query, int $limit)
    {
        $offset = 0;

        do {
            $uri      = "projects/?" . http_build_query($query);
            $response = $this->getResponseByName(
                REST_TestDataBuilder::ADMIN_USER_NAME,
                $this->request_factory->createRequest('GET', $uri)
            );

            $projects = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $number_of_project = (int) $response->getHeaderLine('X-Pagination-Size');
            $this->addProjectIdFromRequestData($projects);

            $offset         += $limit;
            $query['offset'] = $offset;
        } while ($offset < $number_of_project);
    }

    protected function initUserId($user_name)
    {
        $query = urlencode(
            json_encode([
                "username" => $user_name,
            ])
        );

        $response = $this->getResponseByName(
            TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', "users/?query=$query")
        );
        $user     = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

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
