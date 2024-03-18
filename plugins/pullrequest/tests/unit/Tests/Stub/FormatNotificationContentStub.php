<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\Notification\FormatNotificationContent;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;

final class FormatNotificationContentStub implements FormatNotificationContent
{
    private function __construct(private readonly ?string $formatted_content)
    {
    }

    public static function withDefault(): self
    {
        return new self(null);
    }

    public static function withFormattedContent(string $formatted_content): self
    {
        return new self($formatted_content);
    }

    public function getFormattedAndPurifiedNotificationContent(PullRequest $pull_request, TimelineComment $comment): string
    {
        return $this->formatted_content ?? $comment->getContent();
    }
}
