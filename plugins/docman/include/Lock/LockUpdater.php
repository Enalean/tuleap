<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\Lock;

use Docman_Item;
use Docman_LockFactory;
use PFUser;

class LockUpdater
{
    /**
     * @var Docman_LockFactory
     */
    private $lock_factory;

    public function __construct(Docman_LockFactory $lock_factory)
    {
        $this->lock_factory = $lock_factory;
    }

    public function updateLockInformation(Docman_Item $item, bool $is_file_locked, PFUser $user) : void
    {
        $exiting_lock               = $this->lock_factory->getLockInfoForItem($item);
        $is_previous_version_locked = $exiting_lock !== false;

        if ($is_previous_version_locked && ! $is_file_locked) {
            $this->lock_factory->unlock($item);
        }

        if (! $is_previous_version_locked && $is_file_locked) {
            $this->lock_factory->lock($item, $user);
        }
    }
}
