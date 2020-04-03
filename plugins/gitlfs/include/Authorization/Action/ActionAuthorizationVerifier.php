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

namespace Tuleap\GitLFS\Authorization\Action;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationType;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectID;

class ActionAuthorizationVerifier
{
    /**
     * @var ActionAuthorizationDAO
     */
    private $dao;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var \GitRepositoryFactory
     */
    private $git_repository_factory;

    public function __construct(
        ActionAuthorizationDAO $dao,
        SplitTokenVerificationStringHasher $hasher,
        \GitRepositoryFactory $git_repository_factory
    ) {
        $this->dao                    = $dao;
        $this->hasher                 = $hasher;
        $this->git_repository_factory = $git_repository_factory;
    }

    /**
     * @return AuthorizedAction
     * @throws ActionAuthorizationNotFoundException
     * @throws InvalidActionAuthorizationException
     * @throws ActionAuthorizationMatchingUnknownRepositoryException
     */
    public function getAuthorization(
        \DateTimeImmutable $current_time,
        SplitToken $authorization_token,
        $oid,
        ActionAuthorizationType $action_type
    ) {
        $row = $this->dao->searchAuthorizationByIDAndExpiration($authorization_token->getID(), $current_time->getTimestamp());
        if ($row === null) {
            throw new ActionAuthorizationNotFoundException($authorization_token->getID());
        }

        $is_valid_access_key = $this->hasher->verifyHash($authorization_token->getVerificationString(), $row['verifier']);
        if (
            ! $is_valid_access_key ||
            ! \hash_equals($oid, $row['object_oid']) ||
            ! \hash_equals($action_type->getName(), $row['action_type'])
        ) {
            throw new InvalidActionAuthorizationException();
        }

        $git_repository = $this->git_repository_factory->getRepositoryById($row['repository_id']);
        if ($git_repository === null) {
            throw new ActionAuthorizationMatchingUnknownRepositoryException($row['repository_id']);
        }

        return new AuthorizedAction(
            $git_repository,
            new LFSObject(new LFSObjectID($row['object_oid']), $row['object_size'])
        );
    }
}
