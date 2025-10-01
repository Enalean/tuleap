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

namespace Tuleap\Tracker\FormElement\Field\Files\Upload;

use DateInterval;
use DateTimeImmutable;
use PFUser;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\FormElement\Field\Files\FilesField;

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
        int $max_size_upload,
    ) {
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
        $this->max_size_upload      = $max_size_upload;
    }

    public function create(
        FilesField $field,
        PFUser $user,
        DateTimeImmutable $current_time,
        string $filename,
        int $filesize,
        string $filetype,
        string $description,
    ): FileToUpload {
        if ($filesize > $this->max_size_upload) {
            throw new UploadMaxSizeExceededException($filesize, $this->max_size_upload);
        }

        $new_upload = NewFileUpload::fromComponents(
            $field,
            $filename,
            $filesize,
            $filetype,
            $description,
            $user,
            $this->getExpirationDate($current_time)
        );

        $this->transaction_executor->execute(
            function () use ($new_upload, $current_time, &$id) {
                $id = $this->searchFileOngoingUpload($new_upload, $current_time);
                if ($id) {
                    return;
                }

                $id = $this->dao->saveFileOngoingUpload($new_upload);
            }
        );

        return new FileToUpload($id, $filename);
    }

    private function searchFileOngoingUpload(NewFileUpload $new_upload, \DateTimeImmutable $current_time): ?int
    {
        $rows = $this->dao->searchFileOngoingUploadByFieldIdNameAndExpirationDate(
            $new_upload->file_field_id,
            $new_upload->file_name,
            $current_time->getTimestamp()
        );

        foreach ($rows as $row) {
            if ((int) $row['submitted_by'] !== $new_upload->uploading_user_id && $new_upload->file_size === (int) $row['filesize']) {
                throw new UploadCreationConflictException();
            }

            if ($new_upload->file_size === (int) $row['filesize']) {
                return (int) $row['id'];
            }
        }

        return null;
    }

    private function getExpirationDate(DateTimeImmutable $current_time): DateTimeImmutable
    {
        return $current_time->add(new DateInterval('PT' . self::EXPIRATION_DELAY_IN_HOURS . 'H'));
    }
}
