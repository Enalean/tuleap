<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\JiraImporter\Worklog;

use PHPUnit\Framework\TestCase;

class WorklogCommentTest extends TestCase
{
    public function testItBuildsWorklogCommentFromAPIResponse(): void
    {
        $worklog_comment_api_reponse = [
            "version" => 1,
            "type"    => "doc",
            "content" => [
                [
                    "type"    => "paragraph",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "content 01",
                        ]
                    ]
                ],
                [
                    "type"    => "mediaSingle",
                    "content" => [
                        [
                            "type" => "media",
                        ]
                    ]
                ],
                [
                    "type"    => "paragraph",
                    "content" => []
                ],
                [
                    "type"    => "paragraph",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "content 02",
                        ]
                    ]
                ],
            ]
        ];

        $worklog_comment = WorklogComment::buildFromAPIResponse($worklog_comment_api_reponse);

        $this->assertSame("content 01 content 02", $worklog_comment->getCommentInTextFormat());
    }
}
