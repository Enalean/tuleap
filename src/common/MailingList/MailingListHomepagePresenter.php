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
 */

declare(strict_types=1);

namespace Tuleap\MailingList;

/**
 * @psalm-immutable
 */
final class MailingListHomepagePresenter
{
    /**
     * @var MailingListPresenter[]
     */
    public $lists;
    /**
     * @var bool
     */
    public $has_lists;
    /**
     * @var bool
     */
    public $is_project_admin;
    /**
     * @var string
     */
    public $creation_url;
    /**
     * @var string
     */
    public $purified_overridable_intro;

    /**
     * @param MailingListPresenter[] $lists
     */
    public function __construct(
        array $lists,
        bool $is_project_admin,
        string $creation_url,
        string $purified_overridable_intro,
    ) {
        $this->lists                      = $lists;
        $this->has_lists                  = ! empty($lists);
        $this->is_project_admin           = $is_project_admin;
        $this->creation_url               = $creation_url;
        $this->purified_overridable_intro = $purified_overridable_intro;
    }
}
