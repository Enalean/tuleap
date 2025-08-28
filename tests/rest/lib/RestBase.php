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

declare(strict_types=1);

namespace Tuleap\REST;

use GuzzleHttp\Psr7\HttpFactory;
use Psl\Json;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Test\PHPUnit\TestCase;

class RestBase extends TestCase
{
    protected RequestFactoryInterface $request_factory;
    protected StreamFactoryInterface $stream_factory;

    protected RequestWrapper $rest_request;

    protected int $project_private_member_id;
    protected int $project_private_id;
    protected int $project_public_id;
    protected int $project_public_member_id;
    protected int $project_pbi_id;
    protected int $project_deleted_id;
    protected int $project_suspended_id;
    protected int $project_public_with_membership_id;
    protected int $project_future_releases_id;
    protected int $project_services_id;
    protected int $project_public_template_id;
    protected int $project_private_template_id;

    protected int $epic_tracker_id;
    protected int $releases_tracker_id;
    protected int $sprints_tracker_id;
    protected int $tasks_tracker_id;
    protected int $user_stories_tracker_id;
    protected int $deleted_tracker_id;
    protected int $kanban_tracker_id;

    /** @var array<string, int> $project_ids */
    protected array $project_ids = [];
    /** @var array<int, array<string, int>> $tracker_ids */
    protected array $tracker_ids = [];
    /** @var array<int, array<string, string>> $user_groups_ids */
    protected array $user_groups_ids = [];
    /** @var array<string, int> */
    protected array $user_ids = [];

    protected array $tracker_representations = [];

    protected array $release_artifact_ids = [];
    protected array $epic_artifact_ids    = [];
    protected array $story_artifact_ids   = [];
    protected array $sprint_artifact_ids  = [];

    protected Cache $cache;

    private bool $initialized = false;

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

        $client                = new \GuzzleHttp\Client(
            [
                'base_uri' => $base_url,
                'verify'   => false,
                'headers'  => ['Connection' => 'close'],
            ]
        );
        $this->request_factory = new HttpFactory();
        $this->stream_factory  = new HttpFactory();

        $this->rest_request = new RequestWrapper($client, $this->cache);

