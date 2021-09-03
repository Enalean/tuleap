<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreationBuilder;

final class IterationCreationProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID = 20;
    private const USER_ID      = 191;
    private TestLogger $logger;
    private IterationCreation $creation;

    protected function setUp(): void
    {
        $this->creation = IterationCreationBuilder::buildWithIds(self::ITERATION_ID, 53, self::USER_ID, 8612);
        $this->logger   = new TestLogger();
    }

    private function getProcessor(): IterationCreationProcessor
    {
        return new IterationCreationProcessor($this->logger);
    }

    public function testItProcessesIterationCreation(): void
    {
        $this->getProcessor()->processIterationCreation($this->creation);
        self::assertTrue($this->logger->hasDebug('Processing iteration creation with iteration #20 for user #191'));
    }
}
