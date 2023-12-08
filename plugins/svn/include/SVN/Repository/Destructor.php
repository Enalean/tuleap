<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\SVN\Repository;

use Psr\Log\LoggerInterface;
use Tuleap\SVN\Dao;
use Tuleap\SVNCore\Repository;

class Destructor
{
    /** @var Dao */
    private $dao;
    /** @var  LoggerInterface */
    private $logger;

    public function __construct(Dao $dao, LoggerInterface $logger)
    {
        $this->dao    = $dao;
        $this->logger = $logger;
    }

    public function delete(Repository $repository)
    {
        if (! $this->dao->delete($repository->getId())) {
            $this->logger->error('Unable to delete repository:' . $repository->getName() . ' from database');
        } else {
            $this->logger->error('Repository:' . $repository->getName() . ' deleted from database with success');
        }
    }
}
