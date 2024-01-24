<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Instance;

use Tuleap\DB\DBFactory;
use Tuleap\MediawikiStandalone\Service\MediawikiFlavorUsageDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class OngoingInitializationsDaoTest extends TestIntegrationTestCase
{
    private OngoingInitializationsDao $dao;
    private \Project $project;

    protected function setUp(): void
    {
        $this->dao     = new OngoingInitializationsDao(new MediawikiFlavorUsageDao());
        $this->project = ProjectTestBuilder::aProject()->build();
    }

    public function testStartOngoingMigration(): void
    {
        self::assertFalse($this->dao->getStatus($this->project)->isOngoing());
        $this->dao->startInitialization($this->project);
        self::assertTrue($this->dao->getStatus($this->project)->isOngoing());

        // ignore already started initializations
        $this->dao->startInitialization($this->project);
        self::assertTrue($this->dao->getStatus($this->project)->isOngoing());
        self::assertFalse($this->dao->getStatus($this->project)->isError());
    }

    public function testError(): void
    {
        $this->dao->startInitialization($this->project);
        self::assertFalse($this->dao->getStatus($this->project)->isError());

        $this->dao->markAsError($this->project);
        self::assertTrue($this->dao->getStatus($this->project)->isError());
        self::assertFalse($this->dao->getStatus($this->project)->isOngoing());
    }

    public function testFinishInitialization(): void
    {
        $db         = DBFactory::getMainTuleapDBConnection()->getDB();
        $project_id = $this->project->getID();
        $db->run('INSERT INTO plugin_mediawiki_database (project_id, database_name) VALUES (?, "db_name")', $project_id);
        $this->dao->startInitialization($this->project);
        self::assertTrue($this->dao->getStatus($this->project)->isOngoing());

        $this->dao->finishInitialization($this->project);
        self::assertFalse($this->dao->getStatus($this->project)->isOngoing());
        self::assertEmpty($db->run('SELECT project_id FROM plugin_mediawiki_database WHERE project_id=?', $project_id));
    }
}
