<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Item\Icon;

use Docman_Item;
use Tuleap\Event\Dispatchable;

final class ItemIconPresenterEvent implements Dispatchable
{
    public function __construct(private ItemIconPresenter $icon_presenter, private Docman_Item $item)
    {
    }

    public function getPresenter(): ItemIconPresenter
    {
        return $this->icon_presenter;
    }

    public function setPresenter(ItemIconPresenter $presenter): void
    {
        $this->icon_presenter = $presenter;
    }

    public function getItem(): Docman_Item
    {
        return $this->item;
    }
}
