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

namespace Tuleap\Tracker\Admin\GlobalAdmin\Trackers;

/**
 * @psalm-immutable
 */
class TrackerPresenter
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $item_name;
    /**
     * @var string
     */
    public $label;
    /**
     * @var bool
     */
    public $is_promoted;
    /**
     * @var string
     */
    public $admin_url;
    /**
     * @var string
     */
    public $description;
    /**
     * @var string
     */
    public $deletion_url;
    /**
     * @var bool
     */
    public $can_be_deleted;
    /**
     * @var string
     */
    public $cannot_delete_message;

    public function __construct(
        int $id,
        string $item_name,
        string $label,
        string $description,
        bool $is_promoted,
        string $admin_url,
        string $deletion_url,
        bool $can_be_deleted,
        string $cannot_delete_message,
    ) {
        $this->id                    = $id;
        $this->item_name             = $item_name;
        $this->label                 = $label;
        $this->description           = $description;
        $this->is_promoted           = $is_promoted;
        $this->admin_url             = $admin_url;
        $this->deletion_url          = $deletion_url;
        $this->can_be_deleted        = $can_be_deleted;
        $this->cannot_delete_message = $cannot_delete_message;
    }
}
