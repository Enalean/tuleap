<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\FilenamePattern;

/**
 * @psalm-immutable
 */
final class FilenamePattern
{
    public function __construct(private string $pattern, private bool $is_enforced)
    {
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function isEnforced(): bool
    {
        return $this->is_enforced;
    }

    public function isEnforcedAndNonEmpty(): bool
    {
        return $this->is_enforced && ! empty($this->pattern);
    }
}
