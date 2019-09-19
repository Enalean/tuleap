<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

Mock::generatePartial(
    'BackendMailingList',
    'BackendMailingList_TestVersion',
    array('_getMailingListDao',
    'deleteList')
);

Mock::generate('MailingListDao');
Mock::generate('DataAccessResult');
Mock::generate('MailingList');

class BackendMailingListTest extends TuleapTestCase
{

    function testDeleteProjectMailingListsNothingToDelete()
    {
        $backend = new BackendMailingList_TestVersion();
        $dao = new MockMailingListDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', false);
        $dar->setReturnValueAt(0, 'getRow', false);
        $dao->setReturnValue('searchByProject', $dar);
        $backend->setReturnValue('_getMailingListDao', $dao);

        $backend->expectCallCount('_getMailingListDao', 1);
        $backend->expectCallCount('deleteList', 0);
        $this->assertTrue($backend->deleteProjectMailingLists(1));
    }

    function testDeleteProjectMailingListsDbDeleteFail()
    {
        $backend = new BackendMailingList_TestVersion();
        $dao = new MockMailingListDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', false);
        $dar->setReturnValueAt(0, 'getRow', ['group_list_id' => 10]);
        $dar->setReturnValueAt(1, 'getRow', ['group_list_id' => 12]);
        $dar->setReturnValueAt(2, 'getRow', false);
        $dao->setReturnValue('searchByProject', $dar);
        $dao->setReturnValue('deleteList', false);
        $backend->setReturnValue('_getMailingListDao', $dao);

        $backend->expectCallCount('_getMailingListDao', 3);
        $backend->expectCallCount('deleteList', 0);
        $this->assertFalse($backend->deleteProjectMailingLists(1));
    }

    function testDeleteProjectMailingListsSuccess()
    {
        $backend = new BackendMailingList_TestVersion();
        $dao = new MockMailingListDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', false);
        $dar->setReturnValueAt(0, 'getRow', ['group_list_id' => 10]);
        $dar->setReturnValueAt(1, 'getRow', ['group_list_id' => 12]);
        $dar->setReturnValueAt(2, 'getRow', false);
        $dao->setReturnValue('searchByProject', $dar);
        $dao->setReturnValue('deleteList', true);
        $backend->setReturnValue('_getMailingListDao', $dao);

        $backend->expectCallCount('deleteList', 2);
        $backend->setReturnValue('deleteList', true);
        $this->assertTrue($backend->deleteProjectMailingLists(1));
    }
}
