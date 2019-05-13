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

namespace Tuleap\Tracker\FormElement\Field\File\Upload;

use DateInterval;
use DateTimeImmutable;
use LogicException;
use PFUser;
use Tracker_FormElement_Field_File;
use Tuleap\DB\DBTransactionExecutor;

final class FileToUploadCreator
{
    private const EXPIRATION_DELAY_IN_HOURS = 4;

    /**
     * @var FileOngoingUploadDao
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var int
     */
    private $max_size_upload;

    public function __construct(
        FileOngoingUploadDao $dao,
        DBTransactionExecutor $transaction_executor,
        int $max_size_upload
    ) {
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
        $this->max_size_upload      = $max_size_upload;
    }

    public function create(
        Tracker_FormElement_Field_File $field,
        PFUser $user,
        DateTimeImmutable $current_time,
        string $filename,
        int $filesize,
        string $filetype
    ): FileToUpload {
        if ($filesize > $this->max_size_upload) {
            throw new UploadMaxSizeExceededException($filesize, $this->max_size_upload);
        }

        $this->transaction_executor->execute(
            function () use (
                $field,
                $user,
                $current_time,
                $filename,
                $filesize,
                $filetype,
                &$id
            ) {
                $id = $this->searchFileOngoingUpload($field, $filename, $current_time, $user, $filesize);
                if ($id) {
                    return;
                }

                $id = $this->dao->saveFileOngoingUpload(
                    $this->getExpirationDate($current_time)->getTimestamp(),
                    (int) $field->getId(),
                    $filename,
                    $filesize,
                    $filetype,
                    (int) $user->getId()
                );
            }
        );

        return new FileToUpload($id, $filename);
    }

    private function searchFileOngoingUpload(
        Tracker_FormElement_Field_File $field,
        string $name,
        DateTimeImmutable $current_time,
        PFUser $user,
        int $file_size
    ): ?int {
        $rows = $this->dao->searchFileOngoingUploadByFieldIdNameAndExpirationDate(
            (int) $field->getId(),
            $name,
            $current_time->getTimestamp()
        );
        if (count($rows) > 1) {
            throw new LogicException(
                'A identical file is being created multiple times by an ongoing upload, this is not expected'
            );
        }
        if (count($rows) === 1) {
            $row = $rows[0];
            if ((int) $row['submitted_by'] !== (int) $user->getId()) {
                throw new UploadCreationConflictException();
            }
            if ($file_size !== (int) $row['filesize']) {
                throw new UploadCreationFileMismatchException();
            }

            return (int) $row['id'];
        }

        return null;
    }

    private function getExpirationDate(DateTimeImmutable $current_time): DateTimeImmutable
    {
        return $current_time->add(new DateInterval('PT' . self::EXPIRATION_DELAY_IN_HOURS . 'H'));
    }
}
