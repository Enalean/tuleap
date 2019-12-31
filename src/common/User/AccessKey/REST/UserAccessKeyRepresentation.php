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

declare(strict_types=1);

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
     * @var string
     */
    public $creation_date;

    /**
     * @var string|null
     */
    public $expiration_date;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string|null
     */
    public $last_used_on;

    /**
     * @var string|null
     */
    public $last_used_by;

    /**
     * @var UserAccessKeyScopeRepresentation[]
     */
    public $scopes = [];

    public function build(AccessKeyMetadata $access_key_metadata): void
    {
        $this->id              = $access_key_metadata->getID();
        $this->creation_date   = JsonCast::toDate($access_key_metadata->getCreationDate()->getTimestamp());

        $expiration_date = $access_key_metadata->getExpirationDate();
        if ($expiration_date !== null) {
            $this->expiration_date = JsonCast::toDate($expiration_date->getTimestamp());
        }
        $this->description = $access_key_metadata->getDescription();

        $last_used_date = $access_key_metadata->getLastUsedDate();
        if ($last_used_date !== null) {
            $this->last_used_on = JsonCast::toDate($last_used_date->getTimestamp());
        }

        $last_used_ip = $access_key_metadata->getLastUsedIP();
        if ($last_used_ip !== null) {
            $this->last_used_by = $last_used_ip;
        }

        foreach ($access_key_metadata->getScopes() as $scope) {
            $this->scopes[] = new UserAccessKeyScopeRepresentation($scope);
        }
    }
}
