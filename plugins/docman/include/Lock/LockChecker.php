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

use Tuleap\Docman\REST\v1\ExceptionItemIsLockedByAnotherUser;

class LockChecker
{
    /**
     * @var \Docman_LockFactory
     */
    private $lock_factory;

    public function __construct(\Docman_LockFactory $lock_factory)
    {
        $this->lock_factory = $lock_factory;
    }

    /**
     * @throws ExceptionItemIsLockedByAnotherUser
     */
    public function checkItemIsLocked(\Docman_Item $item, \PFUser $user): void
    {
        $lock_infos = $this->lock_factory->getLockInfoForItem($item);

        if ($lock_infos && (int)$lock_infos['user_id'] !== (int)$user->getId()) {
            throw new ExceptionItemIsLockedByAnotherUser();
        }
    }
}
