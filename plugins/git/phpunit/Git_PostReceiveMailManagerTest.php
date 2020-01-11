<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of tuleap.
 *
 * tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once 'bootstrap.php';

final class Git_PostReceiveMailManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRemoveMailByRepository(): void
    {
        $prm = \Mockery::mock(\Git_PostReceiveMailManager::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $dao = \Mockery::mock(Git_PostReceiveMailDao::class);
        $prm->dao = $dao;

        $repo = \Mockery::spy(\GitRepository::class);

        $backend = \Mockery::spy(\GitBackend::class);
        $repo->shouldReceive('getBackend')->andReturn($backend);

        $prm->dao->shouldReceive('removeNotification')->andReturnTrue();

        $repo->shouldReceive('loadNotifiedMails')->once();
        $backend->shouldReceive('changeRepositoryMailingList')->once();

        $prm->removeMailByRepository($repo, "codendiadm@codendi.org");
    }
}
