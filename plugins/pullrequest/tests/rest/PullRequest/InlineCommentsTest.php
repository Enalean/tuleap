<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

final class InlineCommentsTest extends \RestBase
{
    public function testOptions(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'pull_requests/1/inline-comments')
        );
        self::assertEqualsCanonicalizing(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOptionsInlineComment(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'pull_request_inline_comments/1')
        );
        self::assertEqualsCanonicalizing(['OPTIONS', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testEditAnInlineComment(): void
    {
        $new_content = 'unmoated ludicrosity';
        $response    = $this->getResponse(
            $this->request_factory
                ->createRequest('PATCH', 'pull_request_inline_comments/1')
                ->withBody($this->stream_factory->createStream(json_encode(['content' => $new_content])))
        );
        self::assertSame(200, $response->getStatusCode());

        $edited_comment = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($new_content, $edited_comment['content']);
        self::assertNotNull($edited_comment['last_edition_date']);
    }
}
