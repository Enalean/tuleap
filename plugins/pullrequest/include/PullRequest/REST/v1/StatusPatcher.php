<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Git_Command_Exception;
use GitRepository;
use GitRepositoryFactory;
use Psr\Log\LoggerInterface;
use Luracast\Restler\RestException;
use PFUser;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Authorization\UserCannotMergePullRequestException;
use Tuleap\PullRequest\Exception\PullRequestCannotBeAbandoned;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestCloser;
use Tuleap\REST\ProjectAuthorization;
use URLVerification;

class StatusPatcher
{
    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;

    /**
     * @var AccessControlVerifier
     */
    private $access_control_verifier;

    /**
     * @var PullRequestCloser
     */
    private $pull_request_closer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var URLVerification
     */
    private $url_verification;
    /**
     * @var PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;

    public function __construct(
        GitRepositoryFactory $git_repository_factory,
        AccessControlVerifier $access_control_verifier,
        PullRequestPermissionChecker $pull_request_permission_checker,
        PullRequestCloser $pull_request_closer,
        URLVerification $url_verification,
        LoggerInterface $logger
    ) {
        $this->git_repository_factory          = $git_repository_factory;
        $this->access_control_verifier         = $access_control_verifier;
        $this->pull_request_permission_checker = $pull_request_permission_checker;
        $this->pull_request_closer             = $pull_request_closer;
        $this->url_verification                = $url_verification;
        $this->logger                          = $logger;
    }

    /**
     * @throws RestException
     */
    public function patchStatus(
        PFUser $user,
        PullRequest $pull_request,
        string $status
    ) {
        $git_repository_source      = $this->getRepository($pull_request->getRepositoryId());
        $git_repository_destination = $this->getRepository($pull_request->getRepoDestId());

        switch ($status) {
            case PullRequestRepresentation::STATUS_ABANDON:
                try {
                    $this->checkUserCanAbandon(
                        $user,
                        $git_repository_source,
                        $git_repository_destination,
                        $pull_request->getBranchSrc(),
                        $pull_request->getBranchDest()
                    );
                    $this->pull_request_closer->abandon($pull_request, $user);
                } catch (PullRequestCannotBeAbandoned $exception) {
                    throw new RestException(400, $exception->getMessage());
                }
                break;
            case PullRequestRepresentation::STATUS_MERGE:
                try {
                    $this->pull_request_permission_checker->checkPullRequestIsMergeableByUser($pull_request, $user);
                    $this->pull_request_closer->doMerge($git_repository_destination, $pull_request, $user);
                } catch (PullRequestCannotBeMerged $exception) {
                    throw new RestException(400, $exception->getMessage());
                } catch (Git_Command_Exception $exception) {
                    $this->logger->error('Error while merging the pull request -> ' . $exception->getMessage());
                    throw new RestException(500, 'Error while merging the pull request');
                } catch (UserCannotMergePullRequestException $exception) {
                    throw new RestException(403, 'User is not able to WRITE the git repository');
                }
                break;
            default:
                throw new RestException(
                    400,
                    'Cannot deal with provided status. Supported statuses are ' . PullRequestRepresentation::STATUS_MERGE . ', ' . PullRequestRepresentation::STATUS_ABANDON
                );
        }
    }

    /**
     * @throws RestException
     */
    private function getRepository($repository_id)
    {
        $repository = $this->git_repository_factory->getRepositoryById($repository_id);

        if (! $repository) {
            throw new RestException(404, "Git repository not found");
        }

        return $repository;
    }

    /**
     * @throws RestException
     */
    private function checkUserCanAbandon(
        PFUser $user,
        GitRepository $repository_source,
        GitRepository $repository_dest,
        $source_reference,
        $destination_reference
    ) {
        ProjectAuthorization::userCanAccessProject($user, $repository_source->getProject(), $this->url_verification);
        ProjectAuthorization::userCanAccessProject($user, $repository_dest->getProject(), $this->url_verification);

        if (
            ! $this->access_control_verifier->canWrite($user, $repository_source, $source_reference) &&
            ! $this->access_control_verifier->canWrite($user, $repository_dest, $destination_reference)
        ) {
            throw new RestException(403, 'User is not able to WRITE in both source and destination git repositories');
        }
    }
}
