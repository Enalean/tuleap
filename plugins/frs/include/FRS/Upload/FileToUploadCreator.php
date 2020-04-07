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

namespace Tuleap\FRS\Upload;

use DateInterval;
use DateTimeImmutable;
use FRSFileFactory;
use FRSRelease;
use LogicException;
use PFUser;
use Rule_FRSFileName;
use Tuleap\DB\DBTransactionExecutor;

final class FileToUploadCreator
{
    private const EXPIRATION_DELAY_IN_HOURS = 12;

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
    /**
     * @var FRSFileFactory
     */
    private $file_factory;

    public function __construct(
        FRSFileFactory $file_factory,
        FileOngoingUploadDao $dao,
        DBTransactionExecutor $transaction_executor,
        int $max_size_upload
    ) {
        $this->file_factory         = $file_factory;
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
        $this->max_size_upload      = $max_size_upload;
    }

    public function create(
        FRSRelease $release,
        PFUser $user,
        DateTimeImmutable $current_time,
        string $name,
        int $file_size
    ): FileToUpload {
        if ($file_size > $this->max_size_upload) {
            throw new UploadMaxSizeExceededException($file_size, $this->max_size_upload);
        }

        $this->transaction_executor->execute(
            function () use (
                $release,
                $user,
                $current_time,
                $name,
                $file_size,
                &$id
            ) {
                $this->checkFileCanBeCreated($release, $name);
                $id = $this->searchFileOngoingUpload($release, $name, $current_time, $user, $file_size);
                if ($id) {
                    return;
                }

                $id = $this->dao->saveFileOngoingUpload(
                    $this->getExpirationDate($current_time)->getTimestamp(),
                    $release->getReleaseID(),
                    $name,
                    $file_size,
                    (int) $user->getId()
                );
            }
        );

        return new FileToUpload($id);
    }

    private function searchFileOngoingUpload(
        FRSRelease $release,
        string $name,
        DateTimeImmutable $current_time,
        PFUser $user,
        int $file_size
    ): ?int {
        $rows = $this->dao->searchFileOngoingUploadByReleaseIDNameAndExpirationDate(
            $release->getReleaseID(),
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
            if ((int) $row['user_id'] !== (int) $user->getId()) {
                throw new UploadCreationConflictException();
            }
            if ($file_size !== (int) $row['file_size']) {
                throw new UploadCreationFileMismatchException();
            }

            return (int) $row['id'];
        }

        return null;
    }

    private function checkFileCanBeCreated(
        FRSRelease $release,
        string $name
    ): void {
        $rule = new Rule_FRSFileName();
        if (! $rule->isValid($name)) {
            throw new UploadIllegalNameException();
        }

        if (
            $this->file_factory->isFileBaseNameExists(
                $name,
                $release->getReleaseID(),
                $release->getGroupID()
            )
        ) {
            throw new UploadFileNameAlreadyExistsException();
        }

        if (
            $this->file_factory->isSameFileMarkedToBeRestored(
                $name,
                $release->getReleaseID()
            )
        ) {
            throw new UploadFileMarkedToBeRestoredException();
        }
    }

    private function getExpirationDate(DateTimeImmutable $current_time): DateTimeImmutable
    {
        return $current_time->add(new DateInterval('PT' . self::EXPIRATION_DELAY_IN_HOURS . 'H'));
    }
}
