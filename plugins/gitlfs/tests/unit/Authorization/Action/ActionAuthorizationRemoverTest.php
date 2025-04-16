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

declare(strict_types=1);

namespace Tuleap\GitLFS\Authorization\Action;

use ArrayObject;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ActionAuthorizationRemoverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testDeletionOfActionsOldWorkingFiles(): void
    {
        $dao            = $this->createMock(ActionAuthorizationDAO::class);
        $filesystem     = $this->createMock(FilesystemOperator::class);
        $path_allocator = $this->createStub(LFSObjectPathAllocator::class);

        $remover = new ActionAuthorizationRemover($dao, $filesystem, $path_allocator);

        $current_time = new \DateTimeImmutable('04-12-2018', new \DateTimeZone('UTC'));

        $path_allocator->method('getBasePathForSaveInProgressObject')->willReturn('in-progress/');
        $path_allocator->method('getBasePathForReadyToBeAvailableObject')->willReturn('ready/');

        $filesystem->method('listContents')->willReturnCallback(
            fn (string $location): DirectoryListing => match ($location) {
                'in-progress/' => new DirectoryListing(
                    new ArrayObject(
                        [
                            new FileAttributes('in-progress/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
                            new FileAttributes('in-progress/cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc'),
                        ]
                    )
                ),
                'ready/' => new DirectoryListing(
                    new ArrayObject(
                        [
                            new FileAttributes('ready/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
                            new FileAttributes('ready/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'),
                        ]
                    )
                )
            }
        );
        $dao->method('searchExistingOIDsForAuthorizedActionByExpirationAndOIDs')->willReturn([
            'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc',
        ]);

        $dao->expects($this->once())->method('deleteByExpirationDate');
        $filesystem->expects($this->exactly(3))->method('deleteDirectory');

        $remover->deleteExpired($current_time);
    }
}
