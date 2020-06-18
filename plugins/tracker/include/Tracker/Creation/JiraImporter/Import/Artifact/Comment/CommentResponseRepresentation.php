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

use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class CommentResponseRepresentation
{
    /**
     * @var array
     */
    private $comments;

    /**
     * @var int
     */
    private $max_results;

    /**
     * @var int
     */
    private $total;

    /**
     * @var int
     */
    private $start_at;

    public function __construct(
        array $comments,
        int $max_results,
        int $total,
        int $start_at
    ) {
        $this->comments    = $comments;
        $this->max_results = $max_results;
        $this->total       = $total;
        $this->start_at    = $start_at;
    }

    /**
     * @throws JiraConnectionException
     */
    public static function buildFromAPIResponse(?array $comment_response): self
    {
        if ($comment_response === null) {
            throw JiraConnectionException::canNotRetrieveFullCollectionOfIssueCommentsException();
        }

        if (
            ! isset($comment_response['comments']) ||
            ! is_array($comment_response['comments']) ||
            ! isset($comment_response['maxResults']) ||
            ! isset($comment_response['total']) ||
            ! array_key_exists('startAt', $comment_response)
        ) {
            throw JiraConnectionException::canNotRetrieveFullCollectionOfIssueCommentsException();
        }

        $comments    = $comment_response['comments'];
        $max_results = (int) $comment_response['maxResults'];
        $total       = (int) $comment_response['total'];
        $start_at    = (int) $comment_response['startAt'];

        return new self(
            $comments,
            $max_results,
            $total,
            $start_at
        );
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getComments(): array
    {
        return $this->comments;
    }

    public function getMaxResults(): int
    {
        return $this->max_results;
    }

    public function getStartAt(): int
    {
        return $this->start_at;
    }
}
