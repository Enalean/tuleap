<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\IterationView;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationLabels;
use Tuleap\ProgramManagement\Tests\Builder\IterationLabelsBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationLabelsPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const LABEL     = 'Cycles';
    private const SUB_LABEL = 'cycle';
    private IterationLabels $labels;

    #[\Override]
    protected function setUp(): void
    {
        $this->labels = IterationLabelsBuilder::buildWithLabels(self::LABEL, self::SUB_LABEL);
    }

    private function getPresenter(): IterationLabelsPresenter
    {
        return IterationLabelsPresenter::fromLabels($this->labels);
    }

    public function testItBuildsFromLabels(): void
    {
        $presenter = $this->getPresenter();
        self::assertSame(self::LABEL, $presenter->label);
        self::assertSame(self::SUB_LABEL, $presenter->sub_label);
    }

    public function testItDefaultsLabels(): void
    {
        $this->labels = IterationLabelsBuilder::buildWithNoLabels();
        $presenter    = $this->getPresenter();
        self::assertNotSame('', $presenter->label);
        self::assertNotSame('', $presenter->sub_label);
    }
}
