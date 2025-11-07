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

use Feedback;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\ForkRepositories\ForkRepositoriesUrlsBuilder;
use Tuleap\Git\ForkRepositories\Permissions\ForkType;
use Tuleap\Git\Tests\Stub\ForkRepositories\DoFork\ProcessCrossProjectsRepositoryForkStub;
use Tuleap\Git\Tests\Stub\ForkRepositories\DoFork\ProcessPersonalRepositoryForkStub;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\NeverThrow\Fault;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DoForkRepositoriesControllerTest extends TestCase
{
    private RedirectWithFeedbackFactory&MockObject $redirect_with_feedback;
    private \PFUser $user;
    private \Project $project;
    private ProcessPersonalRepositoryFork $process_personal_repository_fork;
    private ProcessCrossProjectsRepositoryFork $process_cross_projects_repository_fork;

    #[\Override]
    protected function setUp(): void
    {
        $this->redirect_with_feedback                 = $this->createMock(RedirectWithFeedbackFactory::class);
        $this->user                                   = UserTestBuilder::aUser()->build();
        $this->project                                = ProjectTestBuilder::aProject()->withUnixName('forks-and-knives')->build();
        $this->process_personal_repository_fork       = ProcessPersonalRepositoryForkStub::willNotBeCalled();
        $this->process_cross_projects_repository_fork = ProcessCrossProjectsRepositoryForkStub::willNotBeCalled();
    }

    private function buildController(): DoForkRepositoriesController
    {
        return new DoForkRepositoriesController(
            $this->redirect_with_feedback,
            ProvideCurrentUserStub::buildWithUser($this->user),
            $this->process_personal_repository_fork,
            $this->process_cross_projects_repository_fork,
            new NoopSapiEmitter(),
        );
    }

    public function testItRedirectsWithSuccessWhenPersonalForkSucceeds(): void
    {
        $this->process_personal_repository_fork = ProcessPersonalRepositoryForkStub::withSuccess();
        $this->redirect_with_feedback->expects($this->once())->method('createResponseForUser')->with(
            $this->user,
            '/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/',
            new NewFeedback(Feedback::SUCCESS, 'Successfully forked')
        );

        $this->buildController()->handle(
            (new NullServerRequest())
                ->withAttribute(\Project::class, $this->project)
                ->withParsedBody(['choose_destination' => ForkType::PERSONAL->value]),
        );
    }

    public function testItRedirectsPersonalForksWithWarningsWhenSomeRepositoriesCouldNotClonedBecauseReasons(): void
    {
        $warnings                               = [
            Fault::fromMessage('This repository contains too much legacy code, yuck.'),
            Fault::fromMessage('Meh.'),
        ];
        $this->process_personal_repository_fork = ProcessPersonalRepositoryForkStub::withWarnings(...$warnings);
        $this->redirect_with_feedback->expects($this->once())->method('createResponseForUser')->with(
            $this->user,
            '/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/',
            ...array_map(
                static fn (Fault $warning) => new NewFeedback(Feedback::WARN, (string) $warning),
                $warnings
            ),
        );

        $this->buildController()->handle(
            (new NullServerRequest())
                ->withAttribute(\Project::class, $this->project)
                ->withParsedBody(['choose_destination' => ForkType::PERSONAL->value]),
        );
    }

    public function testItRedirectsWithErrorWhenPersonalForkFails(): void
    {
        $fault = Fault::fromMessage('An error occurred while forking the repositories');

        $this->process_personal_repository_fork = ProcessPersonalRepositoryForkStub::withError($fault);
        $this->redirect_with_feedback->expects($this->once())->method('createResponseForUser')->with(
            $this->user,
            ForkRepositoriesUrlsBuilder::buildGETForksAndDestinationSelectionURL($this->project),
            new NewFeedback(Feedback::ERROR, (string) $fault)
        );

        $this->buildController()->handle(
            (new NullServerRequest())
                ->withAttribute(\Project::class, $this->project)
                ->withParsedBody(['choose_destination' => ForkType::PERSONAL->value]),
        );
    }

    public function testItRedirectsWithSuccessWhenCrossProjectsForkSucceeds(): void
    {
        $this->process_cross_projects_repository_fork = ProcessCrossProjectsRepositoryForkStub::withSuccess();
        $this->redirect_with_feedback->expects($this->once())->method('createResponseForUser')->with(
            $this->user,
            '/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/',
            new NewFeedback(Feedback::SUCCESS, 'Successfully forked')
        );

        $this->buildController()->handle(
            (new NullServerRequest())
                ->withAttribute(\Project::class, $this->project)
                ->withParsedBody(['choose_destination' => ForkType::CROSS_PROJECT->value]),
        );
    }

    public function testItRedirectsCrossProjectsForksWithWarningsWhenSomeRepositoriesCouldNotClonedBecauseReasons(): void
    {
        $warnings                                     = [
            Fault::fromMessage('This repository contains too much legacy code, yuck.'),
            Fault::fromMessage('Meh.'),
        ];
        $this->process_cross_projects_repository_fork = ProcessCrossProjectsRepositoryForkStub::withWarnings(...$warnings);
        $this->redirect_with_feedback->expects($this->once())->method('createResponseForUser')->with(
            $this->user,
            '/plugins/git/' . urlencode($this->project->getUnixNameLowerCase()) . '/',
            ...array_map(
                static fn (Fault $warning) => new NewFeedback(Feedback::WARN, (string) $warning),
                $warnings
            ),
        );

        $this->buildController()->handle(
            (new NullServerRequest())
                ->withAttribute(\Project::class, $this->project)
                ->withParsedBody(['choose_destination' => ForkType::CROSS_PROJECT->value]),
        );
    }

    public function testItRedirectsWithErrorWhenCrossProjectsForkFails(): void
    {
        $fault = Fault::fromMessage('An error occurred while forking the repositories');

        $this->process_cross_projects_repository_fork = ProcessCrossProjectsRepositoryForkStub::withError($fault);
        $this->redirect_with_feedback->expects($this->once())->method('createResponseForUser')->with(
            $this->user,
            ForkRepositoriesUrlsBuilder::buildGETForksAndDestinationSelectionURL($this->project),
            new NewFeedback(Feedback::ERROR, (string) $fault)
        );

        $this->buildController()->handle(
            (new NullServerRequest())
                ->withAttribute(\Project::class, $this->project)
                ->withParsedBody(['choose_destination' => ForkType::CROSS_PROJECT->value]),
        );
    }
}
