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

use Mockery;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;

final class BurndownCalculatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var BurndownCalculator
     */
    private $burndown_calculator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ComputedFieldDao
     */
    private $computed_dao;

    protected function setUp(): void
    {
        $this->computed_dao        = Mockery::mock(ComputedFieldDao::class);
        $this->burndown_calculator = new BurndownCalculator($this->computed_dao);
    }

    public function testItDoesNotPerformADBCallIfArtifactHasAlreadyBeSeen(): void
    {
        $already_seen = new ArtifactsAlreadyProcessedDuringComputationCollection();
        $already_seen->addArtifactAsAlreadyProcessed('1234');

        $this->computed_dao->shouldReceive('getBurndownManualValueAtGivenTimestamp')->never();

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

        $this->assertEquals($expected, $result);
    }

    public function testItCollectManualValuesOfArtifactForBurndownCache(): void
    {
        $already_seen = new ArtifactsAlreadyProcessedDuringComputationCollection();
        $already_seen->addArtifactAsAlreadyProcessed('1234');

        $this->computed_dao->shouldReceive('getBurndownManualValueAtGivenTimestamp')->once()
            ->andReturn(['value' => 12]);

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

        $this->assertEquals($expected, $result);
    }

    public function testItCollectComputedValuesOfArtifactForBurndownCache(): void
    {
        $already_seen = new ArtifactsAlreadyProcessedDuringComputationCollection();
        $already_seen->addArtifactAsAlreadyProcessed('1234');

        $this->computed_dao->shouldReceive('getBurndownManualValueAtGivenTimestamp')->once()
            ->andReturn(null);
        $dar = Mockery::mock(\DataAccessResult::class);
        $this->computed_dao->shouldReceive('getBurndownComputedValueAtGivenTimestamp')->once()
            ->andReturn($dar);

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

        $this->assertEquals($expected, $result);
    }
}
