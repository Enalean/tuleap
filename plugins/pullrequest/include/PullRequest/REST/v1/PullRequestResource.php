<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\Dao;
use Tuleap\PullRequest\PullRequestNotFoundException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use GitRepositoryFactory;
use GitDao;
use ProjectManager;
use UserManager;
use URLVerification;
use Tuleap\REST\ProjectAuthorization;

class PullRequestResource extends AuthenticatedResource {

    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;

    /**
     * @var PullRequest\Factory
     */
    private $pull_request_factory;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct() {
        $this->git_repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );

        $dao                        = new Dao();
        $this->pull_request_factory = new Factory($dao);

        $this->user_manager = UserManager::instance();
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        return Header::allowOptionsGet();
    }

    /**
     * Get PullRequest
     *
     * Retrieve a given pullrequest. <br/>
     * User is not able to see a pullrequest in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}
     * @access protected
     *
     * @param int $id PullRequest ID
     *
     * @return array {@type PullRequest\REST\v1\PullRequestRepresentation}
     *
     * @throws 403
     * @throws 404
     */
    protected function get($id) {
        try {
            $pull_request = $this->pull_request_factory->getPullRequestById($id);
        } catch (PullRequestNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }

        $git_repository = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());

        if (! $git_repository) {
            throw new RestException(404, "The git repository where the pull request was generated does not exist");
        }

        $user    = $this->user_manager->getCurrentUser();
        $project = $git_repository->getProject();

        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());

        if (! $git_repository->userCanRead($user)) {
            throw new RestException(403, 'User is not able to READ the git repository');
        }

        $pull_request_representation = new PullRequestRepresentation();
        $pull_request_representation->build($pull_request);

        return $pull_request_representation;
    }
}
