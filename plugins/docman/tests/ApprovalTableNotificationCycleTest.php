<?php
/*
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'bootstrap.php';


class ApprovalTableNotificationCycleTest extends TuleapTestCase
{

    /**
     * first:  approve
     * second: reject
     * last: approve
     */
    function testGetTableStateReject()
    {
        $reviewers[0] = \Mockery::spy(Docman_ApprovalReviewer::class);
        $reviewers[0]->shouldReceive('getState')->andReturns(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewers[1] = \Mockery::spy(Docman_ApprovalReviewer::class);
        $reviewers[1]->shouldReceive('getState')->andReturns(PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);

        $reviewers[2] = \Mockery::spy(Docman_ApprovalReviewer::class);
        $reviewers[2]->shouldReceive('getState')->andReturns(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewerIterator = new ArrayIterator($reviewers);

        $table = \Mockery::spy(Docman_ApprovalTable::class);
        $table->shouldReceive('getReviewerIterator')->andReturns($reviewerIterator);

        $cycle = \Mockery::mock(Docman_ApprovalTableNotificationCycle::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $cycle->setTable($table);

        $this->assertEqual($cycle->getTableState(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
    }

    /**
     * first:  approve
     * second: notyet
     * last: approve
     */
    function testGetTableStateNotYet()
    {
        $reviewers[0] = \Mockery::spy(Docman_ApprovalReviewer::class);
        $reviewers[0]->shouldReceive('getState')->andReturns(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewers[1] = \Mockery::spy(Docman_ApprovalReviewer::class);
        $reviewers[1]->shouldReceive('getState')->andReturns(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);

        $reviewers[2] = \Mockery::spy(Docman_ApprovalReviewer::class);
        $reviewers[2]->shouldReceive('getState')->andReturns(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewerIterator = new ArrayIterator($reviewers);

        $table = \Mockery::spy(Docman_ApprovalTable::class);
        $table->shouldReceive('getReviewerIterator')->andReturns($reviewerIterator);

        $cycle = \Mockery::mock(Docman_ApprovalTableNotificationCycle::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $cycle->setTable($table);

        $this->assertEqual($cycle->getTableState(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);
    }

    /**
     * first:  approve
     * second: will not review
     * last: approve
     */
    function testGetTableStateWillNotReview()
    {
        $reviewers[0] = \Mockery::spy(Docman_ApprovalReviewer::class);
        $reviewers[0]->shouldReceive('getState')->andReturns(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewers[1] = \Mockery::spy(Docman_ApprovalReviewer::class);
        $reviewers[1]->shouldReceive('getState')->andReturns(PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED);

        $reviewers[2] = \Mockery::spy(Docman_ApprovalReviewer::class);
        $reviewers[2]->shouldReceive('getState')->andReturns(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewerIterator = new ArrayIterator($reviewers);

        $table = \Mockery::spy(Docman_ApprovalTable::class);
        $table->shouldReceive('getReviewerIterator')->andReturns($reviewerIterator);

        $cycle = \Mockery::mock(Docman_ApprovalTableNotificationCycle::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $cycle->setTable($table);

        $this->assertEqual($cycle->getTableState(), PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
    }

    function testLastReviewerApprove()
    {
        $cycle = \Mockery::mock(Docman_ApprovalTableNotificationCycle::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $mail = \Mockery::spy(Mail::class);
        $cycle->shouldReceive('sendNotifTableApproved')->once()->andReturns($mail);
        $cycle->shouldReceive('notifyNextReviewer')->never();
        $reviewer = \Mockery::spy(Docman_ApprovalReviewer::class);
        $isLastReviewer = true;
        $withComments = "";
        $cycle->reviewerApprove($reviewer, $isLastReviewer, $withComments);
    }
}
