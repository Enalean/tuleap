<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\FRS\REST\v1;

use ForgeConfig;
use FRSFile;
use FRSFileDao;
use FRSFileFactory;
use FRSLogDao;
use FRSReleaseFactory;
use Luracast\Restler\RestException;
use PFUser;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\Upload\EmptyFileToUploadFinisher;
use Tuleap\FRS\Upload\FileOngoingUploadDao;
use Tuleap\FRS\Upload\FileToUploadCreator;
use Tuleap\FRS\Upload\Tus\FileUploadFinisher;
use Tuleap\FRS\Upload\Tus\ToBeCreatedFRSFileBuilder;
use Tuleap\FRS\Upload\UploadPathAllocator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
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
     * @url    GET {id}
     * @access hybrid
     *
     * @param int $id ID of the file
     *
     *
     * @throws RestException 403
     */
    public function getId(int $id): FileRepresentation
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
    public function optionsId(int $id): void
    {
        $this->sendAllowOptionsForFile();
    }

    private function sendAllowOptionsForFile(): void
    {
        Header::allowOptionsGetPostDelete();
    }

    /**
     * Delete file
     *
     * Delete file from FRS
     *
     * @url    DELETE {id}
     *
     * @param int $id The id of the file
     *
     * @status 202
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function deleteId(int $id): void
    {
        $this->sendAllowOptionsForFile();

        $user = $this->user_manager->getCurrentUser();
        $file = $this->getFile($id, $user);

        $frs_permission_manager = FRSPermissionManager::build();
        $project                = $file->getGroup();
        if (! $frs_permission_manager->isAdmin($project, $user)) {
            throw new RestException(403);
        }

        if (! $this->file_factory->delete_file($project->getID(), $id)) {
            throw new I18NRestException(500, dgettext("tuleap-frs", "An error occurred while deleting the file"));
        }
    }

    /**
     * @throws RestException 403
     */
    private function getFile($id, PFUser $user): FRSFile
    {
        $file = $this->file_factory->getFRSFileFromDb($id);
        if (! $file || $file->isDeleted()) {
            throw new RestException(404);
        }

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

    /**
     * Create file
     *
     * Create a file in the release.
     *
     * <br>
     * <pre>
     * {<br>
     * &nbsp;  "release_id": int,<br>
     * &nbsp;  "name": "string",<br>
     * &nbsp;  "file_size": int<br>
     * }<br>
     * </pre>
     *
     * You will get an URL where the file needs to be uploaded using the
     * <a href="https://tus.io/protocols/resumable-upload.html">tus resumable upload protocol</a>
     * to validate the item creation. You will need to use the same authentication mechanism you used
     * to call this endpoint.
     * Note: If the file is empty, then no URL will be returned.
     *
     *
     *
     * @status 201
     * @throws RestException 400
     * @throws RestException 403
     */
    protected function post(FilePOSTRepresentation $file_post_representation): CreatedFileRepresentation
    {
        $this->checkAccess();
        $this->sendAllowOptionsForFile();

        $user = $this->user_manager->getCurrentUser();

        $release = $this->release_factory->getFRSReleaseFromDb($file_post_representation->release_id);
        if (! $release) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-frs', 'The parent release cannot be found.')
            );
        }

        $frs_permission_manager = FRSPermissionManager::build();

        $project = $release->getProject();
        if (! $frs_permission_manager->isAdmin($project, $user)) {
            throw new RestException(403);
        }

        $file_ongoing_upload_dao = new FileOngoingUploadDao();

        $logger                = \BackendLogger::getDefaultLogger();
        $upload_path_allocator = new UploadPathAllocator();
        $file_item_creator     = new FileCreator(
            new FileToUploadCreator(
                $this->file_factory,
                $file_ongoing_upload_dao,
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                (int) ForgeConfig::get('sys_max_size_upload')
            ),
            new EmptyFileToUploadFinisher(
                new FileUploadFinisher(
                    $logger,
                    $upload_path_allocator,
                    new FRSFileFactory($logger),
                    new FRSReleaseFactory(),
                    $file_ongoing_upload_dao,
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                    new FRSFileDao(),
                    new FRSLogDao(),
                    new ToBeCreatedFRSFileBuilder()
                ),
                $upload_path_allocator
            )
        );

        return $file_item_creator->create(
            $release,
            $user,
            $file_post_representation,
            new \DateTimeImmutable()
        );
    }
}
