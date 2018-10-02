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

class AccessKeyVerifier
{
    /**
     * @var AccessKeyDAO
     */
    private $dao;
    /**
     * @var AccessKeyVerificationStringHasher
     */
    private $hasher;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(AccessKeyDAO $dao, AccessKeyVerificationStringHasher $hasher, \UserManager $user_manager)
    {
        $this->dao          = $dao;
        $this->hasher       = $hasher;
        $this->user_manager = $user_manager;
    }

    /**
     * @return \PFUser
     * @throws AccessKeyNotFoundException
     * @throws InvalidAccessKeyException
     * @throws AccessKeyMatchingUnknownUserException
     */
    public function getUser(AccessKey $access_key, $ip_address_requesting_verification)
    {
        $row = $this->dao->searchAccessKeyVerificationAndTraceabilityDataByID($access_key->getID());
        if ($row === null) {
            throw new AccessKeyNotFoundException($access_key->getID());
        }
        $is_valid_access_key = $this->hasher->verifyHash($access_key->getVerificationString(), $row['verifier']);
        if (! $is_valid_access_key) {
            throw new InvalidAccessKeyException();
        }

        $user = $this->user_manager->getUserById($row['user_id']);
        if ($user === null) {
            throw new AccessKeyMatchingUnknownUserException($row['user_id']);
        }

        $this->updateLastAccessInformationIfNeeded(
            $access_key,
            $row['last_usage'],
            $row['last_ip'],
            $ip_address_requesting_verification
        );

        return $user;
    }

    private function updateLastAccessInformationIfNeeded(AccessKey $access_key, $last_usage, $last_ip, $ip_address_requesting_verification)
    {
        $current_time = new \DateTimeImmutable();
        if ($last_usage !== null && $last_ip !== null &&
            $last_ip === $ip_address_requesting_verification &&
            ($current_time->getTimestamp() - $last_usage) < (int) \ForgeConfig::get('last_access_resolution')
        ) {
            return;
        }
        $this->dao->updateAccessKeyUsageByID(
            $access_key->getID(),
            $current_time->getTimestamp(),
            $ip_address_requesting_verification
        );
    }
}
