<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\FRS\REST\v1;

use FRSFile;
use FRSFileFactory;
use FRSReleaseFactory;
use Luracast\Restler\RestException;
use PFUser;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectStatusVerificator;
use UserManager;

class FileResource extends AuthenticatedResource
{
    /**
     * @var FRSReleaseFactory
     */
    private $release_factory;
    /**
     * @var FRSFileFactory
     */
    private $file_factory;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->release_factory = FRSReleaseFactory::instance();
        $this->user_manager    = UserManager::instance();
        $this->file_factory    = new FRSFileFactory();
    }

    /**
     * Get file
     *
     * Get FRS file information
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id ID of the file
     *
     * @return \Tuleap\FRS\REST\v1\FileRepresentation
     *
     * @throws RestException 403
     */
    public function getId($id)
    {
        $this->sendAllowOptionsForFile();

        $user = $this->user_manager->getCurrentUser();
        $file = $this->getFile($id, $user);

        $file_representation = new FileRepresentation();
        $file_representation->build($file);

        return $file_representation;
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId($id)
    {
        $this->sendAllowOptionsForFile();
    }

    private function sendAllowOptionsForFile()
    {
        Header::allowOptionsGet();
    }

    /**
     * @return FRSFile
     * @throws RestException
     */
    private function getFile($id, PFUser $user)
    {
        $file = $this->file_factory->getFRSFileFromDb($id);
        if (! $file || $file->isDeleted()) {
            throw new RestException(404);
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $file->getGroup()
        );

        $is_user_able_to_read_file = $this->release_factory->userCanRead(
            $file->getGroup()->getID(),
            $file->getPackageID(),
            $file->getReleaseID(),
            $user->getId()
        );
        if (! $is_user_able_to_read_file) {
            throw new RestException(403);
        }

        return $file;
    }
}
