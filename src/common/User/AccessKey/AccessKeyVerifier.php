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

namespace Tuleap\User\AccessKey;

use DateTimeImmutable;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeRetriever;

class AccessKeyVerifier
{
    /**
     * @var AccessKeyDAO
     */
    private $dao;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var AccessKeyScopeRetriever
     */
    private $access_key_scope_retriever;

    public function __construct(
        AccessKeyDAO $dao,
        SplitTokenVerificationStringHasher $hasher,
        \UserManager $user_manager,
        AccessKeyScopeRetriever $access_key_scope_retriever
    ) {
        $this->dao                        = $dao;
        $this->hasher                     = $hasher;
        $this->user_manager               = $user_manager;
        $this->access_key_scope_retriever = $access_key_scope_retriever;
    }

    /**
     * @throws AccessKeyNotFoundException
     * @throws ExpiredAccessKeyException
     * @throws InvalidAccessKeyException
     * @throws AccessKeyMatchingUnknownUserException
     */
    public function getUser(SplitToken $access_key, AuthenticationScope $required_scope, string $ip_address_requesting_verification): \PFUser
    {
        $row = $this->dao->searchAccessKeyVerificationAndTraceabilityDataByID($access_key->getID());
        if ($row === null) {
            throw new AccessKeyNotFoundException($access_key->getID());
        }

        if ($this->isAccessKeyExpired($row['expiration_date'])) {
            throw new ExpiredAccessKeyException();
        }

        $is_valid_access_key = $this->hasher->verifyHash($access_key->getVerificationString(), $row['verifier']);
        if (! $is_valid_access_key) {
            throw new InvalidAccessKeyException();
        }

        if (! $this->hasTheNeededScope($required_scope, $access_key)) {
            throw new AccessKeyDoesNotHaveRequiredScopeException($required_scope);
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

    private function isAccessKeyExpired(?int $expiration_timestamp): bool
    {
        if ($expiration_timestamp === null) {
            return false;
        }

        $current_time = new DateTimeImmutable();

        return $expiration_timestamp <= $current_time->getTimestamp();
    }

    private function updateLastAccessInformationIfNeeded(SplitToken $access_key, $last_usage, $last_ip, $ip_address_requesting_verification): void
    {
        $current_time = new DateTimeImmutable();
        if (
            $last_usage !== null && $last_ip !== null &&
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

    private function hasTheNeededScope(AuthenticationScope $required_scope, SplitToken $access_key): bool
    {
        $access_key_scopes = $this->access_key_scope_retriever->getScopesByAccessKeyID($access_key->getID());
        foreach ($access_key_scopes as $access_key_scope) {
            if ($access_key_scope->covers($required_scope)) {
                return true;
            }
        }
        return false;
    }
}
