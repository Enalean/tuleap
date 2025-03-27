<?php
/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\ApprovalTable;

use Docman_ApprovalReviewer;
use Docman_ApprovalTableItem;
use Docman_ApprovalTableNotificationCycle;
use MailNotificationBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ApprovalTableNotificationCycleTest extends TestCase
{
    /**
     * first:  approve
     * second: reject
     * last: approve
     */
    public function testGetTableStateReject(): void
    {
        $reviewers[0] = new Docman_ApprovalReviewer();
        $reviewers[0]->setState(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
        $reviewers[1] = new Docman_ApprovalReviewer();
        $reviewers[1]->setState(PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
        $reviewers[2] = new Docman_ApprovalReviewer();
        $reviewers[2]->setState(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
        $table            = new Docman_ApprovalTableItem();
        $table->reviewers = $reviewers;

        $cycle = new Docman_ApprovalTableNotificationCycle($this->createMock(MailNotificationBuilder::class));
        $cycle->setTable($table);

        self::assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED, $cycle->getTableState());
    }

    /**
     * first:  approve
     * second: notyet
     * last: approve
     */
    public function testGetTableStateNotYet(): void
    {
        $reviewers[0] = new Docman_ApprovalReviewer();
        $reviewers[0]->setState(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
        $reviewers[1] = new Docman_ApprovalReviewer();
        $reviewers[1]->setState(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);
        $reviewers[2] = new Docman_ApprovalReviewer();
        $reviewers[2]->setState(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
        $table            = new Docman_ApprovalTableItem();
        $table->reviewers = $reviewers;

        $cycle = new Docman_ApprovalTableNotificationCycle($this->createMock(MailNotificationBuilder::class));
        $cycle->setTable($table);

        self::assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET, $cycle->getTableState());
    }

    /**
     * first:  approve
     * second: will not review
     * last: approve
     */
    public function testGetTableStateWillNotReview(): void
    {
        $reviewers[0] = new Docman_ApprovalReviewer();
        $reviewers[0]->setState(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
        $reviewers[1] = new Docman_ApprovalReviewer();
        $reviewers[1]->setState(PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED);
        $reviewers[2] = new Docman_ApprovalReviewer();
        $reviewers[2]->setState(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED);
        $table            = new Docman_ApprovalTableItem();
        $table->reviewers = $reviewers;

        $cycle = new Docman_ApprovalTableNotificationCycle($this->createMock(MailNotificationBuilder::class));
        $cycle->setTable($table);

        self::assertEquals(PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED, $cycle->getTableState());
    }

    public function testLastReviewerApprove(): void
    {
        $cycle = $this->createPartialMock(Docman_ApprovalTableNotificationCycle::class, [
            'sendNotifTableApproved',
            'notifyNextReviewer',
        ]);
        $cycle->expects($this->once())->method('sendNotifTableApproved')->willReturn(true);
        $cycle->expects(self::never())->method('notifyNextReviewer');
        $cycle->reviewerApprove(new Docman_ApprovalReviewer(), true, '');
    }
}
