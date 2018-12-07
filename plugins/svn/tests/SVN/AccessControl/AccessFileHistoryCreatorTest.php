<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\AccessControl;

use Tuleap\SVN\Repository\Repository;
use TuleapTestCase;

require_once __DIR__ . '/../../bootstrap.php';

class AccessFileHistoryCreatorTest extends TuleapTestCase
{
    /**
     * @var AccessFileHistoryDao
     */
    private $access_file_dao;
    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var AccessFileHistoryCreator
     */
    private $creator;
    /**
     * @var AccessFileHistoryFactory
     */
    private $access_file_factory;

    public function setUp()
    {
        parent::setUp();

        $this->access_file_dao     = mock('Tuleap\SVN\AccessControl\AccessFileHistoryDao');
        $this->access_file_factory = mock('Tuleap\SVN\AccessControl\AccessFileHistoryFactory');
        $project_history_formatter = mock('Tuleap\SVN\Repository\ProjectHistoryFormatter');
        $project_history_dao       = mock('ProjectHistoryDao');

        $this->creator = new AccessFileHistoryCreator(
            $this->access_file_dao,
            $this->access_file_factory,
            $project_history_dao,
            $project_history_formatter
        );

        $this->repository = mock('Tuleap\SVN\Repository\Repository');
        stub($this->repository)->getProject()->returns(aMockProject()->withId(101)->build());

        $access_file_history = new NullAccessFileHistory($this->repository);
        stub($this->access_file_factory)->getLastVersion($this->repository)->returns($access_file_history);
    }

    public function itUpdatesAccessFile()
    {
        $new_access_file     = "[/tags]\n@members = r\n";
        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

        stub($this->access_file_factory)->getCurrentVersion($this->repository)->returns($current_access_file);
        expect($this->access_file_dao)->create()->once();
        stub($this->access_file_dao)->create()->returns(true);

        $this->creator->create($this->repository, $new_access_file, time());
    }

    public function itThrowsAnExceptionWhenAccessFileSaveFailed()
    {
        $new_access_file     = "[/tags]\n@members = r\n";
        $current_access_file = new AccessFileHistory(
            $this->repository,
            1,
            1,
            "[/] * = rw",
            time()
        );

        stub($this->access_file_factory)->getCurrentVersion($this->repository)->returns($current_access_file);
        expect($this->access_file_dao)->create()->once();
        stub($this->access_file_dao)->create()->returns(false);

        $this->expectException("Tuleap\SVN\AccessControl\CannotCreateAccessFileHistoryException");
        $this->creator->create($this->repository, $new_access_file, time());
    }
}
