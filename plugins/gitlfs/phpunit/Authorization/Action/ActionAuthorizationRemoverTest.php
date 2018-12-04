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

namespace Tuleap\GitLFS\Authorization\Action;

use League\Flysystem\FilesystemInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;

class ActionAuthorizationRemoverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDeletionOfActionsOldWorkingFiles()
    {
        $dao            = \Mockery::mock(ActionAuthorizationDAO::class);
        $filesystem     = \Mockery::mock(FilesystemInterface::class);
        $path_allocator = \Mockery::mock(LFSObjectPathAllocator::class);

        $remover = new ActionAuthorizationRemover($dao, $filesystem, $path_allocator);

        $current_time = new \DateTimeImmutable('04-12-2018', new \DateTimeZone('UTC'));

        $path_allocator->shouldReceive('getBasePathForSaveInProgressObject')->andReturns('in-progress/');
        $path_allocator->shouldReceive('getBasePathForReadyToBeAvailableObject')->andReturns('ready/');

        $filesystem->shouldReceive('listContents')->with('in-progress/')->andReturns([
            ['path' => 'in-progress/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
            ['path' => 'in-progress/cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc']
        ]);
        $filesystem->shouldReceive('listContents')->with('ready/')->andReturns([
            ['path' => 'ready/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
            ['path' => 'ready/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb']
        ]);
        $dao->shouldReceive('searchExistingOIDsForAuthorizedActionByExpirationAndOIDs')->andReturns([
            'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc'
        ]);

        $dao->shouldReceive('deleteByExpirationDate')->once();
        $filesystem->shouldReceive('deleteDir')->andReturns(true)->times(3);

        $remover->deleteExpired($current_time);
    }
}
