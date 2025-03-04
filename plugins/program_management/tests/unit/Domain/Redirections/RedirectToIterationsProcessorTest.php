<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Redirections;

use Tracker_Artifact_Redirect;
use Tuleap\ProgramManagement\Adapter\Events\RedirectUserAfterArtifactCreationOrUpdateEventProxy;
use Tuleap\ProgramManagement\Domain\Events\RedirectUserAfterArtifactCreationOrUpdateEvent;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\IterationRedirectionParametersStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RedirectToIterationsProcessorTest extends TestCase
{
    private const PROGRAM_INCREMENT_ID = 100;
    private const PROJECT_SHORTNAME    = 'my_project';
    private ProjectReference $project;
    private Tracker_Artifact_Redirect $redirect;
    private RedirectUserAfterArtifactCreationOrUpdateEvent $event;
    private IterationRedirectionParameters $iteration_redirect_parameters;

    protected function setUp(): void
    {
        $this->iteration_redirect_parameters = IterationRedirectionParametersStub::withValues('Nope', '100');
        $this->redirect                      = new Tracker_Artifact_Redirect();
        $this->project                       = ProjectReferenceStub::withValues(1, 'Project', self::PROJECT_SHORTNAME, '');
        $this->event                         = RedirectUserAfterArtifactCreationOrUpdateEventProxy::fromEvent(
            new RedirectAfterArtifactCreationOrUpdateEvent(
                new \Codendi_Request(
                    [
                        IterationRedirectionParameters::FLAG               => IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
                        IterationRedirectionParameters::PARAM_INCREMENT_ID => self::PROGRAM_INCREMENT_ID,
                    ],
                    null
                ),
                $this->redirect,
                ArtifactTestBuilder::anArtifact(25)->build()
            )
        );
    }

    public function testItDoesNothingWhenNoRedirection(): void
    {
        RedirectToIterationsProcessor::process($this->iteration_redirect_parameters, $this->event, $this->project);
        self::assertCount(0, $this->redirect->query_parameters);
    }

    public function testItSetParametersForSubmitAndContinue(): void
    {
        $this->redirect->mode                = Tracker_Artifact_Redirect::STATE_CONTINUE;
        $this->iteration_redirect_parameters = IterationRedirectionParametersStub::withCreate();
        RedirectToIterationsProcessor::process($this->iteration_redirect_parameters, $this->event, $this->project);
        self::assertCount(2, $this->redirect->query_parameters);
        self::assertSame('create', $this->redirect->query_parameters[IterationRedirectionParameters::FLAG]);
    }

    public function testItDoesNothingForSubmitAndStay(): void
    {
        $this->redirect->mode                = Tracker_Artifact_Redirect::STATE_STAY;
        $this->iteration_redirect_parameters = IterationRedirectionParametersStub::withCreate();
        RedirectToIterationsProcessor::process($this->iteration_redirect_parameters, $this->event, $this->project);
        self::assertCount(0, $this->redirect->query_parameters);
    }

    public function testItRedirectsUserOnApp(): void
    {
        $this->redirect->mode                = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $this->iteration_redirect_parameters = IterationRedirectionParametersStub::withCreate();
        RedirectToIterationsProcessor::process($this->iteration_redirect_parameters, $this->event, $this->project);
        self::assertCount(0, $this->redirect->query_parameters);
        self::assertSame('/program_management/' . self::PROJECT_SHORTNAME . '/increments/' . self::PROGRAM_INCREMENT_ID . '/plan', $this->redirect->base_url);
    }
}