        $this->initialized = true;
    }

    #[\Override]
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
            RESTTestDataBuilder::PROJECT_PRIVATE_MEMBER_SHORTNAME
        );
        $this->project_private_id                = $this->getProjectId(RESTTestDataBuilder::PROJECT_PRIVATE_SHORTNAME);
        $this->project_public_id                 = $this->getProjectId(RESTTestDataBuilder::PROJECT_PUBLIC_SHORTNAME);
        $this->project_public_member_id          = $this->getProjectId(
            RESTTestDataBuilder::PROJECT_PUBLIC_MEMBER_SHORTNAME
        );
        $this->project_pbi_id                    = $this->getProjectId(RESTTestDataBuilder::PROJECT_PBI_SHORTNAME);
        $this->project_deleted_id                = $this->getProjectId(RESTTestDataBuilder::PROJECT_DELETED_SHORTNAME);
        $this->project_suspended_id              = $this->getProjectId(
            RESTTestDataBuilder::PROJECT_SUSPENDED_SHORTNAME
        );
        $this->project_public_with_membership_id = $this->getProjectId(
            RESTTestDataBuilder::PROJECT_PUBLIC_WITH_MEMBERSHIP_SHORTNAME
        );
        $this->project_future_releases_id        = $this->getProjectId(RESTTestDataBuilder::PROJECT_FUTURE_RELEASES);
        $this->project_services_id               = $this->getProjectId(RESTTestDataBuilder::PROJECT_SERVICES);
        $this->project_public_template_id        = $this->getProjectId(RESTTestDataBuilder::PROJECT_PUBLIC_TEMPLATE);
        $this->project_private_template_id       = $this->getProjectId(RESTTestDataBuilder::PROJECT_PRIVATE_TEMPLATE);

        $this->getTrackerIdsForProjectPrivateMember();
    }

    protected function getResponse(
        RequestInterface $request,
        string $user_name = RESTTestDataBuilder::TEST_USER_1_NAME,
    ): ResponseInterface {
        return $this->getResponseByName($user_name, $request);
    }

    protected function getResponseForReadOnlyUserAdmin(RequestInterface $request): ResponseInterface
    {
        return $this->getResponseByName(RESTTestDataBuilder::TEST_BOT_USER_NAME, $request);
    }

    protected function getResponseWithoutAuth(RequestInterface $request): ResponseInterface
    {
        return $this->rest_request->getResponseWithoutAuth($request);
    }

    protected function getResponseByName(string $name, RequestInterface $request): ResponseInterface
    {
        return $this->rest_request->getResponseByName($name, $request);
    }

    protected function getResponseByBasicAuth(string $username, string $password, RequestInterface $request): ResponseInterface
    {
        return $this->rest_request->getResponseByBasicAuth($username, $password, $request);
    }

    private function initProjectIds(): void
    {
        $offset                    = 0;
        $limit                     = 50;
        $query_for_active_projects = ['limit' => $limit, 'offset' => $offset,];

        $this->getProjectsIdsWithQuery($query_for_active_projects, $limit);

        $deleted_status_label       = 'deleted';
        $query_for_deleted_projects = [
            'limit'  => $limit,
            'offset' => $offset,
            'query'  => Json\encode(['with_status' => $deleted_status_label]),
        ];

        $this->getProjectsIdsWithQuery($query_for_deleted_projects, $limit);

        $suspended_status_label       = 'suspended';
        $query_for_suspended_projects = [
            'limit'  => $limit,
            'offset' => $offset,
            'query'  => Json\encode(['with_status' => $suspended_status_label]),
        ];

        $this->getProjectsIdsWithQuery($query_for_suspended_projects, $limit);
    }

    /** @param list<array{id: int, shortname: string}> $projects */
    private function addProjectIdFromRequestData(array $projects): void
    {
        foreach ($projects as $project) {
            $project_name = $project['shortname'];
            $project_id   = $project['id'];

            $this->project_ids[$project_name] = $project_id;
        }
        $this->cache->setProjectIds($this->project_ids);
    }

    protected function getProjectId(string $project_short_name): int
    {
        return $this->project_ids[$project_short_name];
    }

    private function initTrackerIds(): void
    {
        foreach ($this->project_ids as $project_id) {
            $this->extractTrackersForProject($project_id);
        }
    }

    private function extractTrackersForProject(int $project_id): void
    {
        $offset = 0;
        $limit  = 50;
        $query  = http_build_query(['limit' => $limit, 'offset' => $offset]);

        $tracker_ids            = [];
        $tracker_representation = [];

        do {
            $response = $this->getResponseByName(
                RESTTestDataBuilder::ADMIN_USER_NAME,
                $this->request_factory->createRequest('GET', "projects/$project_id/trackers?$query")
            );

            $trackers = Json\decode($response->getBody()->getContents());

            $number_of_tracker = (int) $response->getHeaderLine('X-Pagination-Size');

            $this->addTrackerIdFromRequestData($trackers, $tracker_ids);
            $this->addTrackerRepresentationFromRequestData($trackers, $tracker_representation);

            $offset += $limit;
        } while ($offset < $number_of_tracker);

        $this->cache->addTrackerRepresentations($tracker_representation);

        $this->tracker_ids[$project_id] = $tracker_ids;
        $this->cache->setTrackerIds($this->tracker_ids);
    }

    private function addTrackerIdFromRequestData(array $trackers, array &$tracker_ids): void
    {
        foreach ($trackers as $tracker) {
            $tracker_id        = $tracker['id'];
            $tracker_shortname = $tracker['item_name'];

            $tracker_ids[$tracker_shortname] = $tracker_id;
        }
    }

    private function addTrackerRepresentationFromRequestData(array $trackers, array &$tracker_representation): void
    {
        foreach ($trackers as $tracker) {
            $tracker_id = $tracker['id'];

            $tracker_representation[$tracker_id] = $tracker;
        }
    }

    private function getTrackerIdsForProjectPrivateMember(): void
    {
        $this->epic_tracker_id         = $this->tracker_ids[$this->project_private_member_id][RESTTestDataBuilder::EPICS_TRACKER_SHORTNAME];
        $this->releases_tracker_id     = $this->tracker_ids[$this->project_private_member_id][RESTTestDataBuilder::RELEASES_TRACKER_SHORTNAME];
        $this->sprints_tracker_id      = $this->tracker_ids[$this->project_private_member_id][RESTTestDataBuilder::SPRINTS_TRACKER_SHORTNAME];
        $this->tasks_tracker_id        = $this->tracker_ids[$this->project_private_member_id][RESTTestDataBuilder::TASKS_TRACKER_SHORTNAME];
        $this->user_stories_tracker_id = $this->tracker_ids[$this->project_private_member_id][RESTTestDataBuilder::USER_STORIES_TRACKER_SHORTNAME];
        // Since the tracker is deleted it can not be retrieved through the REST route, it is however expected to be created right after
        // the user story tracker so hopefully it takes the next available ID. This is a weak assumption, a clean way to achieve the test
        // would be to introduce a DELETE trackers/:id route. See tests/rest/_fixtures/01-private-member/project.xml.
        $this->deleted_tracker_id = $this->user_stories_tracker_id + 1;

        $this->kanban_tracker_id = $this->tracker_ids[$this->project_private_member_id][RESTTestDataBuilder::KANBAN_TRACKER_SHORTNAME];
    }

    protected function getReleaseArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->releases_tracker_id,
            $this->release_artifact_ids
        );
    }

    protected function getEpicArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->epic_tracker_id,
            $this->epic_artifact_ids
        );
    }

    protected function getStoryArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->user_stories_tracker_id,
            $this->story_artifact_ids
        );
    }

    protected function getSprintArtifactIds(): void
    {
        $this->getArtifactIds(
            $this->sprints_tracker_id,
            $this->sprint_artifact_ids
        );
    }

    protected function getArtifactIds(int $tracker_id, array &$retrieved_artifact_ids): void
    {
        if (count($retrieved_artifact_ids) > 0) {
            return;
        }

        $artifacts = $this->getArtifacts($tracker_id);

        $index = 1;
        foreach ($artifacts as $artifact) {
            $retrieved_artifact_ids[$index] = $artifact['id'];
            $index++;
        }
    }

    /**
     * @return array<array{id: int, title: string}>
     */
    protected function getArtifacts(int $tracker_id): array
    {
        $artifacts = $this->cache->getArtifacts($tracker_id);
        if ($artifacts !== null) {
            return $artifacts;
        }

        $query     = http_build_query(['order' => 'asc']);
        $artifacts = Json\decode(
            $this->getResponseByName(
                RESTTestDataBuilder::ADMIN_USER_NAME,
                $this->request_factory->createRequest('GET', "trackers/$tracker_id/artifacts?$query")
            )->getBody()->getContents(),
        );

        $this->cache->setArtifacts($tracker_id, $artifacts);
        return $artifacts;
    }

    /** @return array<string, string> */
    public function getUserGroupsByProjectId(int $project_id): array
    {
        return $this->user_groups_ids[$project_id] ?? [];
    }

    private function initUserGroupsId(): void
    {
        foreach ($this->project_ids as $project_id) {
            $this->extractUserGroupsForProject($project_id);
        }
    }

    private function extractUserGroupsForProject(int $project_id): void
    {
        try {
            $response = $this->getResponseByName(
                RESTTestDataBuilder::ADMIN_USER_NAME,
                $this->request_factory->createRequest('GET', "projects/$project_id/user_groups")
            );

            $ugroups = Json\decode($response->getBody()->getContents());
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

    private function getProjectsIdsWithQuery(array $query, int $limit): void
    {
        $offset = 0;

        do {
            $uri      = 'projects/?' . http_build_query($query);
            $response = $this->getResponseByName(
                RESTTestDataBuilder::ADMIN_USER_NAME,
                $this->request_factory->createRequest('GET', $uri)
            );

            $projects = Json\decode($response->getBody()->getContents());

            $number_of_project = (int) $response->getHeaderLine('X-Pagination-Size');
            $this->addProjectIdFromRequestData($projects);

            $offset         += $limit;
            $query['offset'] = $offset;
        } while ($offset < $number_of_project);
    }

    protected function initUserId(string $user_name): void
    {
        $query = urlencode(
            Json\encode(['username' => $user_name])
        );

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', "users/?query=$query")
        );
        $user     = Json\decode($response->getBody()->getContents());

        $this->addUserIdFromRequestData($user[0]);
    }

    private function initUserIds(): void
    {
        $this->initUserId(BaseTestDataBuilder::ADMIN_USER_NAME);
        $this->initUserId(BaseTestDataBuilder::TEST_USER_1_NAME);
        $this->initUserId(BaseTestDataBuilder::TEST_USER_2_NAME);
        $this->initUserId(BaseTestDataBuilder::TEST_USER_3_NAME);
        $this->initUserId(RESTTestDataBuilder::TEST_USER_4_NAME);
        $this->initUserId(BaseTestDataBuilder::TEST_USER_5_NAME);
        $this->initUserId(BaseTestDataBuilder::TEST_USER_RESTRICTED_1_NAME);
        $this->initUserId(BaseTestDataBuilder::TEST_USER_RESTRICTED_2_NAME);
    }

    /** @param array{id: int, username: string} $user */
    private function addUserIdFromRequestData(array $user): void
    {
        $this->user_ids[$user['username']] = $user['id'];
        $this->cache->setUserId($user);
    }
}
