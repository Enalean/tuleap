<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Download;

use League\Flysystem\FilesystemInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\NotFoundException;

final class FileDownloaderControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testNotFoundExceptionIsThrownWhenTheGitRepositoryCanNotBeFound(): void
    {
        $repository_factory        = \Mockery::mock(\GitRepositoryFactory::class);
        $file_downloader_controler = new FileDownloaderController(
            $repository_factory,
            \Mockery::mock(LFSObjectRetriever::class),
            \Mockery::mock(LFSObjectPathAllocator::class),
            \Mockery::mock(FilesystemInterface::class),
            Prometheus::getInMemory()
        );

        $repository_factory->shouldReceive('getRepositoryById')->andReturnNull();

        $this->expectException(NotFoundException::class);
        $file_downloader_controler->process(
            \Mockery::mock(\HTTPRequest::class),
            \Mockery::mock(BaseLayout::class),
            ['repo_id' => '102']
        );
    }
}
