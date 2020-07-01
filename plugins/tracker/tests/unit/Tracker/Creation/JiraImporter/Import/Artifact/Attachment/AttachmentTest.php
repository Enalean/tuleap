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

use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    public function testItBuildsAnAttachmentFromIssueRESTResponseAPI(): void
    {
        $response = [
            "id" => "10001",
            "filename" => "file01.png",
            "mimeType" => "image/png",
            "created" => "2020-03-25T14:10:10.823+0100",
            "content" => "URL/file01.png",
            "size"    => "30"
        ];

        $attachment = Attachment::buildFromIssueAPIResponse($response);

        $this->assertSame(10001, $attachment->getId());
        $this->assertSame("file01.png", $attachment->getFilename());
        $this->assertSame("image/png", $attachment->getMimeType());
        $this->assertSame("URL/file01.png", $attachment->getContentUrl());
        $this->assertSame(30, $attachment->getSize());
    }
}
