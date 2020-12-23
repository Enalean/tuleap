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

namespace Tuleap\Reference;

/**
 * @psalm-immutable
 */
final class Nature
{
    public const NO_ICON = '';

    /**
     * @var string
     */
    public $keyword;
    /**
     * @var string
     */
    public $icon;
    /**
     * @var string
     */
    public $label;
    /**
     * @var bool
     */
    public $user_can_create_ref_with_nature;

    public function __construct(string $keyword, string $icon, string $label, bool $user_can_create_reference)
    {
        $this->keyword                         = $keyword;
        $this->icon                            = $icon;
        $this->label                           = $label;
        $this->user_can_create_ref_with_nature = $user_can_create_reference;
    }
}
