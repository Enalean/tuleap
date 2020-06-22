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

/**
 * @psalm-immutable
 */
class Comment
{
    /**
     * @var DateTimeImmutable
     */
    private $date;

    /**
     * @var string
     */
    private $rendered_value;

    /**
     * @var string
     */
    private $display_name;

    public function __construct(
        string $display_name,
        DateTimeImmutable $date,
        string $rendered_value
    ) {
        $this->date           = $date;
        $this->rendered_value = $rendered_value;
        $this->display_name   = $display_name;
    }

    public static function buildFromAPIResponse(array $comment_response): self
    {
        if (
            ! isset($comment_response['updateAuthor']['displayName']) ||
            ! isset($comment_response['updated']) ||
            ! isset($comment_response['renderedBody'])
        ) {
            throw new CommentAPIResponseNotWellFormedException();
        }

        return new self(
            (string) $comment_response['updateAuthor']['displayName'],
            new DateTimeImmutable($comment_response['updated']),
            (string) $comment_response['renderedBody']
        );
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getRenderedValue(): string
    {
        return $this->rendered_value;
    }

    public function getDisplayName(): string
    {
        return $this->display_name;
    }
}
