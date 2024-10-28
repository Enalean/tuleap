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

namespace Tuleap\Docman\Reference;

use Docman_Icons;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Docman\Item\Icon\DocumentIconPresenterEvent;

final readonly class DocumentIconPresenterBuilder
{
    public function __construct(private EventDispatcherInterface $event_manager)
    {
    }

    public function buildForItem(\Docman_Item $item): DocumentIconPresenter
    {
        return $this->event_manager
            ->dispatch(new DocumentIconPresenterEvent($this->getIconWithoutPngExtension($item)))
            ->getPresenter();
    }

    private function getIconWithoutPngExtension(\Docman_Item $item): string
    {
        $docman_icons = new Docman_Icons('', $this->event_manager);

        return substr($docman_icons->getIconForItem($item), 0, -4);
    }
}
