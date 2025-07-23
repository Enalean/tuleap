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
use Tuleap\ProgramManagement\Tests\Stub\IterationRedirectionParametersStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RedirectUserAfterArtifactCreationOrUpdateEventProxyTest extends TestCase
{
    private Tracker_Artifact_Redirect $redirect;
    private RedirectAfterArtifactCreationOrUpdateEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->redirect = new Tracker_Artifact_Redirect();
        $this->event    =
            new RedirectAfterArtifactCreationOrUpdateEvent(
                new \Codendi_Request(
                    [
                        IterationRedirectionParameters::FLAG               => IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION,
                        IterationRedirectionParameters::PARAM_INCREMENT_ID => '1280',
                    ],
                    null
                ),
                $this->redirect,
                ArtifactTestBuilder::anArtifact(25)->build()
            );
    }

    public function testSetAndResetQueryParameter(): void
    {
        $proxy = RedirectUserAfterArtifactCreationOrUpdateEventProxy::fromEvent($this->event);
        $proxy->setQueryParameter(IterationRedirectionParametersStub::withValues(IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION, '100'));
        self::assertSame('100', $this->redirect->query_parameters[IterationRedirectionParameters::PARAM_INCREMENT_ID]);
        self::assertSame(IterationRedirectionParameters::REDIRECT_AFTER_CREATE_ACTION, $this->redirect->query_parameters[IterationRedirectionParameters::FLAG]);

        $proxy->resetQueryParameters();
        self::assertEmpty($this->redirect->query_parameters);
    }

    public function testItSetBaseUrl(): void
    {
        $proxy = RedirectUserAfterArtifactCreationOrUpdateEventProxy::fromEvent($this->event);
        $proxy->setBaseUrl('/program_management/my_project/');
        self::assertSame('/program_management/my_project/', $this->redirect->base_url);
    }

    public function testItIsInStayMode(): void
    {
        $this->redirect->mode = Tracker_Artifact_Redirect::STATE_STAY;
        $proxy                = RedirectUserAfterArtifactCreationOrUpdateEventProxy::fromEvent($this->event);
        self::assertTrue($proxy->isStayMode());
    }

    public function testItIsInContinueMode(): void
    {
        $this->redirect->mode = Tracker_Artifact_Redirect::STATE_CONTINUE;
        $proxy                = RedirectUserAfterArtifactCreationOrUpdateEventProxy::fromEvent($this->event);
        self::assertTrue($proxy->isContinueMode());
    }

    public function testItIsInSubmitMode(): void
    {
        $this->redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;
        $proxy                = RedirectUserAfterArtifactCreationOrUpdateEventProxy::fromEvent($this->event);
        self::assertFalse($proxy->isStayMode());
        self::assertFalse($proxy->isContinueMode());
    }
}
