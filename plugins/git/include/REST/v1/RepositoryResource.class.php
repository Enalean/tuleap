<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Git\REST\v1;

use GitRepositoryFactory;
use Tuleap\Git\REST\v1\GitRepositoryRepresentation;
use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use Tuleap\REST\v1\GitRepositoryRepresentationBase;
use Tuleap\REST\AuthenticatedResource;
use GitRepoNotReadableException;
use GitRepoNotFoundException;
use Exception;
use UserManager;
use GitPermissionsManager;
use Git_PermissionsDao;
use Git_SystemEventManager;
use SystemEventManager;
use EventManager;
use PFUser;
use GitRepository;

include_once('www/project/admin/permissions.php');

class RepositoryResource extends AuthenticatedResource {

    const MAX_LIMIT = 50;

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var RepositoryRepresentationBuilder */
    private $representation_builder;

    public function __construct() {
        $this->repository_factory = new GitRepositoryFactory(
            new \GitDao(),
            \ProjectManager::instance()
        );
        $this->representation_builder = new RepositoryRepresentationBuilder(
            new GitPermissionsManager(
                new Git_PermissionsDao(),
                new Git_SystemEventManager(
                    SystemEventManager::instance(),
                    $this->repository_factory
                )
            )
        );
    }

    /**
     * Return info about repository if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the repository
     *
     * @throws 403
     * @throws 404
     */
    public function optionsId($id) {
        $this->sendAllowHeaders();
    }

    /**
     * @access hybrid
     *
     * @param int $id Id of the repository
     * @return GitRepositoryRepresentation | null
     *
     * @throws 403
     * @throws 404
     */
    public function get($id) {
        $this->checkAccess();

        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $id);

        $this->sendAllowHeaders();

        return $this->representation_builder->build($user, $repository, GitRepositoryRepresentationBase::FIELDS_ALL);
    }

    /**
     * @url OPTIONS {id}/pull_requests
     *
     * @param int $id Id of the repository
     *
     * @throws 404
     */
    public function optionsPullRequests($id) {
        $this->checkPullRequestEndpointsAvailable();
        $this->sendAllowHeaders();
    }

    /**
     * Get git repository's pull requests
     *
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/pull_requests
     *
     * @access protected
     *
     * @param  int $id     Id of the repository
     * @param  int $limit  Number of elements displayed per page {@from path}
     * @param  int $offset Position of the first element to display {@from path}
     *
     * @return Tuleap\PullRequest\REST\v1\RepositoryPullRequestRepresentation
     *
     * @throws 403
     * @throws 404
     */
    public function getPullRequests($id, $limit = self::MAX_LIMIT, $offset = 0) {
        $this->checkAccess();
        $this->checkPullRequestEndpointsAvailable();
        $this->checkLimit($limit);

        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $id);
        $result     = $this->getPaginatedPullRequests($repository, $limit, $offset);

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, $result->total_size);

        return $result;
    }

    private function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    private function getRepository(PFUser $user, $id) {
        try {
            $repository = $this->repository_factory->getRepositoryByIdUserCanSee($user, $id);
        } catch (GitRepoNotReadableException $exception) {
            throw new RestException(403, 'Git repository not accessible for user');
        } catch (GitRepoNotFoundException $exception) {
            throw new RestException(404, 'Git repository not found');
        } catch (Exception $exception) {
            throw new RestException(403, 'Project not accessible for user');
        }

        return $repository;
    }

    private function getPaginatedPullRequests(GitRepository $repository, $limit, $offset) {
        $result = null;

        EventManager::instance()->processEvent(
            REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY,
            array(
                'version'    => 'v1',
                'repository' => $repository,
                'limit'      => $limit,
                'offset'     => $offset,
                'result'     => &$result
            )
        );

        return $result;
    }

    private function checkPullRequestEndpointsAvailable() {
        $available = false;

        EventManager::instance()->processEvent(
            REST_GIT_PULL_REQUEST_ENDPOINTS,
            array(
                'available' => &$available
            )
        );

        if ($available === false) {
            throw new RestException(404, 'PullRequest plugin not activated');
        }
    }

    private function sendAllowHeaders() {
        Header::allowOptionsGet();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function checkLimit($limit) {
        if ($limit > self::MAX_LIMIT) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }
}