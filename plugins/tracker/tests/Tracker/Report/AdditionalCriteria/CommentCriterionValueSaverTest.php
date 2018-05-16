<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tracker_Report_AdditionalCriterion;
use TuleapTestCase;

require_once __DIR__.'/../../../bootstrap.php';

class CommentCriterionValueSaverTest extends TuleapTestCase
{
    /**
     * @var CommentCriterionValueSaver;
     */
    private $saver;

    public function setUp()
    {
        parent::setUp();

        $this->dao   = mock('Tuleap\Tracker\Report\AdditionalCriteria\CommentDao');
        $this->saver = new CommentCriterionValueSaver($this->dao);

        $this->report = stub('Tracker_Report')->getId()->returns(1);
    }

    public function itSavesNewValue()
    {
        $criterion = new Tracker_Report_AdditionalCriterion('comment', 'my text');

        expect($this->dao)->save(1, 'my text')->once();
        expect($this->dao)->delete()->never();

        $this->saver->saveValueForReport($this->report, $criterion);
    }

    public function itDeletesValueIfTextIsEmpty()
    {
        $criterion = new Tracker_Report_AdditionalCriterion('comment', '');

        expect($this->dao)->save()->never();
        expect($this->dao)->delete(1)->once();

        $this->saver->saveValueForReport($this->report, $criterion);
    }
}
