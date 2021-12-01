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
class MailingListCreationPresenter
{
    /**
     * @var int
     */
    public $group_id;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string
     */
    public $list_domain;
    /**
     * @var string
     */
    public $list_prefix;
    /**
     * @var string[]
     */
    public $existing_lists;
    /**
     * @var string
     */
    public $purified_intro;
    /**
     * @var string
     */
    public $default_name_value;
    /**
     * @var bool
     */
    public $has_existing_lists;
    /**
     * @var string
     */
    public $do_create_url;

    /**
     * @param string[] $existing_lists
     */
    public function __construct(
        int $group_id,
        \CSRFSynchronizerToken $csrf,
        string $list_domain,
        string $list_prefix,
        array $existing_lists,
        string $purified_intro,
        string $default_name_value,
        string $do_create_url,
    ) {
        $this->group_id           = $group_id;
        $this->csrf_token         = $csrf;
        $this->list_domain        = $list_domain;
        $this->list_prefix        = $list_prefix;
        $this->existing_lists     = $existing_lists;
        $this->purified_intro     = $purified_intro;
        $this->default_name_value = $default_name_value;
        $this->has_existing_lists = ! empty($existing_lists);
        $this->do_create_url      = $do_create_url;
    }
}
