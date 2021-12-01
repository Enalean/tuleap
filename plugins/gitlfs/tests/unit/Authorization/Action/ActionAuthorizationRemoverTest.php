<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use ArrayObject;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;

class ActionAuthorizationRemoverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDeletionOfActionsOldWorkingFiles(): void
    {
        $dao            = \Mockery::mock(ActionAuthorizationDAO::class);
        $filesystem     = \Mockery::mock(FilesystemOperator::class);
        $path_allocator = \Mockery::mock(LFSObjectPathAllocator::class);

        $remover = new ActionAuthorizationRemover($dao, $filesystem, $path_allocator);

        $current_time = new \DateTimeImmutable('04-12-2018', new \DateTimeZone('UTC'));

        $path_allocator->shouldReceive('getBasePathForSaveInProgressObject')->andReturns('in-progress/');
        $path_allocator->shouldReceive('getBasePathForReadyToBeAvailableObject')->andReturns('ready/');

        $filesystem->shouldReceive('listContents')->with('in-progress/')->andReturns(
            new DirectoryListing(
                new ArrayObject(
                    [
                        new FileAttributes('in-progress/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
                        new FileAttributes('in-progress/cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc'),
                    ]
                )
            )
        );
        $filesystem->shouldReceive('listContents')->with('ready/')->andReturns(
            new DirectoryListing(
                new ArrayObject(
                    [
                        new FileAttributes('ready/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
                        new FileAttributes('ready/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'),
                    ]
                )
            )
        );
        $dao->shouldReceive('searchExistingOIDsForAuthorizedActionByExpirationAndOIDs')->andReturns([
            'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
        ]);

        $dao->shouldReceive('deleteByExpirationDate')->once();
        $filesystem->shouldReceive('deleteDirectory')->andReturns(true)->times(3);

        $remover->deleteExpired($current_time);
    }
}
