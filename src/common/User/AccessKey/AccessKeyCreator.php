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

declare(strict_types=1);

namespace Tuleap\User\AccessKey;

use DateTimeImmutable;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeSaver;
use Tuleap\User\AccessKey\Scope\NoValidAccessKeyScopeException;

class AccessKeyCreator
{
    /**
     * @var LastAccessKeyIdentifierStore
     */
    private $last_access_key_identifier_store;
    /**
     * @var AccessKeyDAO
     */
    private $dao;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var AccessKeyScopeSaver
     */
    private $access_key_scope_saver;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var AccessKeyCreationNotifier
     */
    private $notifier;

    public function __construct(
        LastAccessKeyIdentifierStore $last_access_key_identifier_store,
        AccessKeyDAO $dao,
        SplitTokenVerificationStringHasher $hasher,
        AccessKeyScopeSaver $access_key_scope_saver,
        DBTransactionExecutor $transaction_executor,
        AccessKeyCreationNotifier $notifier
    ) {
        $this->last_access_key_identifier_store = $last_access_key_identifier_store;
        $this->dao                              = $dao;
        $this->hasher                           = $hasher;
        $this->access_key_scope_saver           = $access_key_scope_saver;
        $this->transaction_executor             = $transaction_executor;
        $this->notifier                         = $notifier;
    }

    /**
     * @throws AccessKeyAlreadyExpiredException
     * @throws NoValidAccessKeyScopeException
     */
    public function create(
        \PFUser $user,
        string $description,
        ?DateTimeImmutable $expiration_date,
        AuthenticationScope ...$access_key_scopes
    ): void {
        $verification_string = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $current_time        = new DateTimeImmutable();

        $expiration_date_timestamp = null;
        if ($expiration_date !== null) {
            $expiration_date_timestamp = $expiration_date->getTimestamp();
        }

        if ($expiration_date_timestamp !== null && $expiration_date_timestamp < $current_time->getTimestamp()) {
            throw new AccessKeyAlreadyExpiredException();
        }

        $key_id = $this->transaction_executor->execute(
            function () use ($expiration_date_timestamp, $description, $current_time, $verification_string, $user, $access_key_scopes): int {
                $key_id = $this->dao->create(
                    (int) $user->getId(),
                    $this->hasher->computeHash($verification_string),
                    $current_time->getTimestamp(),
                    $description,
                    $expiration_date_timestamp
                );
                $this->access_key_scope_saver->saveKeyScopes(
                    $key_id,
                    ...$access_key_scopes
                );

                return $key_id;
            }
        );

        $access_key = new SplitToken($key_id, $verification_string);

        assert(! empty($access_key_scopes));
        $this->notifier->notifyCreation($user, $description, $access_key_scopes);
        $this->last_access_key_identifier_store->storeLastGeneratedAccessKeyIdentifier($access_key);
    }
}
