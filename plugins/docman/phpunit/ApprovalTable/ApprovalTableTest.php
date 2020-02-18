<?php
/*
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ApprovalTableTest extends TestCase
{

    public function testTableStateRejected(): void
    {
        // Std case
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 1;
        $row['nb_approved'] = 4;
        $row['nb_declined'] = 0;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED, $t1->getApprovalState());

        // Even if some dummy things are returned
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 1;
        $row['nb_approved'] = 5;
        $row['nb_declined'] = 0;
        $t2 = new Docman_ApprovalTableItem();
        $t2->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED, $t2->getApprovalState());

        $row['nb_reviewers'] = 0;
        $row['rejected'] = 1;
        $row['nb_approved'] = 0;
        $row['nb_declined'] = 0;
        $t3 = new Docman_ApprovalTableItem();
        $t3->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED, $t3->getApprovalState());
    }

    public function testTableStateApproved(): void
    {
        // Std case
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 5;
        $row['nb_declined'] = 0;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED, $t1->getApprovalState());

        // Std case with only one person
        $row['nb_reviewers'] = 1;
        $row['rejected'] = 0;
        $row['nb_approved'] = 1;
        $row['nb_declined'] = 0;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED, $t1->getApprovalState());

        // Std case with 2 people
        $row['nb_reviewers'] = 2;
        $row['rejected'] = 0;
        $row['nb_approved'] = 1;
        $row['nb_declined'] = 1;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED, $t1->getApprovalState());

        // Will not review
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 3;
        $row['nb_declined'] = 2;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED, $t1->getApprovalState());
    }

    public function testTableStateNotYet(): void
    {
        // Std case
        $row['nb_reviewers'] = 0;
        $row['rejected'] = 0;
        $row['nb_approved'] = 0;
        $row['nb_declined'] = 0;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET, $t1->getApprovalState());

        // Almost everybody approved
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 4;
        $row['nb_declined'] = 0;
        $t2 = new Docman_ApprovalTableItem();
        $t2->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET, $t2->getApprovalState());

        // Some approved and some declined
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 3;
        $row['nb_declined'] = 1;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET, $t1->getApprovalState());

        // All declined
        $row['nb_reviewers'] = 5;
        $row['rejected'] = 0;
        $row['nb_approved'] = 0;
        $row['nb_declined'] = 5;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET, $t1->getApprovalState());

        // One reviewer declined
        $row['nb_reviewers'] = 1;
        $row['rejected'] = 0;
        $row['nb_approved'] = 0;
        $row['nb_declined'] = 1;
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET, $t1->getApprovalState());
    }

    public function testNoData(): void
    {
        // Std case
        $row = array();
        $t1 = new Docman_ApprovalTableItem();
        $t1->initFromRow($row);
        $this->assertNull($t1->getApprovalState());

        $row = array('nb_reviewers');
        $t2 = new Docman_ApprovalTableItem();
        $t2->initFromRow($row);
        $this->assertNull($t2->getApprovalState());

        $row = array('nb_reviewers', 'rejected');
        $t3 = new Docman_ApprovalTableItem();
        $t3->initFromRow($row);
        $this->assertNull($t3->getApprovalState());

        $row = array('nb_reviewers', 'rejected', 'nb_approved');
        $t4 = new Docman_ApprovalTableItem();
        $t4->initFromRow($row);
        $this->assertNull($t4->getApprovalState());

        $row = array('nb_reviewers', 'rejected' => 0, 'nb_approved');
        $t5 = new Docman_ApprovalTableItem();
        $t5->initFromRow($row);
        $this->assertNull($t5->getApprovalState());

        $row = array('nb_reviewers' => 0, 'rejected' => 0, 'nb_approved');
        $t6 = new Docman_ApprovalTableItem();
        $t6->initFromRow($row);
        $this->assertNull($t6->getApprovalState());

        $row = array('nb_reviewers', 'rejected' => 0, 'nb_approved' => 0);
        $t7 = new Docman_ApprovalTableItem();
        $t7->initFromRow($row);
        $this->assertNull($t7->getApprovalState());

        $row = array('nb_reviewers' => 0, 'rejected', 'nb_approved' => 0);
        $t8 = new Docman_ApprovalTableItem();
        $t8->initFromRow($row);
        $this->assertNull($t8->getApprovalState());
    }
}
