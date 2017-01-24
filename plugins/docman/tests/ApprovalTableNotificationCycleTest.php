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

// Generic
Mock::generatePartial('Docman_ApprovalTableNotificationCycle', 'Docman_ApprovalTableNotificationCycleTest', array('_getReviewerDao', '_getMail', '_getUserManager', '_getUserById', 'getReviewUrl'));

// For  testLastReviewerApprove


Mock::generate('PFUser');
Mock::generate('Mail');
Mock::generate('Docman_ApprovalReviewer');
Mock::generate('Docman_ApprovalTable');


class ApprovalTableNotificationCycleTest extends TuleapTestCase {

    /**
     * first:  approve
     * second: reject
     * last: approve
     */
    function testGetTableStateReject() {
        $reviewers[0] = new MockDocman_ApprovalReviewer($this);
        $reviewers[0]->setReturnValue('getState', PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewers[1] = new MockDocman_ApprovalReviewer($this);
        $reviewers[1]->setReturnValue('getState', PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);

        $reviewers[2] = new MockDocman_ApprovalReviewer($this);
        $reviewers[2]->setReturnValue('getState', PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewerIterator = new ArrayIterator($reviewers);

        $table = new MockDocman_ApprovalTable($this);
        $table->setReturnReference('getReviewerIterator', $reviewerIterator);

        $cycle = new Docman_ApprovalTableNotificationCycleTest($this);
        $cycle->setTable($table);

        $this->assertEqual($cycle->getTableState(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
    }

    /**
     * first:  approve
     * second: notyet
     * last: approve
     */
    function testGetTableStateNotYet() {
        $reviewers[0] = new MockDocman_ApprovalReviewer($this);
        $reviewers[0]->setReturnValue('getState', PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewers[1] = new MockDocman_ApprovalReviewer($this);
        $reviewers[1]->setReturnValue('getState', PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);

        $reviewers[2] = new MockDocman_ApprovalReviewer($this);
        $reviewers[2]->setReturnValue('getState', PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewerIterator = new ArrayIterator($reviewers);

        $table = new MockDocman_ApprovalTable($this);
        $table->setReturnReference('getReviewerIterator', $reviewerIterator);

        $cycle = new Docman_ApprovalTableNotificationCycleTest($this);
        $cycle->setTable($table);

        $this->assertEqual($cycle->getTableState(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);
    }

    /**
     * first:  approve
     * second: will not review
     * last: approve
     */
    function testGetTableStateWillNotReview() {
        $reviewers[0] = new MockDocman_ApprovalReviewer($this);
        $reviewers[0]->setReturnValue('getState', PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewers[1] = new MockDocman_ApprovalReviewer($this);
        $reviewers[1]->setReturnValue('getState', PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED);

        $reviewers[2] = new MockDocman_ApprovalReviewer($this);
        $reviewers[2]->setReturnValue('getState', PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        $reviewerIterator = new ArrayIterator($reviewers);

        $table = new MockDocman_ApprovalTable($this);
        $table->setReturnReference('getReviewerIterator', $reviewerIterator);

        $cycle = new Docman_ApprovalTableNotificationCycleTest($this);
        $cycle->setTable($table);

        $this->assertEqual($cycle->getTableState(), PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
    }

    function testLastReviewerApprove() {
        Mock::generatePartial('Docman_ApprovalTableNotificationCycle', 'Docman_ApprovalTableNotificationCycleTest2', array('_getReviewerDao', '_getMail', '_getUserManager', '_getUserById', 'getReviewUrl', 'sendNotifTableApproved', 'notifyNextReviewer'));
        $cycle = new Docman_ApprovalTableNotificationCycleTest2($this);

        $mail = new MockMail($this);
        //php5: this will works without having to explicitly return reference.
        //$mail->expectOnce('send');
        $cycle->setReturnReference('sendNotifTableApproved', $mail);

        $cycle->expectOnce('sendNotifTableApproved');
        $cycle->expectNever('notifyNextReviewer');

        $reviewer = new MockDocman_ApprovalReviewer($this);
        $isLastReviewer = true;
        $withComments = "";
        $cycle->reviewerApprove($reviewer, $isLastReviewer, $withComments);
    }

}
