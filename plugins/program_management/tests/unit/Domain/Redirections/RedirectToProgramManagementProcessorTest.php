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
use Tuleap\ProgramManagement\Tests\Stub\ProgramRedirectionParametersStub;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RedirectToProgramManagementProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_SHORTNAME = 'my_project';

    private ProgramRedirectionParameters $program_redirection_parameters;
    private ProjectReference $project;
    private Tracker_Artifact_Redirect $redirect;
    private RedirectUserAfterArtifactCreationOrUpdateEvent $event;

    protected function setUp(): void
    {
        $this->program_redirection_parameters = ProgramRedirectionParametersStub::withOtherValue();
        $this->redirect                       = new Tracker_Artifact_Redirect();
        $this->project                        = ProjectReferenceStub::withValues(
            1,
            'Project',
            self::PROJECT_SHORTNAME,
            ''
        );
        $this->event                          = RedirectUserAfterArtifactCreationOrUpdateEventProxy::fromEvent(
            new RedirectAfterArtifactCreationOrUpdateEvent(
                new \Codendi_Request(
                    [
                        ProgramRedirectionParameters::FLAG => ProgramRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
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
        RedirectToProgramManagementProcessor::process(
            $this->program_redirection_parameters,
            $this->event,
            $this->project
        );
        self::assertCount(0, $this->redirect->query_parameters);
    }

    public function testItSetParametersForSubmitAndContinue(): void
    {
        $this->redirect->mode                 = Tracker_Artifact_Redirect::STATE_CONTINUE;
        $this->program_redirection_parameters = ProgramRedirectionParametersStub::withCreate();
        RedirectToProgramManagementProcessor::process(
            $this->program_redirection_parameters,
            $this->event,
            $this->project
        );
        self::assertCount(1, $this->redirect->query_parameters);
        self::assertSame(ProgramRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION, $this->redirect->query_parameters[ProgramRedirectionParameters::FLAG]);
    }

    public function testItDoesNothingForSubmitAndStay(): void
    {
        $this->redirect->mode                 = Tracker_Artifact_Redirect::STATE_STAY;
        $this->program_redirection_parameters = ProgramRedirectionParametersStub::withCreate();
        RedirectToProgramManagementProcessor::process(
            $this->program_redirection_parameters,
            $this->event,
            $this->project
        );
        self::assertCount(0, $this->redirect->query_parameters);
    }

    public function testItRedirectsUserOnApp(): void
    {
        $this->redirect->mode                 = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $this->program_redirection_parameters = ProgramRedirectionParametersStub::withCreate();
        RedirectToProgramManagementProcessor::process(
            $this->program_redirection_parameters,
            $this->event,
            $this->project
        );
        self::assertCount(0, $this->redirect->query_parameters);
        self::assertSame('/program_management/' . self::PROJECT_SHORTNAME, $this->redirect->base_url);
    }
}
