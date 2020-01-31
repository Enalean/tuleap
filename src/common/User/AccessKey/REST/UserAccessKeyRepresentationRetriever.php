<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey\REST;

use Tuleap\REST\AccessKeyHeaderExtractor;
use Tuleap\User\AccessKey\AccessKeyMetadataRetriever;

class UserAccessKeyRepresentationRetriever
{
    /**
     * @var AccessKeyHeaderExtractor
     */
    private $access_key_header_extractor;
    /**
     * @var AccessKeyMetadataRetriever
     */
    private $access_key_metadata_retriever;

    public function __construct(
        AccessKeyHeaderExtractor $access_key_header_extractor,
        AccessKeyMetadataRetriever $access_key_metadata_retriever
    ) {
        $this->access_key_header_extractor   = $access_key_header_extractor;
        $this->access_key_metadata_retriever = $access_key_metadata_retriever;
    }

    public function getByUserAndID(\PFUser $user, string $id): ?UserAccessKeyRepresentation
    {
        $key_id = null;
        if ($id === 'self') {
            $key_id = $this->extractCurrentKeyID();
        } elseif (ctype_digit($id)) {
            $key_id = (int) $id;
        }

        if ($key_id === null) {
            return null;
        }

        $all_access_key_medatada = $this->access_key_metadata_retriever->getMetadataByUser($user);

        foreach ($all_access_key_medatada as $access_key_metadata) {
            if ($access_key_metadata->getID() === $key_id) {
                $representation = new UserAccessKeyRepresentation();
                $representation->build($access_key_metadata);

                return $representation;
            }
        }

        return null;
    }

    private function extractCurrentKeyID(): ?int
    {
        $access_key = $this->access_key_header_extractor->extractAccessKey();

        if ($access_key === null) {
            return null;
        }

        return $access_key->getID();
    }
}
