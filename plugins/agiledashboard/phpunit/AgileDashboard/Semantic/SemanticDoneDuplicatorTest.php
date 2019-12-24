<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TestHelper;
use Tracker_Semantic_StatusDao;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;

class SemanticDoneDuplicatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SemanticDoneDuplicator
     */
    private $duplicator;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticDoneDao
     */
    private $done_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Semantic_StatusDao
     */
    private $status_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->done_dao   = Mockery::mock(SemanticDoneDao::class);
        $this->status_dao = Mockery::mock(Tracker_Semantic_StatusDao::class);

        $this->duplicator = new SemanticDoneDuplicator(
            $this->done_dao,
            $this->status_dao
        );
    }

    public function testItDuplicatesDoneSemantic(): void
    {
        $mapping = [
            [
                'from' => '712',
                'to'   => 9595,
                'values' => [
                    460 => 6226,
                    461 => 6227,
                    462 => 6228,
                ]
            ]
        ];

        $this->done_dao->shouldReceive('getSelectedValues')->once()->andReturn(TestHelper::arrayToDar([
            'value_id' => '462'
        ]));

        $this->status_dao->shouldReceive('searchByTrackerId')->once()->andReturn(TestHelper::arrayToDar([
            'field_id' => '712'
        ]));

        $this->done_dao->shouldReceive('addForTracker')
            ->with(201, [6228])
            ->once();

        $this->duplicator->duplicate(101, 201, $mapping);
    }

    public function testItDoesNotDuplicateEmptyDoneSemantic(): void
    {
        $mapping = [
            [
                'from' => '712',
                'to'   => 9595,
                'values' => [
                    460 => 6226,
                    461 => 6227,
                    462 => 6228,
                ]
            ]
        ];

        $this->done_dao->shouldReceive('getSelectedValues')->once()->andReturn(TestHelper::emptyDar());

        $this->status_dao->shouldNotReceive('searchByTrackerId');
        $this->done_dao->shouldNotReceive('addForTracker');

        $this->duplicator->duplicate(101, 201, $mapping);
    }

    public function testItDoesNotDuplicateDoneSemanticIfItCannotRetrieveBaseStatusField(): void
    {
        $mapping = [
            [
                'from' => '712',
                'to'   => 9595,
                'values' => [
                    460 => 6226,
                    461 => 6227,
                    462 => 6228,
                ]
            ]
        ];

        $this->done_dao->shouldReceive('getSelectedValues')->once()->andReturn(TestHelper::arrayToDar([
            'value_id' => '462'
        ]));

        $this->status_dao->shouldReceive('searchByTrackerId')->once()->andReturn(TestHelper::emptyDar());

        $this->done_dao->shouldNotReceive('addForTracker');

        $this->duplicator->duplicate(101, 201, $mapping);
    }

    public function testItDoesNotDuplicateDoneSemanticIfItCannotRetrieveValueInMapping(): void
    {
        $mapping = [
            [
                'from' => '712',
                'to'   => 9595,
                'values' => [
                    460 => 6226,
                    461 => 6227,
                    462 => 6228,
                ]
            ]
        ];

        $this->done_dao->shouldReceive('getSelectedValues')->once()->andReturn(TestHelper::arrayToDar([
            'value_id' => '463'
        ]));

        $this->status_dao->shouldReceive('searchByTrackerId')->once()->andReturn(TestHelper::arrayToDar([
            'field_id' => '712'
        ]));

        $this->done_dao->shouldNotReceive('addForTracker');

        $this->duplicator->duplicate(101, 201, $mapping);
    }
}
