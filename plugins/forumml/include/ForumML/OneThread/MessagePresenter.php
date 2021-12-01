<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML\OneThread;

use Tuleap\Date\TlpRelativeDatePresenter;

/**
 * @psalm-immutable
 */
final class MessagePresenter
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $body_html;
    /**
     * @var AttachmentPresenter[]
     */
    public $attachments;
    /**
     * @var bool
     */
    public $has_at_least_one_attachment;
    /**
     * @var string
     */
    public $user_name;
    /**
     * @var bool
     */
    public $has_avatar;
    /**
     * @var string
     */
    public $avatar_url;
    /**
     * @var TlpRelativeDatePresenter
     */
    public $date;

    /**
     * @param AttachmentPresenter[] $attachments
     */
    public function __construct(
        int $id,
        string $body_html,
        string $user_name,
        bool $has_avatar,
        string $avatar_url,
        array $attachments,
        TlpRelativeDatePresenter $date,
    ) {
        $this->id          = $id;
        $this->body_html   = $body_html;
        $this->attachments = $attachments;
        $this->user_name   = $user_name;
        $this->has_avatar  = $has_avatar;
        $this->avatar_url  = $avatar_url;
        $this->date        = $date;

        $this->has_at_least_one_attachment = count($attachments) > 0;
    }
}
