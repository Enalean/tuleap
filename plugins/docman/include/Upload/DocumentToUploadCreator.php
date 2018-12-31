<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

class DocumentToUploadCreator
{
    const EXPIRATION_DELAY_IN_HOURS = 12;

    /**
     * @var DocumentOngoingUploadDAO
     */
    private $dao;

    public function __construct(DocumentOngoingUploadDAO $dao)
    {
        $this->dao = $dao;
    }

    public function create(
        \Docman_Item $parent_item,
        \PFUser $user,
        \DateTimeImmutable $current_time,
        $title,
        $description,
        $filename,
        $filesize
    ) {
        if ((int) $filesize > (int) \ForgeConfig::get('sys_max_size_upload')) {
            throw new DocumentToUploadMaxSizeExceededException($filesize);
        }

        $this->dao->wrapAtomicOperations(function (DocumentOngoingUploadDAO $dao) use (
            $parent_item,
            $user,
            $current_time,
            $title,
            $description,
            $filename,
            $filesize,
            &$item_id
        ) {
            $rows = $dao->searchDocumentOngoingUploadByParentIDTitleAndExpirationDate(
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
                    throw new DocumentToUploadCreationConflictException();
                }
                if ($row['filename'] !== $filename || (int) $filesize !== $row['filesize']) {
                    throw new DocumentToUploadCreationFileMismatchException();
                }
                $item_id = $row['item_id'];
                return;
            }

            $item_id = $dao->saveDocumentOngoingUpload(
                $this->getExpirationDate($current_time)->getTimestamp(),
                $parent_item->getId(),
                $title,
                $description,
                $user->getId(),
                $filename,
                $filesize
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
