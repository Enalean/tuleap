<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Document\DownloadFolderAsZip;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Document\Config\FileDownloadLimitsBuilder;
use Tuleap\Document\Tree\DocumentTreeProjectExtractor;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;

final class DocumentFolderZipStreamerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private DocumentFolderZipStreamer $controller;
    private MockObject&DocumentTreeProjectExtractor $project_extractor;

    protected function setUp(): void
    {
        $this->project_extractor = $this->createMock(DocumentTreeProjectExtractor::class);
        $user_manager            = $this->createMock(\UserManager::class);
        $user_manager->method('getCurrentUser')->willReturn(UserTestBuilder::aUser()->withId(110)->build());
        $logging_helper          = $this->createMock(ZipStreamerLoggingHelper::class);
        $notification_sender     = $this->createMock(ZipStreamMailNotificationSender::class);
        $size_is_allowed_checker = $this->createMock(FolderSizeIsAllowedChecker::class);
        $download_limits_builder = $this->createMock(FileDownloadLimitsBuilder::class);
        $this->controller        = new DocumentFolderZipStreamer(
            new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            $this->project_extractor,
            $user_manager,
            $logging_helper,
            $notification_sender,
            $size_is_allowed_checker,
            $download_limits_builder,
            $this->createMock(EmitterInterface::class)
        );
    }

    public function testItThrowsNotFoundWhenNoFolderID(): void
    {
        $this->project_extractor->method('getProject')->willReturn(new \Project(['group_id' => 101]));

        $this->expectException(NotFoundException::class);
        $this->controller->handle(new NullServerRequest());
    }
}
