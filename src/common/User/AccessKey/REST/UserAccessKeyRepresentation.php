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

namespace Tuleap\User\AccessKey\REST;

use Tuleap\REST\JsonCast;
use Tuleap\User\AccessKey\AccessKeyMetadata;

class UserAccessKeyRepresentation
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $creation_date;
    /**
     * @var string
     */
    public $description;
    /**
     * @var int|null
     */
    public $last_used_on;
    /**
     * @var string|null
     */
    public $last_used_by;

    public function build(AccessKeyMetadata $access_key_metadata)
    {
        $this->id            = $access_key_metadata->getID();
        $this->creation_date = JsonCast::toDate($access_key_metadata->getCreationDate()->getTimestamp());
        $this->description   = $access_key_metadata->getDescription();
        if ($access_key_metadata->getLastUsedDate() !== null) {
            $this->last_used_on = JsonCast::toDate($access_key_metadata->getLastUsedDate()->getTimestamp());
        }
        if ($access_key_metadata->getLastUsedIP() !== null) {
            $this->last_used_by = $access_key_metadata->getLastUsedIP();
        }
    }
}
