<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\Test\Stubs;

use Tuleap\Reference\ReferenceInstance;

final class ExtractReferencesStub implements \Tuleap\Reference\ExtractReferences
{
    /**
     * @param ReferenceInstance[][] $return_values
     */
    private function __construct(private bool $always_return, private array $return_values)
    {
    }

    /**
     * @param ReferenceInstance[]   $return_values
     * @param ReferenceInstance[][] $other_return_values
     * @no-named-arguments
     */
    public static function withSuccessiveReferenceInstances(
        array $return_values,
        array ...$other_return_values,
    ): self {
        return new self(false, [$return_values, ...$other_return_values]);
    }

    public static function withNoReference(): self
    {
        return new self(true, [[]]);
    }

    #[\Override]
    public function extractReferences(string $html, int $group_id): array
    {
        if ($this->always_return) {
            return $this->return_values[0];
        }
        if (count($this->return_values) > 0) {
            return array_shift($this->return_values);
        }
        throw new \LogicException('No reference instance configured');
    }
}
