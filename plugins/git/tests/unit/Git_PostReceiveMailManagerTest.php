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

declare(strict_types=1);

namespace Tuleap\Git;

use Git_Backend_Interface;
use Git_PostReceiveMailDao;
use Git_PostReceiveMailManager;
use GitRepository;
use Tuleap\Test\PHPUnit\TestCase;

// phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Git_PostReceiveMailManagerTest extends TestCase
{
    public function testRemoveMailByRepository(): void
    {
        $prm = $this->createPartialMock(Git_PostReceiveMailManager::class, []);

        $dao      = $this->createMock(Git_PostReceiveMailDao::class);
        $prm->dao = $dao;

        $repo    = $this->createMock(GitRepository::class);
        $backend = $this->createMock(Git_Backend_Interface::class);
        $repo->method('getId');
        $repo->method('getBackend')->willReturn($backend);

        $prm->dao->method('removeNotification')->willReturn(true);

        $repo->expects($this->once())->method('loadNotifiedMails');
        $backend->expects($this->once())->method('changeRepositoryMailingList');

        $prm->removeMailByRepository($repo, 'codendiadm@codendi.org');
    }
}
