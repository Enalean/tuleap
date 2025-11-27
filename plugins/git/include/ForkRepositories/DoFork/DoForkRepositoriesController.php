<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\ForkRepositories\DoFork;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PFUser;
use Project;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Git\ForkRepositories\ForkRepositoriesUrlsBuilder;
use Tuleap\Git\ForkRepositories\Permissions\ForkType;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\NeverThrow\Fault;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\User\ProvideCurrentUser;

final class DoForkRepositoriesController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly ProvideCurrentUser $provide_current_user,
        private readonly ProcessPersonalRepositoryFork $personal_repository_forker,
        private readonly ProcessCrossProjectsRepositoryFork $cross_projects_repository_forker,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        assert($project instanceof \Project);

        $user        = $this->provide_current_user->getCurrentUser();
        $parsed_body = $request->getParsedBody();
        if (! \is_array($parsed_body)) {
            return $this->redirectToMyWithError($user, _('You are not allowed to access this resource'));
        }

        $fork_type = (string) $parsed_body['choose_destination'] ?: '';
        if ($fork_type === ForkType::PERSONAL->value) {
            return $this->personal_repository_forker->processPersonalFork($user, $project, $request)->match(
                fn(array $warnings) => $this->redirectWithFeedback($user, $project, $warnings),
                fn(Fault $fault) => $this->redirectToForksAndDestinationsSelectionWithError($user, $project, (string) $fault),
            );
        }

        return $this->cross_projects_repository_forker->processCrossProjectsFork($user, $request)->match(
            fn(array $warnings) => $this->redirectWithFeedback($user, $project, $warnings),
            fn(Fault $fault) => $this->redirectToForksAndDestinationsSelectionWithError($user, $project, (string) $fault),
        );
    }

    private function redirectToMyWithError(PFUser $user, string $error_message): ResponseInterface
    {
        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            '/my',
            new NewFeedback(
                \Feedback::ERROR,
                $error_message,
            ),
        );
    }

    private function redirectToForksAndDestinationsSelectionWithError(PFUser $user, Project $project, string $error_message): ResponseInterface
    {
        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            ForkRepositoriesUrlsBuilder::buildGETForksAndDestinationSelectionURL($project),
            new NewFeedback(
                \Feedback::ERROR,
                $error_message,
            ),
        );
    }

    /**
     * @param list<Fault> $warnings
     */
    private function redirectWithFeedback(PFUser $user, Project $project, array $warnings): ResponseInterface
    {
        $feedbacks = [];
        if (empty($warnings)) {
            $feedbacks[] = new NewFeedback(
                \Feedback::SUCCESS,
                dgettext('tuleap-git', 'Successfully forked'),
            );
        }
        array_push(
            $feedbacks,
            ...array_map(
                static fn (Fault $warning) => new NewFeedback(
                    \Feedback::WARN,
                    (string) $warning
                ),
                $warnings,
            )
        );

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            '/plugins/git/' . urlencode($project->getUnixNameLowerCase()) . '/',
            ...$feedbacks,
        );
    }
}
