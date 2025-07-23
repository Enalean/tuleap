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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Events\BuildRedirectFormActionEvent;
use Tuleap\ProgramManagement\Domain\Redirections\IterationRedirectionParameters;

final class BuildRedirectFormActionEventStub implements BuildRedirectFormActionEvent
{
    private function __construct(
        private int $update_program_increment,
        private int $create_program_increment,
        private int $create_iteration,
        private int $update_iteration,
    ) {
    }

    public static function withCount(): self
    {
        return new self(0, 0, 0, 0);
    }

    #[\Override]
    public function injectAndInformUserAboutUpdatingProgramItem(): void
    {
        $this->update_program_increment++;
    }

    #[\Override]
    public function injectAndInformUserAboutCreatingIteration(IterationRedirectionParameters $iteration_redirection_parameters): void
    {
        $this->create_iteration++;
    }

    #[\Override]
    public function injectAndInformUserAboutCreatingProgramIncrement(): void
    {
        $this->create_program_increment++;
    }

    #[\Override]
    public function injectAndInformUserAboutUpdatingIteration(IterationRedirectionParameters $iteration_redirection_parameters): void
    {
        $this->update_iteration++;
    }

    public function getUpdateProgramIncrementCount(): int
    {
        return $this->update_program_increment;
    }

    public function getCreateProgramIncrementCount(): int
    {
        return $this->create_program_increment;
    }

    public function getCreateIterationCount(): int
    {
        return $this->create_iteration;
    }

    public function getUpdateIterationCount(): int
    {
        return $this->update_iteration;
    }
}
