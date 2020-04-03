<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\DB\DBTransactionExecutorWithConnection;

class VisitRecorder
{
    /**
     * @var RecentlyVisitedDao
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(RecentlyVisitedDao $dao, ?DBTransactionExecutor $transaction_executor = null)
    {
        $this->dao = $dao;
        $this->transaction_executor = $transaction_executor ?? new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
    }

    /**
     * @throws \DataAccessException
     */
    public function record(\PFUser $user, \Tracker_Artifact $artifact): void
    {
        if ($user->isAnonymous()) {
            return;
        }

        $this->transaction_executor->execute(function () use ($user, $artifact) {
            $this->dao->save($user->getId(), $artifact->getId(), $_SERVER['REQUEST_TIME']);
        });
    }
}
