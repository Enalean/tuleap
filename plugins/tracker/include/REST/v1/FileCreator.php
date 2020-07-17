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

namespace Tuleap\Tracker\REST\v1;

use DateTimeImmutable;
use Luracast\Restler\RestException;
use PFUser;
use Tuleap\Tracker\FormElement\Field\File\Upload\EmptyFileToUploadFinisher;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileToUploadCreator;
use Tuleap\Tracker\FormElement\Field\File\Upload\UploadCreationConflictException;
use Tuleap\Tracker\FormElement\Field\File\Upload\UploadCreationFileMismatchException;
use Tuleap\Tracker\FormElement\Field\File\Upload\UploadMaxSizeExceededException;

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

    public function create(
        \Tracker_FormElement_Field_File $field,
        PFUser $user,
        FilePOSTRepresentation $file_post_representation,
        DateTimeImmutable $current_time
    ): CreatedFileRepresentation {
        try {
            $file_to_upload = $this->file_to_upload_creator->create(
                $field,
                $user,
                $current_time,
                $file_post_representation->name,
                $file_post_representation->file_size,
                $file_post_representation->file_type
            );

            if ($file_post_representation->file_size === 0) {
                $this->empty_file_to_upload_finisher->createEmptyFile($file_to_upload, $file_post_representation->name);
            }
            $representation = new CreatedFileRepresentation($file_to_upload, $file_post_representation->file_size);
        } catch (UploadCreationConflictException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (UploadCreationFileMismatchException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (UploadMaxSizeExceededException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        return $representation;
    }
}
