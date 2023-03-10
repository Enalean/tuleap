<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace TuleapDev\Rector;

use PhpParser\Node\Attribute;

abstract class CallbackAndAttribute
{
    public readonly string $new_callback;

    public function __construct(public readonly string $callback, public readonly string $hook, public readonly Attribute $attribute)
    {
        $this->new_callback = $this->getCamelCaseCallbackName($this->hook);
    }

    private function getCamelCaseCallbackName(string $hook): string
    {
        return lcfirst(
            implode(
                '',
                array_map(
                    ucfirst(...),
                    array_map(
                        static fn ($word) => strtoupper($word) === $word ? strtolower($word) : $word,
                        explode('_', $hook),
                    )
                )
            )
        );
    }
}
