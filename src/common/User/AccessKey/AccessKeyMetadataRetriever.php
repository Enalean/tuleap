<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use DateTimeImmutable;

class AccessKeyMetadataRetriever
{
    /**
     * @var AccessKeyDAO
     */
    private $dao;

    public function __construct(AccessKeyDAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return AccessKeyMetadata[]
     */
    public function getMetadataByUser(\PFUser $user)
    {
        $user_id      = (int) $user->getId();
        $current_time = (new DateTimeImmutable())->getTimestamp();

        $all_metadata = [];
        foreach ($this->dao->searchMetadataByUserIDAtCurrentTime($user_id, $current_time) as $metadata) {
            $all_metadata[] = new AccessKeyMetadata(
                $metadata['id'],
                (new DateTimeImmutable())->setTimestamp($metadata['creation_date']),
                $metadata['description'],
                $metadata['last_usage'] === null ? null : (new DateTimeImmutable())->setTimestamp($metadata['last_usage']),
                $metadata['last_ip'],
                $metadata['expiration_date'] === null ? null : (new DateTimeImmutable())->setTimestamp($metadata['expiration_date'])
            );
        }
        return $all_metadata;
    }
}
