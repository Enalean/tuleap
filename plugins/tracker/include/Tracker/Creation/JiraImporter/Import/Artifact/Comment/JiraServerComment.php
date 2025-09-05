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

use DateTimeImmutable;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\ActiveJiraServerUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUser;

/**
 * @psalm-immutable
 */
final class JiraServerComment implements Comment
{
    public function __construct(
        private ActiveJiraServerUser $update_author,
        private DateTimeImmutable $date,
        private string $rendered_value,
    ) {
    }

    #[\Override]
    public static function buildFromAPIResponse(array $comment_response): self
    {
        if (
            ! isset($comment_response['updateAuthor']) ||
            ! isset($comment_response['updated']) ||
            ! isset($comment_response['body'])
        ) {
            throw new CommentAPIResponseNotWellFormedException();
        }

        return new self(
            ActiveJiraServerUser::buildFromPayload($comment_response['updateAuthor']),
            new DateTimeImmutable($comment_response['updated']),
            (string) $comment_response['body']
        );
    }

    #[\Override]
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    #[\Override]
    public function getRenderedValue(): string
    {
        return $this->rendered_value;
    }

    #[\Override]
    public function getUpdateAuthor(): JiraUser
    {
        return $this->update_author;
    }
}
