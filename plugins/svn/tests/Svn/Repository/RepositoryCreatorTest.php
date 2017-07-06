<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Svn\Repository;

require_once __DIR__ . '/../../bootstrap.php';

class RepositoryCreatorTest extends \TuleapTestCase
{
    /**
     * @var \SystemEventManager
     */
    private $system_event_manager;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var RepositoryCreator
     */
    private $repository_creator;

    public function setUp()
    {
        parent::setUp();

        $this->system_event_manager = mock('SystemEventManager');
        $history_dao                = mock('ProjectHistoryDao');
        $dao                        = mock('Tuleap\Svn\Dao');
        $this->repository_creator   = new RepositoryCreator(
            $dao,
            $this->system_event_manager,
            $history_dao
        );

        $this->repository = new Repository(
            01,
            'repo01',
            '',
            '',
            aMockProject()->withId(101)->build()
        );

        stub($dao)->create()->returns(array(1));
    }

    public function itCreatesTheRepository()
    {
        stub($this->system_event_manager)->createEvent()->once();
        $this->repository_creator->create($this->repository);
    }
}
