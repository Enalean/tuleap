<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\REST\v1;

use Luracast\Restler\RestException;
use ProjectDao;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Registration\AnonymousNotAllowedException;
use Tuleap\Project\Registration\LimitedToSiteAdministratorsException;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\RestrictedUsersNotAllowedException;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use UserManager;

class ProjectFieldsResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 100;

    /**
     * @var DescriptionFieldsFactory
     */
    private $description_fields_factory;
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    public function __construct()
    {
        $this->description_fields_factory = new DescriptionFieldsFactory(new DescriptionFieldsDao());
        $this->permission_checker         = new ProjectRegistrationUserPermissionChecker(new ProjectDao());
        $this->user_manager               = UserManager::instance();
    }

    /**
     * Retrieve fields for project creation
     *
     * This route return the fields which are available or mandatory on project creation
     *
     * @url GET
     * @access protected
     *
     * @param int $limit Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return PaginatedProjectFieldRepresentations
     * @throws RestException
     */
    public function get(int $limit = 10, int $offset = 0)
    {
        $user = $this->user_manager->getCurrentUser();

        if (! $user) {
            throw new RestException(404, "Current user not found.");
        }

        try {
            $this->permission_checker->checkUserHasThePermissionToCreateProject($user);
        } catch (LimitedToSiteAdministratorsException $exception) {
            throw new RestException(403, "You don't have the permission to create project");
        } catch (AnonymousNotAllowedException $exception) {
            throw new RestException(403, "Anonymous doesn't have the permission to create project");
        } catch (RestrictedUsersNotAllowedException $exception) {
            throw new RestException(403, "Restricted users doesn't have the permission to create project");
        }

        $project_field_representations = [];
        $description_fields_infos      = $this->description_fields_factory->getPaginatedDescriptionFields(
            $limit,
            $offset
        );

        foreach ($description_fields_infos as $field) {
            $project_field_representations[] = new ProjectFieldRepresentation($field);
        }

        $paginated_project_fields = new PaginatedProjectFieldRepresentations($project_field_representations);

        Header::sendPaginationHeaders($limit, $offset, $paginated_project_fields->total_size, self::MAX_LIMIT);

        return $paginated_project_fields;
    }
}
