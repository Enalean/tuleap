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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Report_AdditionalCriterion;
use Tuleap\Tracker\Test\Builders\ReportTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentCriterionValueSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Tracker_Report $report;
    private CommentDao&MockObject $dao;
    private CommentCriterionValueSaver $saver;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao   = $this->createMock(CommentDao::class);
        $this->saver = new CommentCriterionValueSaver($this->dao);

        $this->report = ReportTestBuilder::aPublicReport()->build();
    }

    public function testItSavesNewValue(): void
    {
        $criterion = new Tracker_Report_AdditionalCriterion('comment', 'my text');

        $this->dao->expects($this->once())->method('save')->with(101, 'my text');
        $this->dao->expects($this->never())->method('delete');

        $this->saver->saveValueForReport($this->report, $criterion);
    }

    public function testItDeletesValueIfTextIsEmpty(): void
    {
        $criterion = new Tracker_Report_AdditionalCriterion('comment', '');

        $this->dao->expects($this->never())->method('save');
        $this->dao->expects($this->once())->method('delete')->with(101);

        $this->saver->saveValueForReport($this->report, $criterion);
    }
}
