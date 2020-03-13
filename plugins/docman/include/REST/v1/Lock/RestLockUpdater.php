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

namespace Tuleap\Docman\REST\v1\Lock;

use Docman_Item;
use Docman_LockFactory;
use Docman_PermissionsManager;
use PFUser;
use Tuleap\REST\I18NRestException;

class RestLockUpdater
{
    /**
     * @var Docman_LockFactory
     */
    private $lock_factory;
    /**
     * @var Docman_PermissionsManager
     */
    private $permissions_manager;

    public function __construct(Docman_LockFactory $lock_factory, Docman_PermissionsManager $permissions_manager)
    {
        $this->lock_factory        = $lock_factory;
        $this->permissions_manager = $permissions_manager;
    }

    /**
     * @throws I18NRestException
     */
    public function lockItem(Docman_Item $item, PFUser $user): void
    {
        if ($this->lock_factory->itemIsLockedByItemId((int) $item->getId())) {
            $this->throwItemIsLockedError();
        }

        $this->lock_factory->lock($item, $user);
    }

    /**
     * @throws I18NRestException
     */
    public function unlockItem(Docman_Item $item, PFUser $user): void
    {
        if ($this->permissions_manager->_itemIsLockedForUser($user, (int) $item->getId())) {
            $this->throwItemIsLockedError();
        }

        $this->lock_factory->unlock($item, $user);
    }

    /**
     * @throws I18NRestException
     */
    private function throwItemIsLockedError(): void
    {
        throw new I18NRestException(
            403,
            dgettext('tuleap-docman', 'Document is locked by another user.')
        );
    }
}
