<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment;

use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Tuleap\ForgeConfigSandbox;

class AttachmentDownloaderTest extends TestCase
{
    use ForgeConfigSandbox;
    use MockeryPHPUnitIntegration;

    public function testItCreatesTheJiraImportFolder(): void
    {
        $tmp_folder = vfsStream::setup();
        ForgeConfig::set("tmp_dir", $tmp_folder->url());

        $client  = Mockery::mock(ClientInterface::class);
        $factory = Mockery::mock(RequestFactoryInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);

        $logger->shouldReceive("debug");

        $downloader = new AttachmentDownloader(
            $client,
            $factory,
            $logger
        );

        $factory->shouldReceive('createRequest')->once();
        $body = Mockery::mock(StreamInterface::class);
        $body->shouldReceive('getContents')->once()->andReturn('');
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn($body);
        $client->shouldReceive('sendRequest')->once()->andReturn($response);

        $attachment = new Attachment(
            10007,
            "file01.png",
            "image/png",
            "URL1",
            30
        );

        $downloaded_filname = $downloader->downloadAttachment($attachment);
        $this->assertTrue(is_dir($tmp_folder->url() . '/' . AttachmentDownloader::JIRA_TEMP_FOLDER . '/'));
        $this->assertTrue(is_file($tmp_folder->url() . '/' . AttachmentDownloader::JIRA_TEMP_FOLDER . '/' . $downloaded_filname));
    }
}
