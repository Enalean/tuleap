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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\BuildIterationUpdateProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessIterationUpdate;

final class BuildIterationUpdateProcessorStub implements BuildIterationUpdateProcessor
{
    private function __construct(private ProcessIterationUpdate $update_processor)
    {
    }

    public static function withProcessor(ProcessIterationUpdate $update_processor): self
    {
        return new self($update_processor);
    }

    #[\Override]
    public function getProcessor(): ProcessIterationUpdate
    {
        return $this->update_processor;
    }
}
