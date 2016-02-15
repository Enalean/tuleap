<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\Comment;

class Comment {

    /** @var int */
    private $id;

    /** @var int */
    private $pull_request_id;

    /** @var int */
    private $user_id;

    /** @var string */
    private $content;


    public function __construct($id, $pull_request_id, $user_id, $content) {
        $this->id              = $id;
        $this->pull_request_id = $pull_request_id;
        $this->user_id         = $user_id;
        $this->content         = $content;
    }

    public function getId() {
        return $this->id;
    }

    public function getPullRequestId() {
        return $this->pull_request_id;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getContent() {
        return $this->content;
    }

}
