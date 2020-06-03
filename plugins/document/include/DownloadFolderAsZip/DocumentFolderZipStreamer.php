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
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Project;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Document\Config\FileDownloadLimitsBuilder;
use Tuleap\Document\Tree\DocumentTreeProjectExtractor;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use ZipStream\Exception\OverflowException;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

final class DocumentFolderZipStreamer extends DispatchablePSR15Compatible implements DispatchableWithProject
{
    /**
     * @var DocumentTreeProjectExtractor
     */
    private $project_extractor;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var ZipStreamerLoggingHelper
     */
    private $error_logging_helper;
    /**
     * @var ZipStreamMailNotificationSender
     */
    private $notification_sender;
    /**
     * @var FolderSizeIsAllowedChecker
     */
    private $folder_size_is_allowed_checker;

    /**
     * @var FileDownloadLimitsBuilder
     */
    private $download_limits_builder;
    /**
     * @var BinaryFileResponseBuilder
     */
    private $binary_file_response_builder;

    public function __construct(
        BinaryFileResponseBuilder $binary_file_response_builder,
        DocumentTreeProjectExtractor $project_extractor,
        \UserManager $user_manager,
        ZipStreamerLoggingHelper $error_logging_helper,
        ZipStreamMailNotificationSender $notification_sender,
        FolderSizeIsAllowedChecker $folder_size_is_allowed_checker,
        FileDownloadLimitsBuilder $download_limits_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        $this->binary_file_response_builder   = $binary_file_response_builder;
        $this->user_manager                   = $user_manager;
        $this->project_extractor              = $project_extractor;
        $this->error_logging_helper           = $error_logging_helper;
        $this->notification_sender            = $notification_sender;
        $this->folder_size_is_allowed_checker = $folder_size_is_allowed_checker;
        $this->download_limits_builder        = $download_limits_builder;
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request_variables = $request->getAttributes();
        $project           = $this->getProject($request_variables);
        $user              = $this->user_manager->getCurrentUser();
        $folder            = $this->getFolder($user, $project, $request_variables);

        $factory = \Docman_FolderFactory::instance($project->getID());
        $factory->getItemTree($folder, $user, false, true);
        $this->checkFolderSizeIsAllowedForDownload($folder);

        return $this->binary_file_response_builder->fromCallback(
            $request,
            $this->buildStreamFolderArchiveCallback($folder, $project, $user),
            $folder->getTitle() . '.zip',
            'application/zip'
        );
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

    /**
     * @psalm-return callable():void
     */
    private function buildStreamFolderArchiveCallback(\Docman_Folder $folder, Project $project, \PFUser $user): callable
    {
        return function () use ($folder, $project, $user): void {
            $options = new Archive();
            $options->setStatFiles(true);
            $options->setLargeFileSize(0);
            $options->setZeroHeader(true);

            $zip = new ZipStream(null, $options);
            $errors_listing_builder = new ErrorsListingBuilder();

            ini_set('max_execution_time', '0');

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
        };
    }

    /**
     * @throws ForbiddenException
     */
    private function checkFolderSizeIsAllowedForDownload(\Docman_Folder $folder): void
    {
        $is_below_limit = $this->folder_size_is_allowed_checker->checkFolderSizeIsBelowLimit(
            $folder,
            $this->download_limits_builder->build()
        );
        if (! $is_below_limit) {
            throw new ForbiddenException('The size of the folder exceeds the maximum allowed download size.');
        }
    }
}
