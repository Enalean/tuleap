<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\REST\v1;

use Git_PermissionsDao;
use Git_RemoteServer_Dao;
use Git_RemoteServer_GerritServerFactory;
use Git_SystemEventManager;
use GitPermissionsManager;
use GitRepository;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use SystemEventManager;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Exceptions\InvalidJsonException;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\MissingMandatoryParameterException;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use URLVerification;

final class GitProjectResource extends AuthenticatedResource
{
    private const MAX_LIMIT              = 50;
    private const PROJECT                = 'project';
    private const INDIVIDUAL             = 'individual';
    private const SCOPES_REPRESENTATIONS = [
        self::PROJECT    => GitRepository::REPO_SCOPE_PROJECT,
        self::INDIVIDUAL => GitRepository::REPO_SCOPE_INDIVIDUAL
    ];

    /**
     * @url    OPTIONS {id}/git
     *
     * @param int $id Id of the project
     */
    public function optionsGit(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get git
     *
     * Get info about project Git repositories. Repositories are returned ordered by last_push date, if there are no push
     * yet, it's the creation date of the repository that is taken into account.
     * <br>
     * The total number of repositories returned by 'x-pagination-size' header corresponds to ALL repositories, including
     * those you cannot view so you might retrieve a lower number of repositories than 'x-pagination-size'.
     * <br>
     * <br>
     * With fields = 'basic', permissions is always set as <strong>NULL</strong>
     * <br>
     * <br>
     * Basic example:
     * <br>
     * <br>
     * <pre>
     * "repositories": [{<br>
     *   &nbsp;"id" : 90,<br>
     *   &nbsp;"uri": "git/90",<br>
     *   &nbsp;"name": "repo",<br>
     *   &nbsp;"path": "project/repo.git",<br>
     *   &nbsp;"description": "-- Default description --",<br>
     *   &nbsp;"permissions": null<br>
     *  }<br>
     * ...<br>
     * ]
     * </pre>
     * <br>
     *
     * <br>
     * All example:
     * <br>
     * <br>
     * <pre>
     * "repositories": [{<br>
     *   &nbsp;"id" : 90,<br>
     *   &nbsp;"uri": "git/90",<br>
     *   &nbsp;"name": "repo",<br>
     *   &nbsp;"path": "project/repo.git",<br>
     *   &nbsp;"description": "-- Default description --",<br>
     *   &nbsp;"permissions": {<br>
     *   &nbsp;   "read": [<br>
     *   &nbsp;     &nbsp;{<br>
     *   &nbsp;     &nbsp;  "id": "116_2",<br>
     *   &nbsp;     &nbsp;  "uri": "user_groups/116_2",<br>
     *   &nbsp;     &nbsp;  "label": "registered_users",<br>
     *   &nbsp;     &nbsp;  "users_uri": "user_groups/116_2/users"<br>
     *   &nbsp;     &nbsp;}<br>
     *   &nbsp;   ],<br>
     *   &nbsp;   "write": [<br>
     *   &nbsp;     &nbsp;{<br>
     *   &nbsp;     &nbsp;  "id": "116_3",<br>
     *   &nbsp;     &nbsp;  "uri": "user_groups/116_3",<br>
     *   &nbsp;     &nbsp;  "label": "project_members",<br>
     *   &nbsp;     &nbsp;  "users_uri": "user_groups/116_3/users"<br>
     *   &nbsp;     &nbsp;}<br>
     *   &nbsp;   ]<br>
     *   &nbsp;   "rewind": [<br>
     *   &nbsp;     &nbsp;{<br>
     *   &nbsp;     &nbsp;  "id": "116_122",<br>
     *   &nbsp;     &nbsp;  "uri": "user_groups/116_122",<br>
     *   &nbsp;     &nbsp;  "label": "admins",<br>
     *   &nbsp;     &nbsp;  "users_uri": "user_groups/116_122/users"<br>
     *   &nbsp;     &nbsp;}<br>
     *   &nbsp;   ],<br>
     *   &nbsp;}<br>
     *  }<br>
     * ...<br>
     * ]
     * </pre>
     * <br>
     * You can use <code>query</code> parameter in order to filter results. Currently you can only filter on scope or
     * owner_id. By default, all repositories are returned.
     * <br>
     * { "scope": "project" } will return only project repositories.
     * <br>
     * { "scope": "individual" } will return only forked repositories.
     * <br>
     * { "owner_id": 123 } will return all repositories created by user with id 123.
     * <br>
     * { "scope": "individual", "owner_id": 123 } will return all repositories forked by user with id 123.
     *
     * @url    GET {id}/git
     * @access hybrid
     *
     * @param int    $id       Id of the project
     * @param int    $limit    Number of elements displayed per page {@from path}
     * @param int    $offset   Position of the first element to display {@from path}
     * @param string $fields   Whether you want to fetch permissions or just repository info {@from path}{@choice basic,all}
     * @param string $query    Filter repositories {@from path}
     * @param string $order_by {@from path}{@choice push_date,path}
     *
     * @return GitRepositoryListRepresentation
     *
     * @throws RestException
     */
    public function getGit(
        int $id,
        int $limit = 10,
        int $offset = 0,
        string $fields = GitRepositoryRepresentation::FIELDS_BASIC,
        string $query = '',
        string $order_by = 'push_date'
    ) {
        $this->checkAccess();

        $user    = \UserManager::instance()->getCurrentUser();
        $project = $this->getProject($id, $user);

        $query_parameter_parser = new QueryParameterParser(new JsonDecoder());

        try {
            $scope    = $this->getScopeFromQueryParameter($query_parameter_parser, $query);
            $owner_id = $this->getOwnerIdFromQueryParameter($query_parameter_parser, $query);
        } catch (QueryParameterException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (InvalidJsonException $e) {
            throw new RestException(400, $e->getMessage());
        }

        $repository_factory        = new GitRepositoryFactory(new \GitDao(), ProjectManager::instance());
        $total_number_repositories = 0;
        $git_repositories   = $repository_factory->getPaginatedRepositoriesUserCanSee(
            $project,
            $user,
            $scope,
            $owner_id,
            $order_by,
            $limit,
            $offset,
            $total_number_repositories
        );

        $git_plugin                  = \PluginManager::instance()->getPluginByName('git');
        $git_system_event_manager    = new Git_SystemEventManager(SystemEventManager::instance(), $repository_factory);
        $fine_grained_dao            = new FineGrainedDao();
        $repository_resource_builder = new RepositoryRepresentationBuilder(
            new GitPermissionsManager(
                new Git_PermissionsDao(),
                new Git_SystemEventManager(SystemEventManager::instance(), $repository_factory),
                $fine_grained_dao,
                new FineGrainedRetriever($fine_grained_dao)
            ),
            new Git_RemoteServer_GerritServerFactory(
                new Git_RemoteServer_Dao(),
                new \GitDao(),
                $git_system_event_manager,
                ProjectManager::instance()
            ),
            new \Git_LogDao(),
            \EventManager::instance(),
            new \Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder())
        );
        $result = new GitRepositoryListRepresentation(
            $repository_resource_builder->buildWithList($user, $git_repositories, $fields)
        );

        $this->optionsGit($id);
        Header::sendPaginationHeaders($limit, $offset, $total_number_repositories, self::MAX_LIMIT);
        return $result;
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    private function getProject(int $id, PFUser $user): \Project
    {
        $project = \ProjectManager::instance()->getProject($id);
        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        return $project;
    }

    /**
     * @throws InvalidJsonException
     * @throws \Tuleap\REST\InvalidParameterTypeException
     * @throws RestException
     */
    private function getScopeFromQueryParameter(QueryParameterParser $query_parameter_parser, string $query): string
    {
        try {
            $requested_scope = $query_parameter_parser->getString($query, 'scope');
            if (! array_key_exists($requested_scope, self::SCOPES_REPRESENTATIONS)) {
                throw new RestException(400, 'Invalid value supplied for scope. Expected: "project" or "individual".');
            }
            return self::SCOPES_REPRESENTATIONS[$requested_scope];
        } catch (MissingMandatoryParameterException $e) {
            // no scope is provided, skip it
        }
        return '';
    }

    /**
     * @throws QueryParameterException
     * @throws InvalidJsonException
     */
    private function getOwnerIdFromQueryParameter(QueryParameterParser $query_parameter_parser, string $query): int
    {
        try {
            return $query_parameter_parser->getInt($query, 'owner_id');
        } catch (MissingMandatoryParameterException $e) {
            // no owner_id is provided, skip it
        }
        return 0;
    }
}
