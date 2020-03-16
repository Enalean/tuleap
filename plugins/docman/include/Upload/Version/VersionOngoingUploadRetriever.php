<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\Upload\Version;

use PFUser;

class VersionOngoingUploadRetriever
{
    /**
     * @var DocumentOnGoingVersionToUploadDAO
     */
    private $dao;

    public function __construct(DocumentOnGoingVersionToUploadDAO $dao)
    {
        $this->dao = $dao;
    }

    public function isThereAlreadyAnUploadOngoing(\Docman_Item $item, \DateTimeImmutable $current_time): bool
    {
        return ! empty($this->dao->searchDocumentVersionOngoingUploadByItemIdAndExpirationDate(
            $item->getId(),
            $current_time->getTimestamp()
        ));
    }

    public function isThereAlreadyAnUploadOngoingForOtherUser(\Docman_File $item, PFUser $user, \DateTimeImmutable $current_time): bool
    {
        return ! empty(
            $this->dao->searchDocumentVersionOngoingUploadForAnotherUserByItemIdAndExpirationDate(
                $item->getId(),
                (int) $user->getId(),
                $current_time->getTimestamp()
            )
        );
    }
}
