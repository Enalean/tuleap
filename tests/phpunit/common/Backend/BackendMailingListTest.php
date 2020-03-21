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

declare(strict_types=1);

namespace Tuleap\Backend;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class BackendMailingListTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDeleteProjectMailingListsNothingToDelete() : void
    {
        $backend = \Mockery::mock(\BackendMailingList::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\MailingListDao::class);
        $dar = \Mockery::spy(\DataAccessResult::class);
        $dar->shouldReceive('isError')->andReturns(false);
        $dar->shouldReceive('getRow')->once()->andReturns(false);
        $dao->shouldReceive('searchByProject')->andReturns($dar);

        $backend->shouldReceive('_getMailingListDao')->once()->andReturns($dao);
        $backend->shouldReceive('deleteList')->never();
        $this->assertTrue($backend->deleteProjectMailingLists(1));
    }

    public function testDeleteProjectMailingListsDbDeleteFail() : void
    {
        $backend = \Mockery::mock(\BackendMailingList::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\MailingListDao::class);
        $dar = \Mockery::spy(\DataAccessResult::class);
        $dar->shouldReceive('isError')->andReturns(false);
        $dar->shouldReceive('getRow')->once()->andReturns(['group_list_id' => 10]);
        $dar->shouldReceive('getRow')->once()->andReturns(['group_list_id' => 12]);
        $dar->shouldReceive('getRow')->once()->andReturns(false);
        $dao->shouldReceive('searchByProject')->andReturns($dar);
        $dao->shouldReceive('deleteList')->andReturns(false);

        $backend->shouldReceive('_getMailingListDao')->times(3)->andReturns($dao);
        $backend->shouldReceive('deleteList')->never();
        $this->assertFalse($backend->deleteProjectMailingLists(1));
    }

    public function testDeleteProjectMailingListsSuccess() : void
    {
        $backend = \Mockery::mock(\BackendMailingList::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\MailingListDao::class);
        $dar = \Mockery::spy(\DataAccessResult::class);
        $dar->shouldReceive('isError')->andReturns(false);
        $dar->shouldReceive('getRow')->once()->andReturns(['group_list_id' => 10]);
        $dar->shouldReceive('getRow')->once()->andReturns(['group_list_id' => 12]);
        $dar->shouldReceive('getRow')->once()->andReturns(false);
        $dao->shouldReceive('searchByProject')->andReturns($dar);
        $dao->shouldReceive('deleteList')->andReturns(true);
        $backend->shouldReceive('_getMailingListDao')->andReturns($dao);
        $backend->shouldReceive('deleteList')->times(2)->andReturns(true);
        $this->assertTrue($backend->deleteProjectMailingLists(1));
    }
}
