<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\REST\v1;

use DateTimeImmutable;
use LogicException;
use Luracast\Restler\RestException;
use Tuleap\Project\Registration\AnonymousNotAllowedException;
use Tuleap\Project\Registration\LimitedToSiteAdministratorsException;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForPlatformException;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForUserException;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\RestrictedUsersNotAllowedException;
use Tuleap\Project\Registration\Template\CustomProjectArchiveFeatureFlag;
use Tuleap\Project\Registration\Template\Upload\FileOngoingUploadDao;
use Tuleap\Project\Registration\Template\Upload\ProjectFileToUploadCreator;
use Tuleap\Project\REST\v1\Project\CreatedFileRepresentation;
use Tuleap\Project\REST\v1\Project\ProjectFilePOSTRepresentation;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use UserManager;

final class ProjectFileResource extends AuthenticatedResource
{
    public const  ROUTE = 'project_files';

    /**
     * @url OPTIONS
     *
     */
    public function optionsFiles(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Create a file from a project zip file
     *
     * Create a file from a project template which be used to create new project from it
     * <br/>
     * You will get an URL where the file needs to be uploaded using the tus resumable upload protocol to validate the item creation.
     * You will need to use the same authentication mechanism you used to call this endpoint.
     * <br/>
     * <strong> /!\ This route is under construction and subject to changes /!\ </strong>
     *
     * @url POST
     *
     * @access protected
     *
     * @status 201
     * @throws RestException 401
     * @throws RestException 400
     * @throws RestException 501
     */
    protected function post(ProjectFilePOSTRepresentation $file_post_representation): CreatedFileRepresentation
    {
        if (! CustomProjectArchiveFeatureFlag::canCreateFromCustomArchive()) {
            throw new LogicException('This route not should be called because the feature flag is disabled');
        }

        $user_registration_checker = new ProjectRegistrationUserPermissionChecker(new \ProjectDao());

        $this->checkAccess();
        $current_user = UserManager::instance()->getCurrentUser();
        try {
            $user_registration_checker->checkUserCreateAProject(
                $current_user
            );
        } catch (MaxNumberOfProjectReachedForPlatformException | MaxNumberOfProjectReachedForUserException | LimitedToSiteAdministratorsException | AnonymousNotAllowedException | RestrictedUsersNotAllowedException $e) {
            throw new RestException(403, $e->getMessage());
        }


        $this->optionsFiles();
        $file_ongoing_upload_dao = new FileOngoingUploadDao();
        $file_creator            =
            new ProjectFileToUploadCreator(
                $file_ongoing_upload_dao,
            );

        $file_creator = $file_creator->creatFileToUpload(
            $file_post_representation,
            $current_user,
            new DateTimeImmutable()
        );

        return new CreatedFileRepresentation($file_creator->getUploadHref());
    }
}
