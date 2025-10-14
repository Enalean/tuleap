<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap;

use Tuleap\Event\Dispatchable;

/**
 * @deprecated See \Tuleap\Request\DispatchableWithBurningParrot or \Tuleap\Request\DispatchableWithThemeSelection
 */
class BurningParrotCompatiblePageEvent implements Dispatchable
{
    public const string NAME = 'burningParrotCompatiblePage';

    private $is_in_burning_parrot_compatible_page = false;

    public function setIsInBurningParrotCompatiblePage()
    {
        $this->is_in_burning_parrot_compatible_page = true;
    }

    public function isInBurningParrotCompatiblePage()
    {
        return $this->is_in_burning_parrot_compatible_page;
    }
}
