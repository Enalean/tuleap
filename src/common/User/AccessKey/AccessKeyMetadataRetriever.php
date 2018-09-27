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

namespace Tuleap\User\AccessKey;

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
        $all_metadata = [];
        foreach ($this->dao->searchMetadataByUserID($user->getId()) as $metadata) {
            $all_metadata[] = new AccessKeyMetadata(
                new \DateTimeImmutable('@' . $metadata['creation_date']),
                $metadata['description'],
                $metadata['last_usage'] === null ? null : new \DateTimeImmutable('@' . $metadata['last_usage']),
                $metadata['last_ip']
            );
        }
        return $all_metadata;
    }
}
