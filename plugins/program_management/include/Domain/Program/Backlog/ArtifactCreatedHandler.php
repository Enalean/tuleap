<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Events\ArtifactCreatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DispatchProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\RemovePlannedFeaturesFromTopBacklog;

final class ArtifactCreatedHandler
{
    public function __construct(
        private RemovePlannedFeaturesFromTopBacklog $feature_remover,
        private VerifyIsProgramIncrementTracker $program_increment_verifier,
        private DispatchProgramIncrementCreation $creation_dispatcher,
    ) {
    }

    public function handle(ArtifactCreatedEvent $event): void
    {
        $this->feature_remover->removeFeaturesPlannedInAProgramIncrementFromTopBacklog($event->getArtifact()->getId());

        $creation = ProgramIncrementCreation::fromArtifactCreatedEvent(
            $this->program_increment_verifier,
            $event
        );
        if (! $creation) {
            return;
        }
        $this->creation_dispatcher->dispatchCreation($creation);
    }
}
