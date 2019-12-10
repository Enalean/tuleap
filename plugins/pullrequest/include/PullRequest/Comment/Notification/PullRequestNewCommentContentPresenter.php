<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Comment\Notification;

/**
 * @psalm-immutable
 */
final class PullRequestNewCommentContentPresenter
{
    /**
     * @var string
     */
    public $change_user_display_name;
    /**
     * @var string
     */
    public $change_user_profile_url;
    /**
     * @var int
     */
    public $pull_request_id;
    /**
     * @var string
     */
    public $pull_request_title;
    /**
     * @var string
     */
    public $pull_request_url;
    /**
     * @var string
     */
    public $comment;

    public function __construct(
        string $change_user_display_name,
        string $change_user_profile_url,
        int $pull_request_id,
        string $pull_request_title,
        string $pull_request_url,
        string $comment
    ) {
        $this->change_user_display_name = $change_user_display_name;
        $this->change_user_profile_url  = $change_user_profile_url;
        $this->pull_request_id          = $pull_request_id;
        $this->pull_request_title       = $pull_request_title;
        $this->pull_request_url         = $pull_request_url;
        $this->comment                  = $comment;
    }
}
