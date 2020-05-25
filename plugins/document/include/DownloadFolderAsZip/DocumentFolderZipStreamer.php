<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Document\DownloadFolderAsZip;

use Docman_PermissionsManager;
use HTTPRequest;
use Project;
use Tuleap\Document\Tree\DocumentTreeProjectExtractor;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use ZipStream\Exception\OverflowException;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

final class DocumentFolderZipStreamer implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var DocumentTreeProjectExtractor
     */
    private $project_extractor;
    /**
     * @var ZipStreamerLoggingHelper
     */
    private $error_logging_helper;
    /**
     * @var ZipStreamMailNotificationSender
     */
    private $notification_sender;

    public function __construct(
        DocumentTreeProjectExtractor $project_extractor,
        ZipStreamerLoggingHelper $error_logging_helper,
        ZipStreamMailNotificationSender $notification_sender
    ) {
        $this->project_extractor    = $project_extractor;
        $this->error_logging_helper = $error_logging_helper;
        $this->notification_sender = $notification_sender;
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);
        $user    = $request->getCurrentUser();
        $folder  = $this->getFolder($user, $project, $variables);

        $this->streamFolder($folder, $project, $user);
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    private function getFolder(
        \PFUser $user,
        Project $project,
        array $variables
    ): \Docman_Folder {
        if (! isset($variables['folder_id'])) {
            throw new NotFoundException('folder_id is missing in the route params.');
        }

        $folder_id      = (int) $variables['folder_id'];
        $folder_factory = \Docman_FolderFactory::instance($project->getID());
        $folder         = $folder_factory->getItemFromDb($folder_id);

        if (! Docman_PermissionsManager::instance($project->getID())->userCanAccess($user, $folder_id)) {
            throw new ForbiddenException('You are not allowed to download this folder');
        }

        assert($folder instanceof \Docman_Folder);

        return $folder;
    }

    /**
     * @throws NotFoundException
     */
    public function getProject(array $variables): Project
    {
        return $this->project_extractor->getProject($variables);
    }

    private function streamFolder(\Docman_Folder $folder, Project $project, \PFUser $user): void
    {
        $options = new Archive();
        $options->setSendHttpHeaders(true);
        $options->setStatFiles(true);

        $zip = new ZipStream($folder->getTitle() . '.zip', $options);

        $factory = \Docman_FolderFactory::instance($project->getID());
        $factory->getItemTree($folder, $user, false, true);
        $errors_listing_builder = new ErrorsListingBuilder();

        $folder->accept(
            new ZipStreamFolderFilesVisitor($zip, $this->error_logging_helper, $errors_listing_builder),
            ['path' => '', 'base_folder_id' => $folder->getId()]
        );

        $errors_listing_builder->addErrorsFileIfAnyToArchive($zip);
        try {
            $zip->finish();
        } catch (OverflowException $e) {
            $this->error_logging_helper->logOverflowExceptionError($folder);
        }

        if ($errors_listing_builder->hasAnyError()) {
            $this->notification_sender->sendNotificationAboutErrorsInArchive(
                $user,
                $folder,
                $project
            );
        }
    }
}
