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

class AccessKeyCreator
{
    /**
     * @var LastAccessKeyIdentifierStore
     */
    private $last_access_key_identifier_store;

    public function __construct(LastAccessKeyIdentifierStore $last_access_key_identifier_store)
    {
        $this->last_access_key_identifier_store = $last_access_key_identifier_store;
    }

    public function create()
    {
        $verification_string = AccessKeyVerificationString::generateNewAccessKeyVerificationString();

        $key_id_for_demonstration_purpose = 1;
        $access_key                       = new AccessKey($key_id_for_demonstration_purpose, $verification_string);

        $this->last_access_key_identifier_store->storeLastGeneratedAccessKeyIdentifier($access_key);
    }
}
