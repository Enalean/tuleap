<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Color\ItemColor;
use Tuleap\Tracker\Tracker;

class Tracker_ColorPresenterCollection implements Iterator // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /** @var array */
    private $colors = [];

    public function __construct(Tracker $tracker)
    {
        foreach (ItemColor::COLOR_NAMES as $color) {
             $this->colors[] = [
                 'color'    => $color,
                 'selected' => $color === $tracker->getColor()->getName(),
             ];
        }
    }

    public function current(): array
    {
        return current($this->colors);
    }

    public function key(): int
    {
        return key($this->colors);
    }

    public function next(): void
    {
        next($this->colors);
    }

    public function rewind(): void
    {
        reset($this->colors);
    }

    public function valid(): bool
    {
        return current($this->colors) !== false;
    }
}
