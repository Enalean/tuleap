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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Redirections;

use Tuleap\Test\Builders\ProjectTestBuilder;

final class RedirectToPlannedIterationsAppHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Tracker_Artifact_Redirect $redirect;
    private RedirectToPlannedIterationsAppHandler $handler;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project  = ProjectTestBuilder::aProject()->withUnixName("guinea-pig")->build();
        $this->redirect = new \Tracker_Artifact_Redirect();
        $this->handler  = new RedirectToPlannedIterationsAppHandler();
    }

    public function testItDoesNotRedirectWhenNoRedirectionIsNeeded(): void
    {
        $this->handler->process(
            IterationsRedirectParameters::buildFromCodendiRequest(
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
            IterationsRedirectParameters::buildFromCodendiRequest(
                $this->buildRequest(IterationsRedirectParameters::REDIRECT_AFTER_CREATE_ACTION)
            ),
            $this->redirect,
            $this->project
        );

        self::assertEmpty($this->redirect->base_url);
        self::assertEmpty($this->redirect->query_parameters);
    }

    public function testItSetsRedirectionFlagAndParamsForAFuturRedirectionWhenUserContinuesToSubmitArtifacts(): void
    {
        $this->redirect->mode = \Tracker_Artifact_Redirect::STATE_CONTINUE;

        $this->handler->process(
            IterationsRedirectParameters::buildFromCodendiRequest(
                $this->buildRequest(IterationsRedirectParameters::REDIRECT_AFTER_CREATE_ACTION)
            ),
            $this->redirect,
            $this->project
        );

        self::assertEmpty($this->redirect->base_url);
        self::assertEquals(
            [
                'redirect-to-planned-iterations' => 'create',
                'increment-id' => '1280'
            ],
            $this->redirect->query_parameters
        );
    }

    public function testItRedirectsToProgramManagementApp(): void
    {
        $this->redirect->mode = \Tracker_Artifact_Redirect::STATE_SUBMIT;

        $this->handler->process(
            IterationsRedirectParameters::buildFromCodendiRequest(
                $this->buildRequest(IterationsRedirectParameters::REDIRECT_AFTER_CREATE_ACTION)
            ),
            $this->redirect,
            $this->project
        );

        self::assertEquals('/program_management/guinea-pig/increments/1280/plan', $this->redirect->base_url);
        self::assertEmpty($this->redirect->query_parameters);
    }

    private function buildRequest(?string $redirect_value): \Codendi_Request
    {
        return new \Codendi_Request(
            [
                IterationsRedirectParameters::FLAG => $redirect_value,
                IterationsRedirectParameters::PARAM_INCREMENT_ID => "1280"
            ],
            null
        );
    }
}
