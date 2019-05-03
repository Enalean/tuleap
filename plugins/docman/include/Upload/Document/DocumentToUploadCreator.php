<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Document;

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;

class DocumentToUploadCreator
{
    public const EXPIRATION_DELAY_IN_HOURS = 12;

    /**
     * @var DocumentOngoingUploadDAO
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(DocumentOngoingUploadDAO $dao, DBTransactionExecutor $transaction_executor)
    {
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
    }

    public function create(
        \Docman_Item $parent_item,
        \PFUser $user,
        \DateTimeImmutable $current_time,
        $title,
        $description,
        $filename,
        $filesize,
        int $status,
        int $obsolescence_date
    ) {
        if ((int) $filesize > (int) \ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING)) {
            throw new UploadMaxSizeExceededException(
                (int) $filesize,
                (int) \ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING)
            );
        }

        $this->transaction_executor->execute(function () use (
            $parent_item,
            $user,
            $current_time,
            $title,
            $description,
            $filename,
            $filesize,
            &$item_id,
            $status,
            $obsolescence_date
        ) {
            $rows = $this->dao->searchDocumentOngoingUploadByParentIDTitleAndExpirationDate(
                $parent_item->getId(),
                $title,
                $current_time->getTimestamp()
            );
            if (count($rows) > 1) {
                throw new \LogicException(
                    'A identical document is being created multiple times by an ongoing upload, this is not expected'
                );
            }
            if (count($rows) === 1) {
                $row = $rows[0];
                if ($row['user_id'] !== (int) $user->getId()) {
                    throw new UploadCreationConflictException();
                }
                if ($row['filename'] !== $filename || (int) $filesize !== $row['filesize']) {
                    throw new UploadCreationFileMismatchException();
                }
                $item_id = $row['item_id'];
                return;
            }

            $item_id = $this->dao->saveDocumentOngoingUpload(
                $this->getExpirationDate($current_time)->getTimestamp(),
                $parent_item->getId(),
                $title,
                $description,
                $user->getId(),
                $filename,
                $filesize,
                $status,
                $obsolescence_date
            );
        });

        return new DocumentToUpload($item_id);
    }

    /**
     * @return \DateTimeImmutable
     */
    private function getExpirationDate(\DateTimeImmutable $current_time)
    {
        return $current_time->add(new \DateInterval('PT' . self::EXPIRATION_DELAY_IN_HOURS . 'H'));
    }
}
