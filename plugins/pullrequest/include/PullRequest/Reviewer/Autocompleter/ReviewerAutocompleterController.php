<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reviewer\Autocompleter;

use GitRepoNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Authorization\UserCannotMergePullRequestException;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

final class ReviewerAutocompleterController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * Arbitrary limit, implement a proper pagination of the results
     * if you want all everything
     */
    private const MAX_USERS_RETURNED_TO_AUTOCOMPLETER = 20;

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;
    /**
     * @var Factory
     */
    private $pull_request_factory;
    /**
     * @var PotentialReviewerRetriever
     */
    private $potential_reviewer_retriever;
    /**
     * @var JSONResponseBuilder
     */
    private $json_response_builder;

    public function __construct(
        UserManager $user_manager,
        Factory $pull_request_factory,
        PullRequestPermissionChecker $pull_request_permission_checker,
        PotentialReviewerRetriever $potential_reviewer_retriever,
        JSONResponseBuilder $json_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->user_manager                    = $user_manager;
        $this->pull_request_factory            = $pull_request_factory;
        $this->pull_request_permission_checker = $pull_request_permission_checker;
        $this->potential_reviewer_retriever    = $potential_reviewer_retriever;
        $this->json_response_builder           = $json_response_builder;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        \Tuleap\Project\ServiceInstrumentation::increment('pullrequest');

        $pull_request_id = (int) $request->getAttribute('pull_request_id');

        try {
            $pull_request = $this->pull_request_factory->getPullRequestById($pull_request_id);
        } catch (PullRequestNotFoundException $e) {
            throw new NotFoundException();
        }

        $current_user = $this->user_manager->getCurrentUser();

        try {
            $this->pull_request_permission_checker->checkPullRequestIsMergeableByUser($pull_request, $current_user);
        } catch (GitRepoNotFoundException | UserCannotMergePullRequestException $e) {
            throw new NotFoundException();
        }

        try {
            $username_to_search = UsernameToSearch::fromString($request->getQueryParams()['name'] ?? '');
        } catch (UsernameToSearchTooSmallException $exception) {
            return $this->json_response_builder->fromData(
                ['error' => sprintf('The "name" parameter must be at least %d characters', $exception->getMinimalAcceptedLength())]
            )->withStatus(400);
        }

        $potential_reviewers = $this->potential_reviewer_retriever->getPotentialReviewers(
            $pull_request,
            $username_to_search,
            self::MAX_USERS_RETURNED_TO_AUTOCOMPLETER
        );

        $potential_reviewer_representations = [];

        foreach ($potential_reviewers as $potential_reviewer) {
            $representation = new MinimalUserRepresentation();
            $representation->build($potential_reviewer);
            $potential_reviewer_representations[] = $representation;
        }

        return $this->json_response_builder->fromData($potential_reviewer_representations);
    }
}
