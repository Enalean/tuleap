<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use TestHelper;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;

#[DisableReturnValueGenerationForTestDoubles]
final class BurndownCalculatorTest extends TestCase
{
    private BurndownCalculator $burndown_calculator;
    private ComputedFieldDao&MockObject $computed_dao;

    protected function setUp(): void
    {
        $this->computed_dao        = $this->createMock(ComputedFieldDao::class);
        $this->burndown_calculator = new BurndownCalculator($this->computed_dao);
    }

    public function testItDoesNotPerformADBCallIfArtifactHasAlreadyBeSeen(): void
    {
        $already_seen = new ArtifactsAlreadyProcessedDuringComputationCollection();
        $already_seen->addArtifactAsAlreadyProcessed('1234');

        $this->computed_dao->expects($this->never())->method('getBurndownManualValueAtGivenTimestamp');

        $expected = [
            'children'   => false,
            'manual_sum' => null,
        ];

        $result = $this->burndown_calculator->fetchChildrenAndManualValuesOfArtifacts(
            ['1234'],
            1587049512,
            true,
            'effort',
            12,
            $already_seen
        );

        self::assertEquals($expected, $result);
    }

    public function testItCollectManualValuesOfArtifactForBurndownCache(): void
    {
        $already_seen = new ArtifactsAlreadyProcessedDuringComputationCollection();
        $already_seen->addArtifactAsAlreadyProcessed('1234');

        $this->computed_dao->expects($this->once())->method('getBurndownManualValueAtGivenTimestamp')->willReturn(['value' => 12]);

        $expected = [
            'children'   => false,
            'manual_sum' => 12,
        ];

        $result = $this->burndown_calculator->fetchChildrenAndManualValuesOfArtifacts(
            ['123'],
            1587049512,
            true,
            'effort',
            12,
            $already_seen
        );

        self::assertEquals($expected, $result);
    }

    public function testItCollectComputedValuesOfArtifactForBurndownCache(): void
    {
        $already_seen = new ArtifactsAlreadyProcessedDuringComputationCollection();
        $already_seen->addArtifactAsAlreadyProcessed('1234');

        $this->computed_dao->expects($this->once())->method('getBurndownManualValueAtGivenTimestamp')->willReturn(null);
        $dar = TestHelper::emptyDar();
        $this->computed_dao->expects($this->once())->method('getBurndownComputedValueAtGivenTimestamp')->willReturn($dar);

        $expected = [
            'children'   => $dar,
            'manual_sum' => false,
        ];

        $result = $this->burndown_calculator->fetchChildrenAndManualValuesOfArtifacts(
            ['123'],
            1587049512,
            true,
            'effort',
            12,
            $already_seen
        );

        self::assertEquals($expected, $result);
    }
}
