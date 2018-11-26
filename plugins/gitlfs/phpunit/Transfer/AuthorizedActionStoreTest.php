<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\GitLFS\Transfer;

use PHPUnit\Framework\TestCase;
use Tuleap\GitLFS\Authorization\Action\AuthorizedAction;

class AuthorizedActionStoreTest extends TestCase
{
    public function testAuthorizedActionCanBeSavedAndRetrieved()
    {
        $authorized_action = \Mockery::mock(AuthorizedAction::class);

        $store = new AuthorizedActionStore();
        $store->keepAuthorizedAction($authorized_action);

        $this->assertSame($authorized_action, $store->getAuthorizedAction());
    }

    /**
     * @expectedException \LogicException
     */
    public function testTheStoreIsUsableForOnlyOneAuthorizedAction()
    {
        $store = new AuthorizedActionStore();
        $store->keepAuthorizedAction(\Mockery::mock(AuthorizedAction::class));
        $store->keepAuthorizedAction(\Mockery::mock(AuthorizedAction::class));
    }

    /**
     * @expectedException \LogicException
     */
    public function testTheStoreOnlyAcceptToBeQueriedWhenAnAuthorizedActionHasBeenSet()
    {
        (new AuthorizedActionStore)->getAuthorizedAction();
    }
}
