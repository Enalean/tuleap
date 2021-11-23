<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement;

use Tuleap\Test\Builders\ProjectTestBuilder;

final class RedirectToProgramIncrementAppHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Tracker_Artifact_Redirect $redirect;
    private RedirectToProgramIncrementAppHandler $handler;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project  = ProjectTestBuilder::aProject()->withUnixName("guinea-pig")->build();
        $this->redirect = new \Tracker_Artifact_Redirect();
        $this->handler  = new RedirectToProgramIncrementAppHandler();
    }

    public function testItDoesNotRedirectWhenNoRedirectionIsNeeded(): void
    {
        $this->handler->process(
            RedirectToProgramManagementAppManager::buildFromCodendiRequest(
                $this->buildRequest(null)
            ),
            $this->redirect,
            $this->project
        );

        self::assertEmpty($this->redirect->base_url);
        self::assertEmpty($this->redirect->query_parameters);
    }

    public function testItDoesNotRedirectWhenUserStaysAfterSubmission(): void
    {
        $this->redirect->mode = \Tracker_Artifact_Redirect::STATE_STAY;

        $this->handler->process(
            RedirectToProgramManagementAppManager::buildFromCodendiRequest(
                $this->buildRequest(RedirectToProgramManagementAppManager::REDIRECT_AFTER_CREATE_ACTION)
            ),
            $this->redirect,
            $this->project
        );

        self::assertEmpty($this->redirect->base_url);
        self::assertEmpty($this->redirect->query_parameters);
    }

    public function testItSetsRedirectionFlagForAFuturRedirectionWhenUserContinuesToSubmitArtifacts(): void
    {
        $this->redirect->mode = \Tracker_Artifact_Redirect::STATE_CONTINUE;

        $this->handler->process(
            RedirectToProgramManagementAppManager::buildFromCodendiRequest(
                $this->buildRequest(RedirectToProgramManagementAppManager::REDIRECT_AFTER_CREATE_ACTION)
            ),
            $this->redirect,
            $this->project
        );

        self::assertEmpty($this->redirect->base_url);
        self::assertEquals(['program_increment' => 'create'], $this->redirect->query_parameters);
    }

    public function testItRedirectsToProgramManagementApp(): void
    {
        $this->redirect->mode = \Tracker_Artifact_Redirect::STATE_SUBMIT;

        $this->handler->process(
            RedirectToProgramManagementAppManager::buildFromCodendiRequest(
                $this->buildRequest(RedirectToProgramManagementAppManager::REDIRECT_AFTER_CREATE_ACTION)
            ),
            $this->redirect,
            $this->project
        );

        self::assertEquals('/program_management/guinea-pig', $this->redirect->base_url);
        self::assertEmpty($this->redirect->query_parameters);
    }

    /**
     * @return \Codendi_Request&\PHPUnit\Framework\MockObject\MockObject
     */
    private function buildRequest(?string $redirect_value)
    {
        $request = $this->createMock(\Codendi_Request::class);
        $request->method('get')->with(RedirectToProgramManagementAppManager::FLAG)->willReturn($redirect_value);
        return $request;
    }
}
