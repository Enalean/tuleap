<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Docman\REST\v1;

use Docman_Item;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use Luracast\Restler\RestException;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use URLVerification;
use UserManager;

class DocmanItemsRequestBuilder
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(UserManager $user_manager, ProjectManager $project_manager)
    {
        $this->user_manager    = $user_manager;
        $this->project_manager = $project_manager;
    }

    /**
     * @param int $id
     *
     * @return DocmanItemsRequest
     *
     * @throws I18NRestException
     * @throws RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function buildFromItemId($id)
    {
        $item_factory = Docman_ItemFactory::instance($id);

        $item = $item_factory->getItemFromDb($id);
        if ($item === null) {
            throw new I18NRestException(
                404,
                sprintf(
                    dgettext('tuleap-docman', 'The resource %d does not exist.'),
                    $id
                )
            );
        }

        $project = $this->project_manager->getProject($item->getGroupId());
        $user    = $this->user_manager->getCurrentUser();

        $this->checkProjectAccessibility($project, $user);
        $this->checkItemAccessibility($project, $user, $item);

        return new DocmanItemsRequest($item_factory, $item, $project, $user);
    }

    /**
     * @throws RestException
     */
    private function checkProjectAccessibility(Project $project, PFUser $user)
    {
        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt($user, $project);
    }

    /**
     * @throws RestException
     */
    private function checkItemAccessibility(Project $project, PFUser $user, Docman_Item $item)
    {
        $docman_permissions_manager = Docman_PermissionsManager::instance($project->getGroupId());
        if (! $docman_permissions_manager->userCanAccess($user, $item->getId())) {
            throw new I18NRestException(
                403,
                _('You are not allowed to access this resource')
            );
        }
    }
}
