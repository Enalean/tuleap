<?php
/**
 * Copyright (c) Enalean, 2018-present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\AdditionalCriteria;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Report_AdditionalCriterion;

final class CommentCriterionValueSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|null
     */
    private $report;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CommentDao
     */
    private $dao;
    /**
     * @var CommentCriterionValueSaver;
     */
    private $saver;

    protected function setUp(): void
    {
        $this->dao   = \Mockery::mock(CommentDao::class);
        $this->saver = new CommentCriterionValueSaver($this->dao);

        $this->report = \Mockery::spy(\Tracker_Report::class)->shouldReceive('getId')->andReturns(1)->getMock();
    }

    public function testItSavesNewValue(): void
    {
        $criterion = new Tracker_Report_AdditionalCriterion('comment', 'my text');

        $this->dao->shouldReceive('save')->with(1, 'my text')->once();
        $this->dao->shouldReceive('delete')->never();

        $this->saver->saveValueForReport($this->report, $criterion);
    }

    public function testItDeletesValueIfTextIsEmpty(): void
    {
        $criterion = new Tracker_Report_AdditionalCriterion('comment', '');

        $this->dao->shouldReceive('save')->never();
        $this->dao->shouldReceive('delete')->with(1)->once();

        $this->saver->saveValueForReport($this->report, $criterion);
    }
}
