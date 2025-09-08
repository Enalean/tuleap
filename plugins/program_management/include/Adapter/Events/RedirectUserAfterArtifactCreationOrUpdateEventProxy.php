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
use Tuleap\ProgramManagement\Domain\Events\RedirectUserAfterArtifactCreationOrUpdateEvent;
use Tuleap\ProgramManagement\Domain\Redirections\IterationRedirectionParameters;
use Tuleap\ProgramManagement\Domain\Redirections\ProgramRedirectionParameters;
use Tuleap\Tracker\Artifact\RedirectAfterArtifactCreationOrUpdateEvent;

final class RedirectUserAfterArtifactCreationOrUpdateEventProxy implements RedirectUserAfterArtifactCreationOrUpdateEvent
{
    private function __construct(
        private Tracker_Artifact_Redirect $redirect,
    ) {
    }

    public static function fromEvent(RedirectAfterArtifactCreationOrUpdateEvent $event): self
    {
        return new self(
            $event->getRedirect(),
        );
    }

    #[\Override]
    public function setQueryParameter(IterationRedirectionParameters $parameters): void
    {
        $this->redirect->query_parameters[IterationRedirectionParameters::FLAG]               = $parameters->getValue();
        $this->redirect->query_parameters[IterationRedirectionParameters::PARAM_INCREMENT_ID] = $parameters->getIncrementId(
        );
    }

    #[\Override]
    public function setProgramIncrementQueryParameter(ProgramRedirectionParameters $parameters): void
    {
        $this->redirect->query_parameters[ProgramRedirectionParameters::FLAG] = $parameters->getValue();
    }

    #[\Override]
    public function setBaseUrl(string $url): void
    {
        $this->redirect->base_url = $url;
    }

    #[\Override]
    public function resetQueryParameters(): void
    {
        $this->redirect->query_parameters = [];
    }

    #[\Override]
    public function isContinueMode(): bool
    {
        return Tracker_Artifact_Redirect::STATE_CONTINUE === $this->redirect->mode;
    }

    #[\Override]
    public function isStayMode(): bool
    {
        return Tracker_Artifact_Redirect::STATE_STAY === $this->redirect->mode;
    }
}
