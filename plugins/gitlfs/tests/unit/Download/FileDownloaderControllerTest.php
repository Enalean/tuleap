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

use League\Flysystem\FilesystemReader;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\NotFoundException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileDownloaderControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testNotFoundExceptionIsThrownWhenTheGitRepositoryCanNotBeFound(): void
    {
        $repository_factory        = $this->createStub(\GitRepositoryFactory::class);
        $file_downloader_controler = new FileDownloaderController(
            $repository_factory,
            $this->createStub(LFSObjectRetriever::class),
            $this->createStub(LFSObjectPathAllocator::class),
            $this->createStub(FilesystemReader::class),
            Prometheus::getInMemory()
        );

        $repository_factory->method('getRepositoryById')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $file_downloader_controler->process(
            $this->createStub(\HTTPRequest::class),
            $this->createStub(BaseLayout::class),
            ['repo_id' => '102']
        );
    }
}
