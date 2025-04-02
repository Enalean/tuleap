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

namespace Tuleap\ProgramManagement\Adapter\Events;

use Tracker_Artifact_Redirect;
use Tuleap\GlobalResponseMock;
use Tuleap\ProgramManagement\Domain\Redirections\IterationRedirectionParameters;
use Tuleap\ProgramManagement\Domain\Redirections\ProgramRedirectionParameters;
use Tuleap\ProgramManagement\Tests\Stub\IterationRedirectionParametersStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Renderer\BuildArtifactFormActionEvent;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BuildRedirectFormActionEventProxyTest extends TestCase
{
    use GlobalResponseMock;

    private BuildRedirectFormActionEventProxy $proxy;
    private Tracker_Artifact_Redirect $redirect;

    protected function setUp(): void
    {
        $this->redirect = new Tracker_Artifact_Redirect();
        $this->proxy    = BuildRedirectFormActionEventProxy::fromEvent(
            new BuildArtifactFormActionEvent(
                new \Codendi_Request(
                    [],
                    null
                ),
                $this->redirect
            )
        );
    }

    public function testItInjectAndInformUserAboutUpdatingProgramItem(): void
    {
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(
            \Feedback::INFO,
            $this->stringContains('update')
        );
        $this->proxy->injectAndInformUserAboutUpdatingProgramItem();
        self::assertCount(1, $this->redirect->query_parameters);
        self::assertSame(
            ProgramRedirectionParameters::REDIRECT_AFTER_UPDATE_ACTION,
            $this->redirect->query_parameters[ProgramRedirectionParameters::FLAG]
        );
    }

    public function testItInjectAndInformUserAboutCreatingProgramIncrement(): void
    {
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(
            \Feedback::INFO,
            $this->stringContains('create')
        );
        $this->proxy->injectAndInformUserAboutCreatingProgramIncrement();
        self::assertCount(1, $this->redirect->query_parameters);
        self::assertSame(
            ProgramRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
            $this->redirect->query_parameters[ProgramRedirectionParameters::FLAG]
        );
    }

    public function testItInjectAndInformUserAboutCreatingIncrementIteration(): void
    {
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(
            \Feedback::INFO,
            $this->stringContains('create')
        );
        $increment_id = '100';
        $this->proxy->injectAndInformUserAboutCreatingIteration(
            IterationRedirectionParametersStub::withValues(
                IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
                $increment_id
            )
        );
        self::assertCount(5, $this->redirect->query_parameters);
        self::assertSame(
            IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
            $this->redirect->query_parameters[IterationRedirectionParameters::FLAG]
        );
        self::assertSame(
            $increment_id,
            $this->redirect->query_parameters[IterationRedirectionParameters::PARAM_INCREMENT_ID]
        );
        self::assertSame(
            $increment_id,
            $this->redirect->query_parameters['link-artifact-id']
        );
        self::assertSame(
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD,
            $this->redirect->query_parameters['link-type']
        );
        self::assertSame('true', $this->redirect->query_parameters['immediate']);
    }

    public function testItInjectsAndInformsUserAboutUpdatingIteration(): void
    {
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with(
            \Feedback::INFO,
            $this->stringContains('update')
        );
        $increment_id = '100';
        $this->proxy->injectAndInformUserAboutUpdatingIteration(
            IterationRedirectionParametersStub::withValues(
                IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
                $increment_id
            )
        );
        self::assertCount(2, $this->redirect->query_parameters);
        self::assertSame(
            IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
            $this->redirect->query_parameters[IterationRedirectionParameters::FLAG]
        );
        self::assertSame(
            $increment_id,
            $this->redirect->query_parameters[IterationRedirectionParameters::PARAM_INCREMENT_ID]
        );
    }
}
