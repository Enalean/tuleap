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

namespace Tuleap\FRS\REST\v1;

use DateTimeImmutable;
use FRSRelease;
use Luracast\Restler\RestException;
use PFUser;
use Tuleap\FRS\Upload\EmptyFileToUploadFinisher;
use Tuleap\FRS\Upload\FileToUploadCreator;
use Tuleap\FRS\Upload\UploadCreationConflictException;
use Tuleap\FRS\Upload\UploadCreationFileMismatchException;
use Tuleap\FRS\Upload\UploadFileMarkedToBeRestoredException;
use Tuleap\FRS\Upload\UploadFileNameAlreadyExistsException;
use Tuleap\FRS\Upload\UploadIllegalNameException;
use Tuleap\FRS\Upload\UploadMaxSizeExceededException;
use Tuleap\REST\I18NRestException;

final class FileCreator
{
    /**
     * @var FileToUploadCreator
     */
    private $file_to_upload_creator;
    /**
     * @var EmptyFileToUploadFinisher
     */
    private $empty_file_to_upload_finisher;

    public function __construct(
        FileToUploadCreator $file_to_upload_creator,
        EmptyFileToUploadFinisher $empty_file_to_upload_finisher
    ) {
        $this->file_to_upload_creator = $file_to_upload_creator;
        $this->empty_file_to_upload_finisher = $empty_file_to_upload_finisher;
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    public function create(
        FRSRelease $release,
        PFUser $user,
        FilePOSTRepresentation $file_post_representation,
        DateTimeImmutable $current_time
    ): CreatedFileRepresentation {
        try {
            $file_to_upload = $this->file_to_upload_creator->create(
                $release,
                $user,
                $current_time,
                $file_post_representation->name,
                $file_post_representation->file_size
            );

            if ($file_post_representation->file_size === 0) {
                $this->empty_file_to_upload_finisher->createEmptyFile($file_to_upload, $file_post_representation->name);
                return new CreatedFileRepresentation();
            }
        } catch (UploadCreationConflictException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (UploadCreationFileMismatchException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (UploadMaxSizeExceededException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (UploadIllegalNameException $exception) {
            throw new I18NRestException(
                400,
                $GLOBALS['Language']->getText('file_admin_editreleases', 'illegal_file_name')
            );
        } catch (UploadFileNameAlreadyExistsException $exception) {
            throw new I18NRestException(
                400,
                $GLOBALS['Language']->getText('file_admin_editreleases', 'filename_exists')
            );
        } catch (UploadFileMarkedToBeRestoredException $exception) {
            throw new I18NRestException(
                400,
                $GLOBALS['Language']->getText('file_admin_editreleases', 'filename_to_be_restored')
            );
        }

        $representation = new CreatedFileRepresentation();
        $representation->build($file_to_upload->getUploadHref());

        return $representation;
    }
}
