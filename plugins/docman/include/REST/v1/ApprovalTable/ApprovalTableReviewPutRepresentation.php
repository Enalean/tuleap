<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\ApprovalTable;

/**
 * @psalm-immutable
 */
final class ApprovalTableReviewPutRepresentation
{
    /**
     * @var string Review state of user {@from body} {@required true} {@choice not_yet,approved,rejected,comment_only,will_not_review}
     */
    public string $review;
    /**
     * @var string Review comment {@from body} {@required false}
     */
    public string $comment = '';
    /**
     * @var bool Receive email whenever the item is updated {@from body} {@required true}
     */
    public bool $notification;

    public function __construct(string $review, string $comment, bool $notification)
    {
        $this->review       = $review;
        $this->comment      = $comment;
        $this->notification = $notification;
    }
}
