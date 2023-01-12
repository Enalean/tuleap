<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\User\Account;

use Tuleap\Event\Dispatchable;

final class NotificationsOnOwnActionsCollection implements Dispatchable, \Iterator, \Countable
{
    public const NAME = 'notificationsOnOwnActionsCollection';

    private array $store = [];
    private int $i       = 0;

    public function __construct(
        /**
         * @readonly
         */
        public \PFUser $user,
    ) {
    }

    public function add(NotificationsOnOwnActionsPresenter $presenter): void
    {
        $this->store[] = $presenter;
    }

    public function current(): NotificationsOnOwnActionsPresenter
    {
        return $this->store[$this->i];
    }

    public function next(): void
    {
        $this->i++;
    }

    public function key(): int
    {
        return $this->i;
    }

    public function valid(): bool
    {
        return isset($this->store[$this->i]);
    }

    public function rewind(): void
    {
        $this->i = 0;
    }

    public function count(): int
    {
        return count($this->store);
    }
}
