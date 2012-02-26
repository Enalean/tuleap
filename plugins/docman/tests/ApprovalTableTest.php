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

require_once(dirname(__FILE__).'/../include/Docman_ApprovalTable.class.php');


class ApprovalTableTest extends UnitTestCase {

    function testTableStateRejected() {
        // Std case
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 1;
        $row['nb_approved'] = 4;
        $row['nb_declined'] = 0;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEqual($t1->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);

        // Even if some dummy things are returned
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 1;
        $row['nb_approved'] = 5;
        $row['nb_declined'] = 0;
        $t2 = new Docman_ApprovalTableItem();
        $t2->initFromRow($row);
        $this->assertEqual($t2->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);

        $row['nb_reviewers'] = 0;
        $row['rejected'] = 1;
        $row['nb_approved'] = 0;
        $row['nb_declined'] = 0;
        $t3 = new Docman_ApprovalTableItem();
        $t3->initFromRow($row);
        $this->assertEqual($t3->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
    }

    function testTableStateApproved() {
        // Std case
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 5;
        $row['nb_declined'] = 0;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEqual($t1->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        // Std case with only one person
        $row['nb_reviewers'] = 1;
        $row['rejected'] = 0;
        $row['nb_approved'] = 1;
        $row['nb_declined'] = 0;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEqual($t1->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        // Std case with 2 people
        $row['nb_reviewers'] = 2;
        $row['rejected'] = 0;
        $row['nb_approved'] = 1;
        $row['nb_declined'] = 1;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEqual($t1->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);

        // Will not review
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 3;
        $row['nb_declined'] = 2;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEqual($t1->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
    }

    function testTableStateNotYet() {
        // Std case
        $row['nb_reviewers'] = 0;
        $row['rejected'] = 0;
        $row['nb_approved'] = 0;
        $row['nb_declined'] = 0;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEqual($t1->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);

        // Almost everybody approved
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 4;
        $row['nb_declined'] = 0;
        $t2 = new Docman_ApprovalTableItem();
        $t2->initFromRow($row);
        $this->assertEqual($t2->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);

        // Some approved and some declined
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 3;
        $row['nb_declined'] = 1;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEqual($t1->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);

        // All declined
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 0;
        $row['nb_declined'] = 5;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEqual($t1->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);

        // One reviewer declined
        $row['nb_reviewers'] = 1;
        $row['rejected'] = 0;
        $row['nb_approved'] = 0;
        $row['nb_declined'] = 1;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEqual($t1->getApprovalState(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);
    }

    function testNoData() {
        // Std case
        $row = array();
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertIdentical($t1->getApprovalState(), null);

        $row = array('nb_reviewers');
        $t2 = new Docman_ApprovalTableItem();
        $t2->initFromRow($row);
        $this->assertIdentical($t2->getApprovalState(), null);

        $row = array('nb_reviewers', 'rejected');
        $t3 = new Docman_ApprovalTableItem();
        $t3->initFromRow($row);
        $this->assertIdentical($t3->getApprovalState(), null);

        $row = array('nb_reviewers', 'rejected', 'nb_approved');
        $t4 = new Docman_ApprovalTableItem();
        $t4->initFromRow($row);
        $this->assertIdentical($t4->getApprovalState(), null);

        $row = array('nb_reviewers', 'rejected' => 0, 'nb_approved');
        $t5 = new Docman_ApprovalTableItem();
        $t5->initFromRow($row);
        $this->assertIdentical($t5->getApprovalState(), null);

        $row = array('nb_reviewers' => 0, 'rejected' => 0, 'nb_approved');
        $t6 = new Docman_ApprovalTableItem();
        $t6->initFromRow($row);
        $this->assertIdentical($t6->getApprovalState(), null);

        $row = array('nb_reviewers', 'rejected' => 0, 'nb_approved' => 0);
        $t7 = new Docman_ApprovalTableItem();
        $t7->initFromRow($row);
        $this->assertIdentical($t7->getApprovalState(), null);

        $row = array('nb_reviewers' => 0, 'rejected', 'nb_approved' => 0);
        $t8 = new Docman_ApprovalTableItem();
        $t8->initFromRow($row);
        $this->assertIdentical($t8->getApprovalState(), null);
    }
}

?>
