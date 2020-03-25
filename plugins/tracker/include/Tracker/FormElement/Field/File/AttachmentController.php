<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File;

use PFUser;
use Project_AccessException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tracker_FileInfo;
use Tracker_FileInfo_InvalidFileInfoException;
use Tracker_FileInfo_UnauthorisedException;
use Tracker_FileInfoFactory;
use Tracker_FormElementFactory;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileBeingUploadedInformationProvider;
use URLVerification;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

class AttachmentController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var URLVerification
     */
    private $url_verification;
    /**
     * @var FileOngoingUploadDao
     */
    private $ongoing_upload_dao;
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var FileBeingUploadedInformationProvider
     */
    private $file_information_provider;
    /**
     * @var BinaryFileResponseBuilder
     */
    private $response_builder;
    /**
     * @var Tracker_FileInfoFactory
     */
    private $file_info_factory;

    public function __construct(
        URLVerification $url_verification,
        FileOngoingUploadDao $ongoing_upload_dao,
        Tracker_FormElementFactory $form_element_factory,
        FileBeingUploadedInformationProvider $file_information_provider,
        Tracker_FileInfoFactory $file_info_factory,
        BinaryFileResponseBuilder $response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->url_verification          = $url_verification;
        $this->ongoing_upload_dao        = $ongoing_upload_dao;
        $this->form_element_factory      = $form_element_factory;
        $this->file_info_factory         = $file_info_factory;
        $this->file_information_provider = $file_information_provider;
        $this->response_builder          = $response_builder;
    }

    /**
     * @throws NotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current_user = $request->getAttribute(RESTCurrentUserMiddleware::class);
        \assert($current_user instanceof PFUser);

        $fileinfo = $this->getAlreadyLinkedFileInfo($current_user, $request);
        if (! $fileinfo) {
            $fileinfo = $this->getReadyToBeLinkedUploadedFileInfo($request);
        }

        $field = $fileinfo->getField();

        $tracker = $field->getTracker();
        if (! $tracker) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        $project = $tracker->getProject();
        try {
            $this->url_verification->userCanAccessProject($current_user, $project);
        } catch (Project_AccessException $e) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        if (! $field->userCanRead($current_user)) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        $path = $fileinfo->getPath();
        if ($request->getAttribute('preview') !== null) {
            $path = $fileinfo->getThumbnailPath();
        }

        if (! $path || ! is_file($path)) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        return $this->response_builder->fromFilePath(
            $request,
            $path,
            $fileinfo->getFilename(),
            $fileinfo->getFiletype()
        );
    }

    private function getAlreadyLinkedFileInfo(PFUser $current_user, ServerRequestInterface $request): ?Tracker_FileInfo
    {
        $fileinfo = $this->file_info_factory->getById((int) $request->getAttribute('id'));
        if (! $fileinfo) {
            return null;
        }

        if ($fileinfo->getFilename() !== $request->getAttribute('filename')) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        try {
            $this->file_info_factory->getArtifactByFileInfoIdAndUser($current_user, (int) $fileinfo->getId());
        } catch (Tracker_FileInfo_InvalidFileInfoException | Tracker_FileInfo_UnauthorisedException $exception) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        return $fileinfo;
    }

    private function getReadyToBeLinkedUploadedFileInfo(ServerRequestInterface $request): Tracker_FileInfo
    {
        $file_information = $this->file_information_provider->getFileInformation($request);
        if (! $file_information) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        if ($file_information->getLength() !== $file_information->getOffset()) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        if ($file_information->getName() !== $request->getAttribute('filename')) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        $row = $this->ongoing_upload_dao->searchFileOngoingUploadById($file_information->getID());
        if (! $row) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        $field_id = (int) $row['field_id'];
        $field    = $this->form_element_factory->getUsedFormElementFieldById($field_id);
        if (! $field || ! $this->form_element_factory->isFieldAFileField($field)) {
            throw new NotFoundException(_('The file cannot be found'));
        }

        return new Tracker_FileInfo(
            $row['id'],
            $field,
            $row['submitted_by'],
            $row['description'],
            $row['filename'],
            $row['filesize'],
            $row['filetype']
        );
    }
}
