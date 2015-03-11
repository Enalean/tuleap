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

include_once('www/project/admin/permissions.php');

class RepositoryResource extends AuthenticatedResource {

    /** @var GitRepositoryFactory */
    private $repository_factory;

    public function __construct() {
        $this->repository_factory = new GitRepositoryFactory(
            new \GitDao(),
            \ProjectManager::instance()
        );
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
        $this->checkAcess();
        $user = $this->getCurrentUser();

        try {
            $repository = $this->repository_factory->getRepositoryByIdUserCanSee($user, $id);
            $repo_representation = new GitRepositoryRepresentation();
            $repo_representation->build($repository, $user, GitRepositoryRepresentationBase::FIELDS_ALL);

            return $repo_representation;
        } catch (GitRepoNotReadableException $exception) {
            throw new RestException(403, 'Git repository not accessible for user');
        } catch (GitRepoNotFoundException $exception) {
            throw new RestException(404, 'Git repository not found');
        } catch (Exception $exception) {
            throw new RestException(403, 'Project not accessible for user');
        }

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
        Header::allowOptionsGet();
    }

    private function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }
}