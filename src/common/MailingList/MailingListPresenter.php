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
final class MailingListPresenter
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $description;
    /**
     * @var bool
     */
    public $is_public;
    /**
     * @var string
     */
    public $admin_url;
    /**
     * @var string
     */
    public $update_url;
    /**
     * @var string
     */
    public $delete_url;

    public function __construct(
        int $id,
        string $name,
        string $description,
        bool $is_public,
        string $admin_url,
        string $update_url,
        string $delete_url
    ) {
        $this->id          = $id;
        $this->name        = $name;
        $this->description = $description;
        $this->is_public   = $is_public;
        $this->admin_url   = $admin_url;
        $this->update_url  = $update_url;
        $this->delete_url  = $delete_url;
    }
}
