<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker;

use Tuleap\ProgramManagement\Tests\Builder\IterationTrackerIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationLabelsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const LABEL     = 'Iterations';
    private const SUB_LABEL = 'iteration';

    private ?IterationTrackerIdentifier $iteration_tracker;
    private RetrieveIterationLabelsStub $label_retriever;

    protected function setUp(): void
    {
        $this->iteration_tracker = IterationTrackerIdentifierBuilder::buildWithId(35);
        $this->label_retriever   = RetrieveIterationLabelsStub::buildLabels(self::LABEL, self::SUB_LABEL);
    }

    private function getLabelsFromIterationTracker(): IterationLabels
    {
        return IterationLabels::fromIterationTracker(
            $this->label_retriever,
            $this->iteration_tracker
        );
    }

    public function testItBuildsFromIterationTracker(): void
    {
        $labels = $this->getLabelsFromIterationTracker();
        self::assertSame(self::LABEL, $labels->label);
        self::assertSame(self::SUB_LABEL, $labels->sub_label);
    }

    public function testLabelsAreNullWhenNoIterationTracker(): void
    {
        $this->iteration_tracker = null;
        $labels                  = $this->getLabelsFromIterationTracker();

        self::assertNull($labels->label);
        self::assertNull($labels->sub_label);
    }

    public function testLabelsAreNullWhenNoSavedLabels(): void
    {
        $this->label_retriever = RetrieveIterationLabelsStub::buildLabels(null, null);
        $labels                = $this->getLabelsFromIterationTracker();

        self::assertNull($labels->label);
        self::assertNull($labels->sub_label);
    }
}
