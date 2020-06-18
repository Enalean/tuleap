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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment;

use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class CommentResponseRepresentationTest extends TestCase
{
    public function testItBuildsAResponseRepresentationFromAPIResponse(): void
    {
        $response = CommentResponseRepresentation::buildFromAPIResponse(
            [
                'startAt'    => 0,
                'maxResults' => 50,
                'total'      => 1,
                'comments'   => [
                    0 => [
                        "body" => "Comment 01",
                        "renderedBody" => "<p>Comment 01</p>",
                        "created" => "2020-04-21T11:36:46.601+0200",
                        "updated" => "2020-04-21T11:36:46.601+0200"
                    ]
                ]
            ]
        );

        $this->assertSame(50, $response->getMaxResults());
        $this->assertSame(1, $response->getTotal());
        $this->assertCount(1, $response->getComments());
    }

    public function testItThrowsAnExceptionIfAPIResponseIsNotWellFormed(): void
    {
        $this->expectException(JiraConnectionException::class);

        CommentResponseRepresentation::buildFromAPIResponse(
            [
                'startAt'    => 0,
                'maxResults' => 50,
                'total'      => 1,
            ]
        );

        $this->expectException(JiraConnectionException::class);

        CommentResponseRepresentation::buildFromAPIResponse(
            [
                'startAt'    => 0,
                'maxResults' => 50,
                'total'      => 1,
                'comments' => null
            ]
        );
    }
}
