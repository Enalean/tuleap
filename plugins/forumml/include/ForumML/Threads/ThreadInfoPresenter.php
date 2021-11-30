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

namespace Tuleap\ForumML\Threads;

use Tuleap\Date\TlpRelativeDatePresenter;

/**
 * @psalm-immutable
 */
final class ThreadInfoPresenter
{
    /**
     * @var string
     */
    public $subject;
    /**
     * @var int
     */
    public $nb_replies;
    /**
     * @var string
     */
    public $url;
    /**
     * @var bool
     */
    public $has_avatar;
    /**
     * @var string
     */
    public $avatar_url;
    /**
     * @var string
     */
    public $sender;
    /**
     * @var TlpRelativeDatePresenter
     */
    public $created_on;

    public function __construct(
        string $subject,
        int $nb_replies,
        string $url,
        bool $has_avatar,
        string $avatar_url,
        string $sender,
        TlpRelativeDatePresenter $created_on,
    ) {
        $this->subject    = $subject;
        $this->nb_replies = $nb_replies;
        $this->url        = $url;
        $this->has_avatar = $has_avatar;
        $this->avatar_url = $avatar_url;
        $this->sender     = $sender;
        $this->created_on = $created_on;
    }
}
