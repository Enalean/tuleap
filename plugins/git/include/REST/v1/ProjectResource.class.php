<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

use GitRepository;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use PFUser;
use Project;
use Tuleap\REST\Exceptions\InvalidJsonException;
use Tuleap\REST\MissingMandatoryParameterException;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;

include_once('www/project/admin/permissions.php');

class ProjectResource
{
    const PROJECT                = 'project';
    const INDIVIDUAL             = 'individual';
    const SCOPES_REPRESENTATIONS = [
        self::PROJECT    => GitRepository::REPO_SCOPE_PROJECT,
        self::INDIVIDUAL => GitRepository::REPO_SCOPE_INDIVIDUAL
    ];

    /**
     * @var RepositoryRepresentationBuilder
     */
    private $repository_resource_builder;
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var QueryParameterParser
     */
    private $query_parameter_parser;

    public function __construct(
        GitRepositoryFactory $repository_factory,
        RepositoryRepresentationBuilder $repository_resource_builder,
        QueryParameterParser $query_parameter_parser
    ) {
        $this->repository_factory          = $repository_factory;
        $this->repository_resource_builder = $repository_resource_builder;
        $this->query_parameter_parser      = $query_parameter_parser;
    }

    public function getGit(Project $project, PFUser $user, $limit, $offset, $fields, $query)
    {
        $scope = '';
        try {
            $requested_scope = $this->query_parameter_parser->getString($query, 'scope');
            if (! in_array($requested_scope, array_keys(self::SCOPES_REPRESENTATIONS))) {
                throw new RestException(400, 'Invalid value supplied for scope. Expected: "project" or "individual".');
            }
            $scope = self::SCOPES_REPRESENTATIONS[$requested_scope];
        } catch (MissingMandatoryParameterException $e) {
            // no scope are provided, skip it
        } catch (QueryParameterException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (InvalidJsonException $e) {
            throw new RestException(400, $e->getMessage());
        }

        $results          = [];
        $git_repositories = $this->repository_factory->getPagninatedRepositoriesUserCanSee(
            $project,
            $user,
            $scope,
            $limit,
            $offset
        );

        foreach ($git_repositories as $repository) {
            $results[] = $this->repository_resource_builder->build($user, $repository, $fields);
        }

        return [
            'repositories' => $results
        ];
    }
}