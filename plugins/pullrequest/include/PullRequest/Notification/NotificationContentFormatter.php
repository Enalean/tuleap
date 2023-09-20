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

namespace Tuleap\PullRequest\Notification;

use GitRepositoryFactory;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;

final class NotificationContentFormatter implements FormatNotificationContent
{
    public function __construct(
        private readonly ContentInterpretor $content_interpreter,
        private readonly GitRepositoryFactory $repository_factory,
        private readonly \Codendi_HTMLPurifier $purifier,
    ) {
    }

    public function getFormattedAndPurifiedNotificationContent(PullRequest $pull_request, TimelineComment $comment): string
    {
        if ($comment->getFormat() === TimelineComment::FORMAT_TEXT) {
            return $this->getPurifyTextContent($comment->getContent());
        }

        $repository = $this->repository_factory->getRepositoryById($pull_request->getRepositoryId());
        if (! $repository) {
            return $this->getPurifyTextContent($comment->getContent());
        }

        return $this->content_interpreter->getInterpretedContentWithReferences(
            $comment->getContent(),
            (int) $repository->getProjectId(),
        );
    }

    private function getPurifyTextContent(string $text_content): string
    {
        return nl2br($this->purifier->purify($text_content, \Codendi_HTMLPurifier::CONFIG_CONVERT_HTML));
    }
}
